<?php

declare(strict_types=1);

namespace App\Domains\Crawler\Services;

use App\Domains\Crawler\Contracts\CrawlerServiceInterface;
use App\Domains\Crawler\DTOs\CrawlRequestDTO;
use App\Domains\Crawler\DTOs\CrawlResultDTO;
use App\Domains\Parsing\Enums\PriceSourceEnum;
use App\Domains\Parsing\Services\PriceAggregationService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class CrawlerService implements CrawlerServiceInterface
{
    public function __construct(
        private readonly PriceAggregationService $priceAggregationService,
    ) {}

    public function crawl(CrawlRequestDTO $request): CrawlResultDTO
    {
        try {
            // Best practice:
            // - if a browser worker (Playwright/Puppeteer) is configured, use it for JS-rendered pages
            // - otherwise, use lightweight HTTP fetch + simplified parsing as fallback
            $workerResult = $this->crawlViaWorker($request);
            if ($workerResult !== null) {
                return $workerResult;
            }

            $response = $this->httpFetch($request->getUrl(), $request->getProxy());

            if (! $response->successful()) {
                return new CrawlResultDTO(
                    success: false,
                    error: "HTTP error: {$response->status()}"
                );
            }

            $html = $response->body();
            $price = $this->extractPrice($html, $request->getSelectors());
            $currency = $this->extractCurrency($html, $request->getSelectors());
            $productName = $this->extractProductName($html, $request->getSelectors());

            if ($price === null) {
                return new CrawlResultDTO(
                    success: false,
                    error: 'Price not found in page'
                );
            }

            // Save to price snapshots
            $this->priceAggregationService->savePrice(
                shopDomain: $request->getShopDomain(),
                productUrl: $request->getUrl(),
                price: $price,
                currency: $currency ?? 'EUR',
                source: PriceSourceEnum::CRAWLER
            );

            return new CrawlResultDTO(
                success: true,
                price: $price,
                currency: $currency ?? 'EUR',
                productName: $productName
            );
        } catch (\Exception $e) {
            Log::error('Crawler error', [
                'url' => $request->getUrl(),
                'error' => $e->getMessage(),
            ]);

            return new CrawlResultDTO(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    private function httpFetch(string $url, ?string $proxy): Response
    {
        $timeout = (int) config('crawler.timeout', 30);
        $userAgent = (string) config('crawler.user_agent', 'Mozilla/5.0');

        $options = [];
        $proxy = $this->resolveProxy($proxy);
        if ($proxy !== null) {
            // Guzzle options
            $options['proxy'] = $proxy;
        }

        $response = Http::timeout($timeout)
            ->withHeaders([
                'User-Agent' => $userAgent,
                'Accept' => 'text/html,application/xhtml+xml',
            ])
            ->withOptions($options)
            ->get($url);

        // Laravel HTTP client может возвращать PromiseInterface при async-режиме.
        // Здесь мы гарантируем синхронный Response.
        if ($response instanceof \GuzzleHttp\Promise\PromiseInterface) {
            /** @var Response $response */
            $response = $response->wait();
        }

        return $response;
    }

    /**
     * Возвращает null если воркер не сконфигурирован/недоступен, чтобы можно было упасть на HTTP fallback.
     */
    private function crawlViaWorker(CrawlRequestDTO $request): ?CrawlResultDTO
    {
        $workerUrl = (string) config('crawler.worker.url', '');
        $workerUrl = rtrim($workerUrl, '/');
        if ($workerUrl === '') {
            return null;
        }

        $timeout = (int) config('crawler.worker.timeout', 45);
        $token = (string) config('crawler.worker.token', '');

        $payload = [
            'url' => $request->getUrl(),
            'shop_domain' => $request->getShopDomain(),
            'selectors' => $request->getSelectors(),
            'proxy' => $this->resolveProxy($request->getProxy()),
        ];

        try {
            $http = Http::timeout($timeout)->acceptJson();
            if ($token !== '') {
                $http = $http->withToken($token);
            }

            // Expected worker API:
            // POST {workerUrl}/crawl
            // -> { success: bool, price?: number, currency?: string, product_name?: string, error?: string }
            $res = $http->post("{$workerUrl}/crawl", $payload);
            if ($res instanceof \GuzzleHttp\Promise\PromiseInterface) {
                /** @var Response $res */
                $res = $res->wait();
            }

            if (! $res->successful()) {
                Log::warning('Crawler worker HTTP error, falling back', [
                    'status' => $res->status(),
                    'url' => $request->getUrl(),
                ]);

                return null;
            }

            $data = $res->json();
            if (! is_array($data)) {
                return null;
            }

            if (($data['success'] ?? false) !== true) {
                // воркер ответил, но не смог — возвращаем ошибку (не фоллбек), чтобы не маскировать проблемы
                return new CrawlResultDTO(
                    success: false,
                    error: (string) ($data['error'] ?? 'Worker crawl failed')
                );
            }

            $price = isset($data['price']) ? (float) $data['price'] : null;
            if ($price === null || $price <= 0) {
                return new CrawlResultDTO(success: false, error: 'Worker did not return valid price');
            }

            $currency = isset($data['currency']) && is_string($data['currency']) ? $data['currency'] : 'EUR';
            $productName = isset($data['product_name']) && is_string($data['product_name']) ? $data['product_name'] : null;

            // Save to price snapshots (crawler source)
            $this->priceAggregationService->savePrice(
                shopDomain: $request->getShopDomain(),
                productUrl: $request->getUrl(),
                price: $price,
                currency: $currency,
                source: PriceSourceEnum::CRAWLER
            );

            return new CrawlResultDTO(
                success: true,
                price: $price,
                currency: $currency,
                productName: $productName
            );
        } catch (\Exception $e) {
            Log::warning('Crawler worker exception, falling back', [
                'url' => $request->getUrl(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function resolveProxy(?string $explicitProxy): ?string
    {
        $explicitProxy = $explicitProxy !== null ? trim($explicitProxy) : null;
        if ($explicitProxy !== null && $explicitProxy !== '') {
            return $explicitProxy;
        }

        $pool = config('crawler.proxies', []);
        if (! is_array($pool) || empty($pool)) {
            return null;
        }

        $pool = array_values(array_filter(array_map(static fn ($v) => is_string($v) ? trim($v) : '', $pool), static fn (string $v) => $v !== ''));
        if (empty($pool)) {
            return null;
        }

        return $pool[array_rand($pool)];
    }

    private function extractPrice(string $html, array $selectors): ?float
    {
        $doc = $this->loadHtml($html);

        foreach ($selectors['price'] ?? [] as $selector) {
            $node = $this->queryFirstNodeByCss($doc, $selector);
            if ($node === null) {
                continue;
            }

            $raw = $this->extractNodeValue($node);
            $price = $this->parsePrice($raw);
            if ($price !== null) {
                return $price;
            }
        }

        return null;
    }

    private function extractCurrency(string $html, array $selectors): ?string
    {
        $doc = $this->loadHtml($html);

        foreach ($selectors['currency'] ?? [] as $selector) {
            // Иногда в конфиге валюта указана как символ ("€") — поддержим и это.
            if (! $this->looksLikeCssSelector($selector)) {
                $maybe = $this->normalizeCurrency(trim($selector));
                if ($maybe !== '' && str_contains($html, $selector)) {
                    return $maybe;
                }

                continue;
            }

            $node = $this->queryFirstNodeByCss($doc, $selector);
            if ($node === null) {
                continue;
            }

            $raw = trim($this->extractNodeValue($node));
            if ($raw === '') {
                continue;
            }

            return $this->normalizeCurrency($raw);
        }

        return null;
    }

    private function extractProductName(string $html, array $selectors): ?string
    {
        $doc = $this->loadHtml($html);

        foreach ($selectors['name'] ?? [] as $selector) {
            $node = $this->queryFirstNodeByCss($doc, $selector);
            if ($node === null) {
                continue;
            }

            $raw = trim($this->extractNodeValue($node));
            if ($raw !== '') {
                return $raw;
            }
        }

        return null;
    }

    private function loadHtml(string $html): \DOMDocument
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);

        // DOMDocument ожидает валидный HTML, поэтому аккуратно грузим как HTML5-ish.
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return $doc;
    }

    private function looksLikeCssSelector(string $selector): bool
    {
        $selector = trim($selector);
        if ($selector === '') {
            return false;
        }

        return str_starts_with($selector, '.')
            || str_starts_with($selector, '#')
            || str_starts_with($selector, '[')
            || preg_match('/^[a-z][a-z0-9_-]*([.#\\[]|$)/i', $selector) === 1;
    }

    private function queryFirstNodeByCss(\DOMDocument $doc, string $cssSelector): ?\DOMElement
    {
        $cssSelector = trim($cssSelector);
        if ($cssSelector === '') {
            return null;
        }
        if (! $this->looksLikeCssSelector($cssSelector)) {
            return null;
        }

        $xpathQuery = $this->cssToXpath($cssSelector);
        if ($xpathQuery === null) {
            return null;
        }

        $xpath = new \DOMXPath($doc);
        $nodes = $xpath->query($xpathQuery);
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);

        return $node instanceof \DOMElement ? $node : null;
    }

    private function extractNodeValue(\DOMElement $node): string
    {
        // Частый кейс: <meta itemprop="priceCurrency" content="EUR">
        if ($node->hasAttribute('content')) {
            $content = trim($node->getAttribute('content'));
            if ($content !== '') {
                return $content;
            }
        }

        return trim((string) $node->textContent);
    }

    /**
     * Минимальный CSS → XPath конвертер для common-селекторов.
     * Поддержка:
     * - tag
     * - .class
     * - #id
     * - tag.class
     * - [attr]
     * - [attr="value"]
     */
    private function cssToXpath(string $selector): ?string
    {
        $selector = trim($selector);

        // tag
        $tag = '*';
        $rest = $selector;

        if (preg_match('/^[a-z][a-z0-9_-]*/i', $rest, $m) === 1) {
            $tag = $m[0];
            $rest = substr($rest, strlen($m[0]));
        }

        $conditions = [];

        while ($rest !== '') {
            if (str_starts_with($rest, '.')) {
                $rest = substr($rest, 1);
                if (preg_match('/^[a-z0-9_-]+/i', $rest, $m) !== 1) {
                    return null;
                }
                $class = $m[0];
                $conditions[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";
                $rest = substr($rest, strlen($class));

                continue;
            }

            if (str_starts_with($rest, '#')) {
                $rest = substr($rest, 1);
                if (preg_match('/^[a-z0-9_-]+/i', $rest, $m) !== 1) {
                    return null;
                }
                $id = $m[0];
                $conditions[] = "@id='{$this->escapeXpathLiteral($id)}'";
                $rest = substr($rest, strlen($id));

                continue;
            }

            if (str_starts_with($rest, '[')) {
                $end = strpos($rest, ']');
                if ($end === false) {
                    return null;
                }
                $inside = substr($rest, 1, $end - 1);
                $rest = substr($rest, $end + 1);

                $inside = trim($inside);
                if ($inside === '') {
                    return null;
                }

                // attr="value" | attr='value' | attr=value | attr
                if (preg_match('/^([a-z0-9:_-]+)\\s*=\\s*([\"\\\']?)(.*?)\\2$/i', $inside, $m) === 1) {
                    $attr = $m[1];
                    $val = $m[3];
                    $conditions[] = "@{$attr}='{$this->escapeXpathLiteral($val)}'";
                } else {
                    if (preg_match('/^[a-z0-9:_-]+$/i', $inside) !== 1) {
                        return null;
                    }
                    $conditions[] = "@{$inside}";
                }

                continue;
            }

            // unsupported combinators/complex selectors
            return null;
        }

        $cond = '';
        if (! empty($conditions)) {
            $cond = '['.implode(' and ', $conditions).']';
        }

        return "//*[local-name()='{$tag}']{$cond}";
    }

    private function escapeXpathLiteral(string $value): string
    {
        // Мы используем одинарные кавычки в XPath, поэтому экранируем их безопасно.
        return str_replace("'", '&apos;', $value);
    }

    private function parsePrice(string $text): ?float
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        // Вытаскиваем "числовое ядро"
        if (preg_match('/(\d[\d\s.,]*)/', $text, $m) !== 1) {
            return null;
        }
        $num = $m[1];

        // убираем пробелы/неразрывные пробелы (thousands separators)
        $num = str_replace(["\u{00A0}", ' '], '', $num);

        $hasComma = str_contains($num, ',');
        $hasDot = str_contains($num, '.');

        if ($hasComma && $hasDot) {
            // decimal separator is the last occurring symbol
            $lastComma = strrpos($num, ',');
            $lastDot = strrpos($num, '.');
            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                // 1.234,56 -> 1234.56
                $num = str_replace('.', '', $num);
                $num = str_replace(',', '.', $num);
            } else {
                // 1,234.56 -> 1234.56
                $num = str_replace(',', '', $num);
            }
        } elseif ($hasComma) {
            // 1234,56 -> 1234.56
            // если несколько запятых, считаем что последняя — десятичная
            $parts = explode(',', $num);
            $decimal = array_pop($parts);
            $integer = implode('', $parts);
            $num = $integer.'.'.$decimal;
        } elseif ($hasDot) {
            // если несколько точек, считаем что последняя — десятичная
            $parts = explode('.', $num);
            if (count($parts) > 2) {
                $decimal = array_pop($parts);
                $integer = implode('', $parts);
                $num = $integer.'.'.$decimal;
            }
        }

        $num = preg_replace('/[^\d.]/', '', $num) ?? '';
        if ($num === '' || $num === '.') {
            return null;
        }

        $price = (float) $num;

        return $price > 0 ? $price : null;
    }

    private function normalizeCurrency(string $currency): string
    {
        $currency = strtoupper($currency);
        $map = [
            '€' => 'EUR',
            '$' => 'USD',
            '£' => 'GBP',
        ];

        return $map[$currency] ?? $currency;
    }
}
