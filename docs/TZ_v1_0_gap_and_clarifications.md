# EMIGRAM MARKET — ТЗ v1.0: Gap-анализ и уточнения (для “сделано корректно”)

Дата: 2026-01-09  
Цель документа: убрать двусмысленности ТЗ и дать проверяемые критерии приемки (DoD) по каждому пункту.

## 1) Короткий вердикт по текущей реализации (по репозиторию)

### ✅ Реализовано
- **Pricing / персональная цена**: расчет скидки + rounding до `.99/.90`, возврат экономии и правил.
- **REST API v1 + Sanctum**: основные endpoint’ы присутствуют и защищены.
- **Корзина / базовый split**: корзина хранит `shop_domain`, есть разбиение на товары по магазину и checkout “для магазина”.
- **Installment (расчет)**: расчет лимита/плана на базе user level, есть Stripe-обвязка.
- **AI (OpenAI)**: чат + поиск аналогов через OpenAI.
- **Админка (Filament)**: ресурсы Shops/Products/Orders/DiscountRules/PriceSnapshots и т.д.
- **Логи/метрики/очереди**: базовая инфраструктура присутствует.

### ⚠️ Частично / не соответствует ТЗ (нужно решить: “делаем” или “фиксируем ТЗ”)
- **Aimeos как core e-commerce**: в проекте есть Aimeos-репозитории, но операции корзины/заказов *не реализованы* и уходят в fallback.
- **Crawler по ТЗ (Puppeteer/Playwright + proxies)**: текущий crawler — HTTP+упрощенный DOM parsing (не браузерный рендер).
- **Selectors из БД**: extension использует hardcoded selectors; нет полного flow “селекторы в БД → API → extension/webview/crawler”.
- **Price_final = min(extension, webview, crawler)**: текущая агрегация выбирает по приоритету источника, а не минимум по всем источникам.
- **“Без прямого партнерства” + агентская модель оформления**: текущие агенты ориентированы на REST API магазина; “без API” требует автоматизации (Playwright/Selenium/WebView flow) и юридической проработки.
- **Стек в ТЗ**: в `composer.json` фактически `Laravel ^12` и `PHP ^8.2`, а в ТЗ указано Laravel 10+ / PHP 8.3.
- **GraphQL**: в ТЗ упоминается как вариант, но в реализации нет.
- **80% coverage**: тесты есть, но покрытие не зафиксировано метрикой/порогом.

## 2) Уточнения, без которых ТЗ нельзя “сделать корректно”

### 2.1 Pricing: формула (в ТЗ есть 2 разных определения)
В тексте встречаются:
- `Emigram price = store price - personal discount`
- `Emigram_price = store_price * (1 - discount_total)`

**Предложение**: закрепить одну модель:
- `discount_total_percent = clamp(base_percent + personal_percent, min_percent, max_percent)`
- `emigram_price = round(store_price * (1 - discount_total_percent/100))`
- `savings_abs = store_price - emigram_price`
- `savings_percent = savings_abs / store_price * 100`

### 2.2 Price_final
В ТЗ указано: `Price_final = min(extension, webview, crawler)`.

**Проблема**: в реальности источники имеют разные свойства:
- extension/webview дают “живую” цену в момент просмотра;
- crawler часто получает “каталожную” цену, может быть устаревшей/не той валютой/без доставки/без купонов.

**Нужно выбрать и закрепить одно правило** (иначе разработка будет “правильной” в одном понимании и “неправильной” в другом):

**Вариант A (строго по ТЗ)**  
`Price_final = min(актуальные_цены_по_источникам)` с TTL на источник.  
Плюсы: математически прозрачно. Минусы: риск занижать цену из “невалидного” источника.

**Вариант B (практичнее для marketplace)**  
`Price_final = best(source_priority, freshness, min_on_tie)` — как сейчас, но это надо явно описать в ТЗ.  
Плюсы: меньше ложных “слишком дешево”. Минусы: это не `min`.

### 2.3 Парсинг: селекторы в БД (extension/WebView/crawler)
В ТЗ: “DOM-парсинг (селекторы в JSON-конфиге из БД)”.

**Нужно формализовать**:
- Где хранится конфиг: `shops.parsing_selectors` (JSON).
- Формат конфига (пример):
  - `price`: массив CSS селекторов
  - `currency`: массив CSS селекторов
  - `name`: массив CSS селекторов
  - опционально: `in_stock`, `images`, `variants`, `locale`, `price_regex`
- Кто и как его отдает:
  - `GET /api/v1/shops/{domain}/parsing-config` (публичный/под токеном — решить)
  - кэширование (ETag/TTL)
- Как extension/webview применяют конфиг:
  - сначала пробуют специфичный конфиг домена
  - затем fallback

### 2.4 Crawler: “Puppeteer/Playwright + proxies + 10–45 мин”
Сейчас в коде crawler — HTTP-запрос (без браузерного рендера).

