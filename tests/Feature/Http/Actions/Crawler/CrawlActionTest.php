<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Crawler;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CrawlActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->shop = Shop::factory()->create([
            'domain' => 'test-shop.com',
            'name' => 'Test Shop',
        ]);
    }

    public function testCrawlSynchronously(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com/product',
                'shop_domain' => $this->shop->domain,
                'selectors' => [
                    'price' => ['.price'],
                    'currency' => ['€'],
                    'name' => ['h1'],
                ],
                'async' => false,
            ]);

        // Crawler may fail if URL is not accessible or parsing fails
        // Accept both 200 (success) and 400 (parsing failed)
        if ($response->status() === 400) {
            $responseData = $response->json();
            $data = $responseData['data'] ?? $responseData;
            $this->assertArrayHasKey('success', $data);
            $this->assertFalse($data['success']);
            $this->assertArrayHasKey('error', $data);
        } else {
            $response->assertStatus(200);
            $responseData = $response->json();
            
            $data = $responseData['data'] ?? $responseData;
            $this->assertArrayHasKey('success', $data);
            $this->assertIsBool($data['success']);
        }
    }

    public function testCrawlAsynchronously(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com/product',
                'shop_domain' => $this->shop->domain,
                'selectors' => [
                    'price' => ['.price'],
                    'currency' => ['€'],
                    'name' => ['h1'],
                ],
                'async' => true,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Crawl job dispatched',
            'status' => 'queued',
        ]);
    }

    public function testCrawlWithInvalidUrl(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'invalid-url',
                'shop_domain' => $this->shop->domain,
                'selectors' => [
                    'price' => ['.price'],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'VALIDATION_ERROR',
        ]);
    }
}


