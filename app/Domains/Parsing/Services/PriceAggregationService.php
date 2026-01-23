<?php

declare(strict_types=1);

namespace App\Domains\Parsing\Services;

use App\Domains\Parsing\Contracts\PriceSourceRepositoryInterface;
use App\Domains\Parsing\DTOs\ParsedPriceDTO;
use App\Domains\Parsing\Enums\PriceSourceEnum;
use Illuminate\Support\Facades\Cache;

final class PriceAggregationService
{
    public function __construct(
        private readonly PriceSourceRepositoryInterface $priceSourceRepository,
    ) {
    }

    /**
     * Базовое доверие к источникам (best practice: не делать "min() по всем источникам").
     * Значение используется для расчёта trust-score вместе со свежестью и валидностью.
     */
    private const SOURCE_TRUST = [
        PriceSourceEnum::EXTENSION->value => 1.00, // пользователь видит цену прямо сейчас
        PriceSourceEnum::WEBVIEW->value => 0.85,   // близко к реальному просмотру
        PriceSourceEnum::CRAWLER->value => 0.65,   // может быть устаревшей/каталожной/без рендера
    ];

    /**
     * Максимальный возраст данных в часах для каждого источника
     */
    private const MAX_AGE_HOURS = [
        PriceSourceEnum::EXTENSION->value => 1,    // Данные extension актуальны 1 час
        PriceSourceEnum::WEBVIEW->value => 6,      // Данные webview актуальны 6 часов
        PriceSourceEnum::CRAWLER->value => 24,     // Данные crawler актуальны 24 часа
    ];

    public function aggregateBestPrice(
        string $shopDomain,
        string $productUrl
    ): ?ParsedPriceDTO {
        // Кэшируем результат агрегации на 2 минуты
        $cacheKey = sprintf('price:aggregate:%s:%s', $shopDomain, md5($productUrl));
        
        return Cache::remember($cacheKey, 120, function () use ($shopDomain, $productUrl) {
            $prices = $this->priceSourceRepository->findByProduct(
                $shopDomain,
                $productUrl
            );

            if (empty($prices)) {
                return null;
            }

            $candidates = $this->filterInvalidPrices($prices);
            if (empty($candidates)) {
                return null;
            }

            $candidates = $this->preferDominantCurrency($candidates);

            // Best practice: если есть свежие "живые" источники, используем их,
            // чтобы не переопределять реальный просмотр crawler-ценой.
            $candidates = $this->selectPreferredPool($candidates);

            // Антианомалии: вычисляем медиану и помечаем экстремальные значения
            $median = $this->median(array_map(static fn (ParsedPriceDTO $p) => $p->getPrice(), $candidates));

            $best = null;
            $bestScore = -INF;

            foreach ($candidates as $candidate) {
                $score = $this->scoreCandidate($candidate, $median);

                // tie-break: более дешевая цена при равном score (пользовательская выгода)
                if ($best === null || $score > $bestScore || ($score === $bestScore && $candidate->getPrice() < $best->getPrice())) {
                    $best = $candidate;
                    $bestScore = $score;
                }
            }

            return $best;
        });
    }

    public function savePrice(
        string $shopDomain,
        string $productUrl,
        float $price,
        string $currency,
        PriceSourceEnum $source
    ): void {
        $dto = new ParsedPriceDTO(
            shopDomain: $shopDomain,
            productUrl: $productUrl,
            price: $price,
            currency: $currency,
            source: $source,
            parsedAt: \DateTimeImmutable::createFromMutable(now()->toDateTime())
        );

        $this->priceSourceRepository->save($dto);
    }

    /**
     * @param array<ParsedPriceDTO> $prices
     * @return array<ParsedPriceDTO>
     */
    private function filterInvalidPrices(array $prices): array
    {
        return array_values(array_filter($prices, static function (ParsedPriceDTO $p): bool {
            // базовая валидация
            if ($p->getPrice() <= 0) {
                return false;
            }
            $currency = trim($p->getCurrency());
            return $currency !== '' && strlen($currency) <= 10;
        }));
    }

