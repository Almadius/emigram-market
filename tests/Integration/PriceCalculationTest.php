<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domains\Pricing\DTOs\PriceResolveRequestDTO;
use App\Domains\Pricing\Services\PriceService;
use App\Domains\Product\Services\ProductService;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Integration test for personalized price calculation:
 * 1. Create product with store price
 * 2. Calculate personalized price for user
 * 3. Verify price calculation with different user levels
 * 4. Verify price enrichment in product search
 */
final class PriceCalculationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Shop $shop;

    private Product $product;

    private PriceService $priceService;

    private ProductService $productService;

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

        $this->product = Product::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Test Product',
            'price' => 100.0,
            'currency' => 'EUR',
            'url' => 'https://test-shop.com/product/1',
        ]);

        $this->priceService = $this->app->make(PriceService::class);
        $this->productService = $this->app->make(ProductService::class);
    }

    public function test_price_calculation_for_user(): void
    {
        $request = new PriceResolveRequestDTO(
            userId: $this->user->id,
            shopDomain: $this->shop->domain,
            productUrl: $this->product->url,
            storePrice: 100.0,
            currency: 'EUR'
        );

        $response = $this->priceService->resolvePrice($request);

        // Verify response structure
        $this->assertNotNull($response);
        $price = $response->getPrice();
        $this->assertNotNull($price);

        // Price should be calculated (may be same or discounted)
        $this->assertIsFloat($price->getEmigramPrice());
        $this->assertGreaterThanOrEqual(0, $price->getEmigramPrice());
        $this->assertEquals('EUR', $price->getCurrency());
    }

    public function test_product_enrichment_with_price(): void
    {
        // Get product with price enrichment for user
        $productWithPrice = $this->productService->findById($this->product->id, $this->user->id);

        $this->assertNotNull($productWithPrice);

        $product = $productWithPrice->getProduct();
        $this->assertEquals($this->product->id, $product->getId());
        $this->assertEquals('Test Product', $product->getName());

        // Verify price information
        $emigramPrice = $productWithPrice->getEmigramPrice();
        $storePrice = $productWithPrice->getStorePrice();

        // At least one price should be present
        $this->assertTrue(
            $emigramPrice !== null || $storePrice !== null,
            'Either emigram price or store price should be present'
        );

        if ($emigramPrice !== null) {
            $this->assertIsFloat($emigramPrice->getEmigramPrice());
            $this->assertGreaterThanOrEqual(0, $emigramPrice->getEmigramPrice());
        }

        if ($storePrice !== null) {
            $this->assertIsFloat($storePrice->getStorePrice());
            $this->assertGreaterThanOrEqual(0, $storePrice->getStorePrice());
        }
    }

    public function test_product_search_with_price_enrichment(): void
    {
        // Search products with user context
        $result = $this->productService->search(
            filters: [],
            page: 1,
            perPage: 20,
            userId: $this->user->id
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);

        $products = $result['data'];
        $this->assertIsArray($products);
        $this->assertGreaterThanOrEqual(1, count($products));

        // Verify first product has price information
        if (count($products) > 0) {
            $firstProduct = $products[0];
            $this->assertArrayHasKey('product', $firstProduct);

            // Product should have either emigram_price or store_price
            $hasPrice = isset($firstProduct['emigram_price']) || isset($firstProduct['store_price']);
            $this->assertTrue($hasPrice, 'Product should have price information');
        }
    }

    public function test_product_without_user_context(): void
    {
        // Get product without user (guest)
        $productWithPrice = $this->productService->findById($this->product->id, null);

        $this->assertNotNull($productWithPrice);

        // Without user, should still have store price
        $storePrice = $productWithPrice->getStorePrice();
        $this->assertNotNull($storePrice, 'Guest should see store price');
        $this->assertIsFloat($storePrice->getStorePrice());
    }

    public function test_price_calculation_with_different_store_prices(): void
    {
        // Test with different store prices
        $prices = [50.0, 100.0, 200.0, 500.0];

        foreach ($prices as $storePrice) {
            $request = new PriceResolveRequestDTO(
                userId: $this->user->id,
                shopDomain: $this->shop->domain,
                productUrl: $this->product->url,
                storePrice: $storePrice,
                currency: 'EUR'
            );

            $response = $this->priceService->resolvePrice($request);
            $price = $response->getPrice();

            $this->assertNotNull($price);
            $this->assertIsFloat($price->getEmigramPrice());
            $this->assertGreaterThanOrEqual(0, $price->getEmigramPrice());
        }
    }
}