**Нужно закрепить целевую архитектуру**:
- Node воркер (Playwright/Puppeteer) как отдельный сервис/контейнер.
- API/очередь задач: Laravel кладет job → Node воркер выполняет → возвращает результат (webhook или очередь).
- Proxy:
  - per shop: `shops.proxy_pool` или `shops.proxy_strategy`
  - retry/backoff и бан-детект
- Scheduler:
  - частота 10–45 минут = “джиттер” (рандомизация) + rate limit.

### 2.5 Aimeos: роль в системе (главная двусмысленность ТЗ vs текущий код)
ТЗ: “использовать Aimeos как core e-commerce”.

Факт: проект использует Eloquent как MVP-ядро (DI прямо биндит Eloquent repo), Aimeos операции cart/order не реализованы.

**Нужно выбрать**:
- **Режим 1 (строго по ТЗ)**: Aimeos — основной storage/logic для cart/order/product/prices. Eloquent только для кастомных доменов (shops/discount rules/snapshots/etc).
- **Режим 2 (MVP-упрощение)**: Aimeos подключен, но core реализован на Eloquent; миграция на Aimeos — этап 3 (и это нужно явно указать в ТЗ/roadmap).

### 2.6 “Без прямого партнерства с магазинами” + оформление заказов (agent)
Здесь ТЗ требует очень аккуратной формулировки. “Оформляем заказ в магазине без API и без партнерства” обычно значит:
- automation через WebView (mobile) или Playwright/Selenium (server-side),
- риск банов/капчи/ToS,
- юридические ограничения (особенно EU).

**Нужно уточнить**:
- Что именно делает EMIGRAM как агент в v1.0:
  - **Вариант MVP**: EMIGRAM формирует “инструкции/checkout link”, а реальное оформление делает пользователь (semi-automated).
  - **Вариант Full**: EMIGRAM реально оформляет заказ в магазине автоматизацией (это отдельный большой проект).
- Какие магазины в v1.0:
  - только “дружелюбные” (без капчи, без жесткого антибота) / только публичные цены,
  - либо только через extension/webview без server-side automation.

### 2.7 Нефункциональные требования (SLA 200мс, GDPR, 80% coverage)
Сейчас требования описаны декларативно, но нет критериев приемки:
- **≤200мс**: для каких endpoint’ов, при какой нагрузке, на каком окружении?
- **GDPR**: какие данные персональные, какие сроки хранения, как удаляем/экспортируем?
- **80% coverage**: каким инструментом считаем, какой минимальный порог в CI?

## 3) Acceptance criteria (чтобы “готово по ТЗ” было проверяемо)

### 3.1 Pricing
- `POST /api/v1/price/resolve` принимает поля из ТЗ и возвращает:
  - `price_emigram`, `savings_abs`, `savings_percent`, `rules`
- Rounding: `.99/.90` (описать точное правило)
- Тесты на 5+ сценариев (min/max, разные уровни, rounding, валюта).

### 3.2 Parsing + PriceSnapshots
- Extension/WebView отправляют `price_store` и источник.
- Селекторы конфигурируются в БД и используются клиентами.
- Snapshot сохраняется, доступен в админке.

### 3.3 Price_final
- Выбран вариант A или B (см. 2.2), реализован и покрыт тестами.

### 3.4 Catalog/Search
- Импорт товаров для 1–2 магазинов.
- Поиск через Meilisearch: фильтры цена/категория/магазин (минимум 2 фильтра).

### 3.5 Cart/Orders/Split
- Корзина хранит товары из разных магазинов.
- Checkout создает отдельный заказ на магазин (split), либо один заказ с явным “подзаказом” на магазин (выбрать модель).
- Заказы видны пользователю и в админке.

### 3.6 Installment
- Рассчитать лимит/план на базе user level/history.
- Интеграция Stripe описана: что создаем (PaymentIntent/Subscription) и когда.

### 3.7 AI
- `/api/v1/ai/chat` работает; `/api/v1/ai/products/{id}/analogs` возвращает список.
- Если добавляется Claude — описать стратегию выбора провайдера и фоллбек.

### 3.8 Admin (Filament)
- CRUD для Shops/Products/DiscountRules/PriceSnapshots/Orders.
- Управление parsing selectors из админки.

### 3.9 Security/Legal
- Политика rate limiting для crawler/extension.
- Минимальный набор GDPR: export/delete (или явно “вне scope v1.0”).

## 4) Рекомендация (как привести ТЗ к реальности без потери смысла)
Если цель — **быстрое MVP**, самый правильный путь:
- Зафиксировать, что **Aimeos как core** и **Playwright crawler** — это **этап 3**.
- Для v1.0 MVP:
  - персональные цены + snapshots + extension/webview,
  - Eloquent core,
  - “agent” как оркестрация/интеграции только для магазинов, где это допустимо.

Если цель — **строгое соответствие этому тексту ТЗ**, то приоритеты обратные:
- сначала Aimeos cart/order/product/pricing,
- затем Node crawler (Playwright/Puppeteer + proxies),
- затем selectors-from-DB + `Price_final = min`.