    /**
     * Best practice: если источники возвращают разные валюты, выбираем доминирующую группу.
     *
     * @param array<ParsedPriceDTO> $prices
     * @return array<ParsedPriceDTO>
     */
    private function preferDominantCurrency(array $prices): array
    {
        $counts = [];
        foreach ($prices as $p) {
            $cur = strtoupper($p->getCurrency());
            $counts[$cur] = ($counts[$cur] ?? 0) + 1;
        }

        if (empty($counts)) {
            return $prices;
        }

        arsort($counts);
        $topCurrency = array_key_first($counts);

        // если есть EUR и она в топе/вровень — предпочитаем EUR
        if (isset($counts['EUR']) && $counts['EUR'] === reset($counts)) {
            $topCurrency = 'EUR';
        }

        $filtered = array_values(array_filter($prices, static fn (ParsedPriceDTO $p) => strtoupper($p->getCurrency()) === $topCurrency));
        return empty($filtered) ? $prices : $filtered;
    }

    private function scoreCandidate(ParsedPriceDTO $candidate, float $median): float
    {
        $source = $candidate->getSource()->value;
        $baseTrust = self::SOURCE_TRUST[$source] ?? 0.5;

        $maxAge = self::MAX_AGE_HOURS[$source] ?? 24;
        $ageHours = now()->diffInHours($candidate->getParsedAt());

        // freshnessFactor: 1.0 (свежо) → 0.0 (на границе TTL) → 0.1 (старше TTL, но лучше чем ничего)
        $freshness = 1.0;
        if ($maxAge > 0) {
            $freshness = 1.0 - min(1.0, $ageHours / $maxAge);
        }
        if ($ageHours > $maxAge) {
            $freshness = 0.1;
        }

        // anomalyPenalty: если цена сильно отличается от медианы, штрафуем (анти-ложные занижения/ошибки парсинга)
        $anomalyPenalty = 0.0;
        if ($median > 0) {
            $ratio = $candidate->getPrice() / $median;
            if ($ratio < 0.4 || $ratio > 1.6) {
                $anomalyPenalty = 1.0;
            }
        }

        // Итоговый score
        // - доверие источнику (основа)
        // - свежесть (важно)
        // - сильный штраф за аномалию
        return ($baseTrust * 100.0) + ($freshness * 50.0) - ($anomalyPenalty * 80.0);
    }

    /**
     * Выбирает "пул" кандидатов по best practice:
     * 1) если есть свежие EXTENSION — берём только их
     * 2) иначе если есть свежие WEBVIEW — берём только их
     * 3) иначе используем все кандидаты (с trust scoring)
     *
     * @param array<ParsedPriceDTO> $candidates
     * @return array<ParsedPriceDTO>
     */
    private function selectPreferredPool(array $candidates): array
    {
        $freshBySource = [
            PriceSourceEnum::EXTENSION->value => [],
            PriceSourceEnum::WEBVIEW->value => [],
            PriceSourceEnum::CRAWLER->value => [],
        ];

        foreach ($candidates as $c) {
            $source = $c->getSource()->value;
            $maxAge = self::MAX_AGE_HOURS[$source] ?? 24;
            $ageHours = now()->diffInHours($c->getParsedAt());
            if ($ageHours <= $maxAge) {
                $freshBySource[$source][] = $c;
            }
        }

        if (!empty($freshBySource[PriceSourceEnum::EXTENSION->value])) {
            return $freshBySource[PriceSourceEnum::EXTENSION->value];
        }

        if (!empty($freshBySource[PriceSourceEnum::WEBVIEW->value])) {
            return $freshBySource[PriceSourceEnum::WEBVIEW->value];
        }

        return $candidates;
    }

    /**
     * @param array<int, float> $values
     */
    private function median(array $values): float
    {
        $values = array_values(array_filter($values, static fn ($v) => is_float($v) || is_int($v)));
        if (empty($values)) {
            return 0.0;
        }
        sort($values);
        $count = count($values);
        $mid = intdiv($count, 2);
        if ($count % 2 === 1) {
            return (float) $values[$mid];
        }
        return ((float) $values[$mid - 1] + (float) $values[$mid]) / 2.0;
    }
}


