# Agent Domain - Агентская модель для создания заказов

Домен для автоматического создания заказов в магазинах от имени EMIGRAM MARKET.

## Архитектура

### Компоненты

1. **ShopAgentInterface** - Интерфейс для агентов магазинов
2. **AgentService** - Сервис для координации создания заказов
3. **GenericShopAgent** - Универсальный агент для магазинов с REST API
4. **CreateShopOrderJob** - Job для фонового создания заказов
5. **CreateShopOrderOnOrderCreated** - Listener для автоматического запуска

### Поток работы

1. Пользователь создает заказ в EMIGRAM MARKET
2. Событие `OrderCreated` отправляется
3. `CreateShopOrderOnOrderCreated` слушатель перехватывает событие
4. Job `CreateShopOrderJob` ставится в очередь
5. Job создает заказ в магазине через `AgentService`
6. `AgentService` находит подходящего агента для магазина
7. Агент создает заказ через API магазина или автоматизацию
8. Результат сохраняется и отправляется событие `ShopOrderCreated`

## Использование

### Автоматическое создание

При создании заказа в EMIGRAM MARKET автоматически запускается создание заказа в магазине:

```php
// В CreateOrderAction или OrderService
$order = $orderService->createOrder($command);
// Автоматически запускается CreateShopOrderJob
```

### Ручное создание

```php
$agentService = app(AgentService::class);
$response = $agentService->createOrderInShop($order, [
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'shipping_address' => '123 Main St, City, Country',
    'customer_phone' => '+1234567890',
]);

if ($response->isSuccess()) {
    $shopOrderId = $response->getShopOrderId();
    // Сохранить shop_order_id в Order
}
```

## Создание специфичных агентов

Для создания агента для конкретного магазина:

1. Создайте класс, реализующий `ShopAgentInterface`:

```php
final class ShopifyAgent implements ShopAgentInterface
{
    public function createOrder(CreateShopOrderRequestDTO $request): CreateShopOrderResponseDTO
    {
        // Логика создания заказа в Shopify
    }
    
    public function supports(string $shopDomain): bool
    {
        return str_contains($shopDomain, 'myshopify.com');
    }
    
    public function getOrderStatus(string $shopOrderId, string $shopDomain): ?string
    {
        // Получение статуса заказа
    }
}
```

2. Зарегистрируйте в `AgentServiceProvider`:

```php
$agents = [
    new ShopifyAgent(),
    new WooCommerceAgent(),
    new GenericShopAgent(), // Fallback
];
```

## Конфигурация магазинов

В `config/shops.php` или в БД (Shop model):

```php
return [
    'example-shop.com' => [
        'api_url' => 'https://api.example-shop.com',
        'api_key' => env('SHOP_API_KEY'),
        'agent' => 'generic', // или 'shopify', 'woocommerce', etc.
    ],
];
```

## TODO

- [ ] Добавить поле `shop_order_id` в таблицу `orders`
- [ ] Реализовать специфичные агенты для популярных платформ (Shopify, WooCommerce)
- [ ] Добавить автоматизацию через Selenium/Puppeteer для магазинов без API
- [ ] Добавить retry логику для неудачных попыток
- [ ] Добавить webhook для получения обновлений статусов заказов от магазинов
- [ ] Добавить мониторинг и метрики создания заказов

