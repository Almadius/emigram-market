# EMIGRAM Market

Marketplace platform с поддержкой парсинга цен, управления заказами, интеграцией с доставкой и ИИ-помощником.

## 🚀 Tech Stack

- **Backend:** Laravel 12 + Filament Admin Panel
- **Database:** PostgreSQL 16
- **Cache/Queue:** Redis 7
- **Frontend:** Vue.js 3 + Vite
- **Payment:** Stripe (рассрочка)
- **Search:** Meilisearch
- **AI:** OpenAI (помощник)
- **Delivery:** DHL, UPS APIs
- **Monitoring:** Prometheus + Grafana (опционально)

## 📋 Prerequisites

- Docker & Docker Compose
- Git
- Node.js 20+ (для локальной разработки)
- Composer 2+ (для локальной разработки)

## 🛠️ Локальная установка с Docker

### 1. Клонирование репозитория

```bash
git clone <repository-url> emigram-market
cd emigram-market
```

### 2. Настройка окружения

```bash
# Копировать пример конфигурации
cp .env.example .env

# Отредактировать .env при необходимости
# Основные настройки для Docker уже настроены
```

### 3. Запуск Docker контейнеров

```bash
# Запуск всех сервисов
docker compose up -d

# Проверка статуса
docker compose ps

# Просмотр логов
docker compose logs -f app
```

### 4. Инициализация приложения

```bash
# Генерация ключа приложения (если не было)
docker compose exec app php artisan key:generate

# Выполнение миграций
docker compose exec app php artisan migrate

# (Опционально) Заполнение тестовыми данными
docker compose exec app php artisan db:seed
```

### 5. Доступ к приложению

- **API:** http://localhost:8002
- **Filament Admin:** http://localhost:8002/admin
- **PostgreSQL:** localhost:5433
- **Redis:** localhost:6381

## 🚢 Production деплой

### Автоматический деплой через GitHub Actions

При пуше в ветку `main` автоматически запускается деплой на продакшен сервер.

**Требуются GitHub Secrets:**
- `SERVER_HOST` - IP или домен сервера
- `SERVER_USER` - SSH пользователь (обычно `root` или `deploy-user`)
- `SSH_PRIVATE_KEY` - Приватный SSH ключ для доступа к серверу

### Подготовка сервера

```bash
# 1. Установка Docker и Docker Compose
curl -fsSL https://get.docker.com | sh
sudo systemctl enable docker
sudo systemctl start docker

# 2. Создание пользователя для деплоя (опционально)
sudo useradd -m -s /bin/bash deploy-user
sudo usermod -aG docker deploy-user

# 3. Создание директории для приложения
sudo mkdir -p /home/deploy-user/emigram-market
sudo chown deploy-user:deploy-user /home/deploy-user/emigram-market

# 4. Настройка .env для продакшена
sudo cp /home/deploy-user/emigram-market/.env.example /home/deploy-user/.env.market.master
sudo nano /home/deploy-user/.env.market.master
# Настроить APP_ENV=production, APP_DEBUG=false, DB credentials, API keys, etc.
```

### Первый деплой

```bash
cd /home/deploy-user/emigram-market

# 1. Скопировать master .env
cp /home/deploy-user/.env.market.master .env

# 2. Запустить контейнеры
docker compose up -d

# 3. Выполнить миграции
docker compose exec app php artisan migrate --force

# 4. Создать администратора Filament
docker compose exec app php artisan make:filament-user
```

## 🔧 Полезные команды

### Docker команды

```bash
# Перезапуск контейнеров
docker compose restart

# Остановка контейнеров
docker compose down

# Просмотр логов
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f postgres

# Вход в контейнер
docker compose exec app bash

# Очистка и пересборка
docker compose down -v
docker compose build --no-cache
docker compose up -d
```

### Laravel команды

```bash
# Artisan команды
docker compose exec app php artisan migrate
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan queue:work

# Tinker (REPL)
docker compose exec app php artisan tinker

# Создание администратора
docker compose exec app php artisan make:filament-user
```

### NPM команды (для разработки)

```bash
# Установка зависимостей
npm install

# Dev сервер с hot reload
npm run dev

# Production build
npm run build
```

## 📊 Мониторинг (опционально)

Добавьте Prometheus и Grafana в `docker-compose.yml` для мониторинга (см. пример из `emigram-partners`).

## 🔐 Безопасность

- Всегда используйте HTTPS в продакшене
- Настройте правильные CORS политики
- Используйте сильные пароли для БД
- Регулярно обновляйте зависимости
- Настройте rate limiting для API
- Храните секреты в `.env` (не коммитьте!)

## 📝 Документация API

API документация доступна по адресу `/api/documentation` (если настроен Swagger/OpenAPI).

## 🧪 Тестирование

```bash
# Запуск всех тестов
docker compose exec app php artisan test

# Запуск конкретного теста
docker compose exec app php artisan test --filter=CartTest

# Coverage отчет
docker compose exec app php artisan test --coverage
```

## 🐛 Troubleshooting

### База данных не доступна

```bash
# Проверить статус PostgreSQL
docker compose exec postgres pg_isready -U emigram

# Проверить логи
docker compose logs postgres
```

### Проблемы с правами доступа

```bash
# Исправить права на storage
docker compose exec app chown -R www-data:www-data /var/www/storage
docker compose exec app chmod -R 775 /var/www/storage
```

### Очистка всех кешей

```bash
docker compose exec app php artisan optimize:clear
```

## 📞 Поддержка

Для вопросов и багов создавайте Issue в репозитории.

## 📄 Лицензия

Proprietary - EMIGRAM © 2026
