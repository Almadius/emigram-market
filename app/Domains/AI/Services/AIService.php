<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\AIServiceInterface;
use App\Domains\AI\DTOs\AIChatRequestDTO;
use App\Domains\AI\DTOs\AIChatResponseDTO;
use App\Domains\AI\DTOs\SearchAnalogRequestDTO;
use App\Domains\Product\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

final class AIService implements AIServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function chat(AIChatRequestDTO $request): AIChatResponseDTO
    {
        try {
            $systemPrompt = 'You are a helpful shopping assistant for EMIGRAM MARKET, an aggregator of online stores. Help users find products, compare prices, and make informed purchasing decisions. Always mention personalized prices when relevant.';

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $request->getMessage()],
            ];

            if ($request->getContext() !== null) {
                $messages[] = ['role' => 'system', 'content' => "Context: {$request->getContext()}"];
            }

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            $content = $response->choices[0]->message->content ?? 'Sorry, I could not generate a response.';

            // Extract product suggestions if any
            $suggestedProducts = $this->extractProductSuggestions($content);

            return new AIChatResponseDTO(
                response: $content,
                suggestedProducts: $suggestedProducts
            );
        } catch (\Exception $e) {
            Log::error('AI chat error', ['error' => $e->getMessage()]);

            return new AIChatResponseDTO(
                response: '',
                suggestedProducts: [],
                error: 'Failed to process AI request: '.$e->getMessage()
            );
        }
    }

    public function searchAnalogs(SearchAnalogRequestDTO $request): array
    {
        try {
            $product = $this->productRepository->findById($request->getProductId());
            if ($product === null) {
                return [];
            }

            $prompt = "Find similar products to: {$product->getName()}. ";
            if ($product->getDescription() !== null) {
                $prompt .= "Description: {$product->getDescription()}. ";
            }
            if ($request->getMaxPrice() !== null) {
                $prompt .= "Maximum price: {$request->getMaxPrice()} {$product->getCurrency()}. ";
            }
            $prompt .= 'Return a JSON array with product names and brief descriptions.';

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a product search assistant. Return only valid JSON arrays.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 300,
            ]);

            $content = $response->choices[0]->message->content ?? '[]';
            $analogs = json_decode($content, true);

            if (! is_array($analogs)) {
                return [];
            }

            // Search for actual products matching the analogs
            $results = [];
            foreach ($analogs as $analog) {
                $name = $analog['name'] ?? '';
                $products = $this->productRepository->searchByQuery($name, 5);
                $results = array_merge($results, $products);
            }

            return array_slice($results, 0, 10);
        } catch (\Exception $e) {
            Log::error('AI search analogs error', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function extractProductSuggestions(string $content): array
    {
        // Simple extraction - can be improved with regex or NLP
        $suggestions = [];
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (preg_match('/product|item|товар/i', $line)) {
                $suggestions[] = trim($line);
            }
        }

        return array_slice($suggestions, 0, 5);
    }
}
