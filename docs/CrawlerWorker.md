# Crawler Worker (Playwright/Puppeteer) — опционально

## Зачем
Многие магазины отдают цену только после JS-рендера. Best practice: не пытаться “эмулировать браузер” в PHP, а вынести это в отдельный Node.js воркер (Playwright/Puppeteer).

Laravel вызывает воркер по HTTP, а если воркер не настроен — использует HTTP fallback.

## Конфигурация (Laravel)
В `.env`:

- `CRAWLER_WORKER_URL` — базовый URL воркера, например `http://crawler-worker:3000`
- `CRAWLER_WORKER_TOKEN` — shared secret (опционально)
- `CRAWLER_WORKER_TIMEOUT_SECONDS` — таймаут запроса к воркеру

## Контракт API воркера
Laravel ожидает endpoint:

- `POST /crawl`

Request JSON:
```json
{
  "url": "https://shop.com/product/123",
  "shop_domain": "shop.com",
  "selectors": {
    "price": [".price", "[data-price]"],
    "currency": [".currency"],
    "name": ["h1"]
  },
  "proxy": "http://user:pass@1.2.3.4:8080"
}
```

Response JSON (успех):
```json
{
  "success": true,
  "price": 199.99,
  "currency": "EUR",
  "product_name": "Product name"
}
```

Response JSON (ошибка):
```json
{
  "success": false,
  "error": "CAPTCHA detected"
}
```

## Безопасность
Если используется `CRAWLER_WORKER_TOKEN`, воркер должен проверять `Authorization: Bearer <token>`.

## Рекомендации по воркеру
- Rate limit + concurrency limit
- Proxy pool + health checks
- CAPTCHA/anti-bot detection
- Логирование причин падений (timeout, captcha, blocked, selector miss)




