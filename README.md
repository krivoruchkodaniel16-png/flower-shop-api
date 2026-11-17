# Flower Shop API — цветочный магазин с конструктором букетов

Серверная часть курсового проекта (вариант 28) по дисциплине «Основы backend-разработки».
REST API на **Laravel 12** (PHP 8.3), СУБД **SQLite** (файл в проекте, при желании — MySQL), аутентификация **Laravel Sanctum** (Bearer-токены).
Клиентская часть (Vue 3) — в отдельном репозитории `flower-shop-client`.

## Возможности

- Каталог готовых букетов: фильтры по поводу, цене, цветам и цвету, поиск, сортировка, пагинация.
- Конструктор букетов: выбор цветов и количества, упаковки, расчёт цены «на лету», проверка правил сборки.
- Оформление заказа: дата и интервал доставки, открытка, **автоматическое списание остатков цветов** в транзакции.
- Роли: **покупатель**, **флорист** (очередь сборки, учёт остатков), **курьер** (доставки), **администратор**.
- Жизненный цикл заказа: `new → assembling → ready → delivering → delivered` (+ `cancelled`).
- Загрузка фото букетов (Storage), отчёт для администратора, журнал запросов к API.
- ИИ-помощник (на повышенную оценку): текст открытки и подбор букета по запросу
  («на юбилей маме, любит пастельные тона, до 3000 рублей»). Работает и без ИИ — встроенным алгоритмом.

## Установка

Требуется только **PHP ≥ 8.3** (расширения pdo_sqlite, mbstring, openssl, fileinfo, curl, gd) и **Composer**.
Отдельный сервер базы данных и Laragon не нужны: база — файл `database/database.sqlite`,
веб-сервер запускается командой Laravel `php artisan serve`.

Самый простой способ — файл `start-project.bat` (Windows): кладётся рядом с папками
`flower-shop-api` и `flower-shop-client` и запускает всё сам.

```bash
git clone <url> flower-shop-api
cd flower-shop-api
composer install
cp .env.example .env            # Windows: copy .env.example .env
php artisan key:generate
```

Ручной запуск:

```bash
php artisan migrate --seed      # создаст database/database.sqlite и заполнит тестовыми данными
                                # (migrate:fresh --seed — пересоздать базу)
php artisan storage:link        # публичный доступ к фото букетов
php artisan serve               # http://localhost:8000
```

Проверка: `GET http://localhost:8000/api/health` → `{"app": "...", "time": "...", "database": "ok"}`.

### Переход на MySQL (необязательно)

В `.env` укажите `DB_CONNECTION=mysql` и параметры `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
(пример — в `.env.example`), затем `php artisan migrate:fresh --seed`. Код приложения менять не нужно.

## Тестовые учётные записи

Пароль у всех: **`password`**

| Роль          | Email                   |
|---------------|-------------------------|
| Администратор | `admin@flowers.test`    |
| Флорист       | `florist@flowers.test`  |
| Курьер        | `courier@flowers.test`, `courier2@flowers.test` |
| Покупатель    | `customer@flowers.test` |

## Тесты

Тесты используют SQLite в памяти (настроено в `phpunit.xml`), БД разработки не затрагивается.

```bash
php artisan test
```

34 теста: Feature (`tests/Feature`, 31) — аутентификация, каталог, конструктор, заказы, роли и политики,
ИИ с заглушкой `Http::fake()`; Unit (`tests/Unit`) — переходы статусов заказа.

## Документация

| Файл | Содержание |
|------|------------|
| [`docs/01-technical-spec.md`](docs/01-technical-spec.md) | Техническое задание: предметная область, роли, требования, ключевой сценарий, use-case, архитектура (КП 1) |
| [`docs/02-database.md`](docs/02-database.md) | ER-диаграмма, таблица сущностей, физическая модель, 3НФ, индексы (КП 2–3) |
| [`docs/schema.sql`](docs/schema.sql) | SQL-скрипт DDL + тестовые данные + SELECT с JOIN (КП 3) |
| [`docs/03-api.md`](docs/03-api.md) | Спецификация API: таблица эндпоинтов, роли, коды ответов, бизнес-правила (КП 6–9) |
| [`docs/04-test-cases.md`](docs/04-test-cases.md) | Таблица тест-кейсов (КП 10) |
| [`docs/postman_collection.json`](docs/postman_collection.json) | Коллекция Postman с переменными `{{base_url}}` и `{{token}}` |

Во всех запросах передавайте заголовок `Accept: application/json`, для защищённых — `Authorization: Bearer <token>`.

## Структура проекта

```
app/
  Enums/            UserRole, OrderStatus (+ допустимые переходы), FlowerColor, PackagingType, PostcardDesign
  Exceptions/       BusinessException — нарушение бизнес-правила (409)
  Http/
    Controllers/Api/  тонкие контроллеры: Form Request → сервис → API Resource
    Middleware/       EnsureUserHasRole (role:admin), LogApiRequest (журнал запросов)
    Requests/         валидация (сообщения на русском — lang/ru/validation.php)
    Resources/        формат ответов
  Models/           Eloquent-модели и связи
  Policies/         OrderPolicy, CustomBouquetPolicy — проверка владельца
  Services/         бизнес-логика (OrderService — ключевой сценарий)
bootstrap/app.php   middleware-псевдонимы и единый формат ошибок API
config/shop.php     параметры бизнес-логики (стоимость доставки, рабочие часы и т. д.)
database/           миграции, фабрики, сидеры
routes/api.php      маршруты API
tests/              Feature и Unit тесты
docs/               документация курсового проекта
```

## Журналы

- `storage/logs/laravel.log` — события бизнес-логики (оформление заказа, смена статуса, ошибки).
- `storage/logs/api-YYYY-MM-DD.log` — каждый запрос: метод, путь, id пользователя, код, время (без тела запроса и токенов).
- `storage/logs/ai-YYYY-MM-DD.log` — обращения к ИИ: функция, пользователь, модель, токены.

## ИИ-помощник (необязательно)

По умолчанию `AI_ENABLED=false` — используются шаблоны открыток и разбор запроса по ключевым словам.
Для подключения модели укажите в `.env` любой OpenAI-совместимый сервис, например локальную Ollama:

```env
AI_ENABLED=true
AI_BASE_URL=http://localhost:11434/v1
AI_MODEL=qwen2.5:7b
AI_API_KEY=
```

Ключ хранится только на сервере. Ответ модели запрашивается в JSON и проверяется валидатором;
при ошибке или таймауте приложение продолжает работать на встроенном алгоритме.
Ограничение — 10 запросов в минуту на пользователя, повторяющиеся запросы кэшируются.
