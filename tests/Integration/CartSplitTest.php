<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domains\Cart\DTOs\CartItemDTO;
use App\Domains\Cart\Services\CartService;
use App\Domains\Cart\Services\CartSplitService;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Integration test for cart splitting by shops:
 * 1. Add products from different shops to cart
 * 2. Verify cart split by shop domain
 * 3. Verify items can be retrieved by shop
 */
final class CartSplitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Shop $shop1;
    private Shop $shop2;
    private Shop $shop3;
    private Product $product1;
    private Product $product2;
    private Product $product3;
    private CartService $cartService;
    private CartSplitService $cartSplitService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->shop1 = Shop::factory()->create([
            'domain' => 'shop1.com',
            'name' => 'Shop 1',
        ]);

        $this->shop2 = Shop::factory()->create([
            'domain' => 'shop2.com',
            'name' => 'Shop 2',
        ]);

        $this->shop3 = Shop::factory()->create([
            'domain' => 'shop3.com',
            'name' => 'Shop 3',
        ]);

        $this->product1 = Product::factory()->create([
            'shop_id' => $this->shop1->id,
            'name' => 'Product 1',
            'price' => 100.0,
            'currency' => 'EUR',
        ]);

        $this->product2 = Product::factory()->create([
            'shop_id' => $this->shop2->id,
            'name' => 'Product 2',
            'price' => 200.0,
            'currency' => 'EUR',
        ]);

        $this->product3 = Product::factory()->create([
            'shop_id' => $this->shop3->id,
            'name' => 'Product 3',
            'price' => 300.0,
            'currency' => 'EUR',
        ]);

        $this->cartService = $this->app->make(CartService::class);
        $this->cartSplitService = $this->app->make(CartSplitService::class);
    }

    public function testCartSplitByShop(): void
    {
        // Add products from different shops
        $item1 = new CartItemDTO(
            productId: $this->product1->id,
            productName: $this->product1->name,
            quantity: 2,
            price: 100.0,
            currency: 'EUR',
            shopId: $this->shop1->id,
            shopDomain: $this->shop1->domain,
        );
        $this->cartService->addItem($this->user->id, $item1);

        $item2 = new CartItemDTO(
            productId: $this->product2->id,
            productName: $this->product2->name,
            quantity: 1,
            price: 200.0,
            currency: 'EUR',
            shopId: $this->shop2->id,
            shopDomain: $this->shop2->domain,
        );
        $this->cartService->addItem($this->user->id, $item2);

        $item3 = new CartItemDTO(
            productId: $this->product3->id,
            productName: $this->product3->name,
            quantity: 3,
            price: 300.0,
            currency: 'EUR',
            shopId: $this->shop3->id,
            shopDomain: $this->shop3->domain,
        );
        $this->cartService->addItem($this->user->id, $item3);

        // Get cart and split by shop
        $cart = $this->cartService->getCart($this->user->id);
        $split = $this->cartSplitService->splitByShop($cart);

        // Verify split structure
        $this->assertIsArray($split);
        $this->assertArrayHasKey($this->shop1->domain, $split);
        $this->assertArrayHasKey($this->shop2->domain, $split);
        $this->assertArrayHasKey($this->shop3->domain, $split);

        // Verify shop1 items
        $shop1Items = $split[$this->shop1->domain];
        $this->assertCount(1, $shop1Items);
        $this->assertEquals($this->product1->id, $shop1Items[0]->getProductId());
        $this->assertEquals(2, $shop1Items[0]->getQuantity());

        // Verify shop2 items
        $shop2Items = $split[$this->shop2->domain];
        $this->assertCount(1, $shop2Items);
        $this->assertEquals($this->product2->id, $shop2Items[0]->getProductId());
        $this->assertEquals(1, $shop2Items[0]->getQuantity());

        // Verify shop3 items
        $shop3Items = $split[$this->shop3->domain];
        $this->assertCount(1, $shop3Items);
        $this->assertEquals($this->product3->id, $shop3Items[0]->getProductId());
        $this->assertEquals(3, $shop3Items[0]->getQuantity());
    }

    public function testGetItemsForSpecificShop(): void
    {
        // Add products from different shops
        $item1 = new CartItemDTO(
            productId: $this->product1->id,
            productName: $this->product1->name,
            quantity: 2,
            price: 100.0,
            currency: 'EUR',
            shopId: $this->shop1->id,
            shopDomain: $this->shop1->domain,
        );
        $this->cartService->addItem($this->user->id, $item1);

        $item2 = new CartItemDTO(
            productId: $this->product2->id,
            productName: $this->product2->name,
            quantity: 1,
            price: 200.0,
            currency: 'EUR',
            shopId: $this->shop2->id,
            shopDomain: $this->shop2->domain,
        );
        $this->cartService->addItem($this->user->id, $item2);

        // Get items for shop1
        $cart = $this->cartService->getCart($this->user->id);
        $shop1Items = $this->cartSplitService->getItemsForShop($cart, $this->shop1->domain);

        $this->assertCount(1, $shop1Items);
        $this->assertEquals($this->product1->id, $shop1Items[0]->getProductId());
        $this->assertEquals($this->shop1->domain, $shop1Items[0]->getShopDomain());

        // Get items for shop2
        $shop2Items = $this->cartSplitService->getItemsForShop($cart, $this->shop2->domain);

        $this->assertCount(1, $shop2Items);
        $this->assertEquals($this->product2->id, $shop2Items[0]->getProductId());
        $this->assertEquals($this->shop2->domain, $shop2Items[0]->getShopDomain());

        // Get items for non-existent shop
        $emptyItems = $this->cartSplitService->getItemsForShop($cart, 'nonexistent.com');
        $this->assertCount(0, $emptyItems);
    }

    public function testCartSplitWithMultipleItemsFromSameShop(): void
    {
        // Add multiple products from same shop
        $product1b = Product::factory()->create([
            'shop_id' => $this->shop1->id,
            'name' => 'Product 1B',
            'price' => 150.0,
            'currency' => 'EUR',
        ]);

        $item1 = new CartItemDTO(
            productId: $this->product1->id,
            productName: $this->product1->name,
            quantity: 2,
            price: 100.0,
            currency: 'EUR',
            shopId: $this->shop1->id,
            shopDomain: $this->shop1->domain,
        );
        $this->cartService->addItem($this->user->id, $item1);

        $item1b = new CartItemDTO(
            productId: $product1b->id,
            productName: $product1b->name,
            quantity: 1,
            price: 150.0,
            currency: 'EUR',
            shopId: $this->shop1->id,
            shopDomain: $this->shop1->domain,
        );
        $this->cartService->addItem($this->user->id, $item1b);

        // Get items for shop1
        $cart = $this->cartService->getCart($this->user->id);
        $shop1Items = $this->cartSplitService->getItemsForShop($cart, $this->shop1->domain);

        $this->assertCount(2, $shop1Items);
        $this->assertEquals($this->product1->id, $shop1Items[0]->getProductId());
        $this->assertEquals($product1b->id, $shop1Items[1]->getProductId());
    }
}

