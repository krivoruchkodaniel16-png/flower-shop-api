# Спецификация REST API

Базовый адрес: `http://localhost:8000/api`. Заголовки: `Accept: application/json`, `Content-Type: application/json`,
для защищённых маршрутов — `Authorization: Bearer <token>`.

**Формат ошибок** (одинаков для всех эндпоинтов):

```json
{"message": "Букет не найден"}
{"message": "Ошибка валидации", "errors": {"price": ["Цена должна быть больше нуля."]}}
{"message": "Недостаточно цветов на складе: Пион розовый — нужно 18, в наличии 12", "details": {"shortages": [...]}}
```

**Списки** возвращаются с пагинацией Laravel: `{"data": [...], "links": {...}, "meta": {"current_page", "last_page", "per_page", "total"}}`.

Роли: **Все** — без токена; **Авт.** — любой авторизованный; `customer`, `florist`, `courier`, `admin`.

## Таблица эндпоинтов

| Метод | URL | Метод контроллера | Описание | Доступ | Тело запроса | Успех | Ошибки |
|-------|-----|-------------------|----------|--------|--------------|-------|--------|
| GET | /health | closure | Проверка API и БД | Все | — | 200 | 503 |
| POST | /register | AuthController@register | Регистрация покупателя | Все | name, email, phone?, password, password_confirmation | 201 | 422 |
| POST | /login | AuthController@login | Вход, выдача токена | Все | email, password | 200 | 401, 422, 429 |
| POST | /logout | AuthController@logout | Отзыв текущего токена | Авт. | — | 200 | 401 |
| GET | /user | AuthController@me | Текущий пользователь | Авт. | — | 200 | 401 |
| GET | /bouquets | BouquetController@index | Каталог: `occasion_id, flower_ids[], color, price_min, price_max, search, sort (price\|name\|created_at), order, page, per_page` | Все | — | 200 | 422 |
| GET | /bouquets/{id} | BouquetController@show | Карточка с составом и поводами | Все | — | 200 | 404 |
| POST | /bouquets | BouquetController@store | Создание букета | admin | name, description?, price, is_active?, flowers[{flower_id, quantity}], occasion_ids[] | 201 | 401, 403, 422 |
| PUT/PATCH | /bouquets/{id} | BouquetController@update | Изменение | admin | те же поля (частично) | 200 | 401, 403, 404, 422 |
| DELETE | /bouquets/{id} | BouquetController@destroy | Удаление | admin | — | 204 | 403, 404, **409** (есть в заказах) |
| POST | /bouquets/{id}/image | BouquetController@uploadImage | Загрузка фото (multipart) | admin | image (jpg/png/webp ≤ 2 МБ) | 200 | 403, 404, 422 |
| GET | /flowers | FlowerController@index | Цветы: `search, color, in_stock, include_inactive (admin, florist), sort, order, per_page` | Все | — | 200 | 422 |
| GET | /flowers/{id} | FlowerController@show | Цветок | Все | — | 200 | 404 |
| POST | /flowers | FlowerController@store | Создание | admin | name, color, price, stock_quantity, description?, is_active? | 201 | 403, 422 |
| PUT/PATCH | /flowers/{id} | FlowerController@update | Изменение | admin | те же поля | 200 | 403, 404, 422 |
| DELETE | /flowers/{id} | FlowerController@destroy | Удаление | admin | — | 204 | 403, 404, **409** (входит в букеты) |
| PATCH | /flowers/{id}/stock | FlowerController@updateStock | Поступление (+) / списание (−) | admin, florist | delta, reason? | 200 | 403, 422, **409** (остаток < 0) |
| GET | /packagings | PackagingController@index | Упаковка | Все | — | 200 | — |
| GET | /packagings/{id} | PackagingController@show | | Все | — | 200 | 404 |
| POST/PUT/DELETE | /packagings[/{id}] | PackagingController@store/update/destroy | CRUD упаковки | admin | name, type, color?, price, max_flowers, is_active? | 201/200/204 | 403, 404, 422, 409 |
| GET | /occasions | OccasionController@index | Поводы (с числом букетов) | Все | — | 200 | — |
| POST/PUT/DELETE | /occasions[/{id}] | OccasionController@store/update/destroy | CRUD поводов | admin | name, slug | 201/200/204 | 403, 404, 422 |
| POST | /custom-bouquets/calculate | CustomBouquetController@calculate | Расчёт цены в конструкторе (без сохранения), предупреждения о нарушениях правил | Все | packaging_id, items[{flower_id, quantity}] | 200 | 404, 422 |
| GET | /custom-bouquets | CustomBouquetController@index | Мои букеты | Авт. | — | 200 | 401 |
| POST | /custom-bouquets | CustomBouquetController@store | Сохранить букет | Авт. | name, comment?, packaging_id, items[] | 201 | 401, 422, **409** (правила сборки) |
| GET | /custom-bouquets/{id} | CustomBouquetController@show | Просмотр | владелец, admin | — | 200 | 403, 404 |
| PUT/PATCH | /custom-bouquets/{id} | CustomBouquetController@update | Изменение | владелец | поля (частично) | 200 | 403, 404, 422, **409** (уже заказан) |
| DELETE | /custom-bouquets/{id} | CustomBouquetController@destroy | Удаление | владелец | — | 204 | 403, 404, **409** (уже заказан) |
| GET | /orders | OrderController@index | Заказы по роли: `status, date_from, date_to, search, sort (created_at\|total_price\|delivery_date\|id), order, page, per_page` | Авт. | — | 200 | 401, 422 |
| POST | /orders | OrderController@store | **Оформление заказа** | Авт. | items[{bouquet_id \| custom_bouquet_id, quantity}], delivery{address, recipient_name, recipient_phone, delivery_date, time_from, time_to}, postcard{text, design?, signature?}?, comment? | 201 | 401, 403, 404, **409**, 422 |
| GET | /orders/{id} | OrderController@show | Детали заказа | владелец, florist (очередь), courier (свои/свободные), admin | — | 200 | 403, 404 |
| POST | /orders/{id}/cancel | OrderController@cancel | Отмена (возврат цветов на склад) | владелец | — | 200 | 403, 404, **409** (уже собирается) |
| PATCH | /orders/{id}/status | OrderController@updateStatus | Смена статуса | florist: assembling, ready; courier: delivering, delivered; admin: любой допустимый | status, comment? | 200 | 403, 404, 422, **409** (недопустимый переход) |
| PATCH | /orders/{id}/courier | OrderController@assignCourier | Назначить курьера | admin | courier_id | 200 | 403, 404, 422, 409 |
| PUT | /orders/{id}/postcard | OrderController@updatePostcard | Изменить/добавить открытку | владелец | text, design?, signature? | 200 | 403, 404, 422, **409** (сборка начата) |
| GET | /users | UserController@index | Пользователи: `role, search, per_page` | admin | — | 200 | 403 |
| PATCH | /users/{id}/role | UserController@updateRole | Сменить роль | admin | role | 200 | 403, 404, 422, 409 |
| GET | /stats | StatisticsController | Отчёт: `date_from, date_to` | admin | — | 200 | 403, 422 |
| POST | /ai/postcard-text | AiController@postcardText | Варианты текста открытки | Авт. (10/мин) | occasion, recipient, tone? (warm\|formal\|funny\|romantic) | 200 | 401, 422, 429 |
| POST | /ai/bouquet-suggestions | AiController@bouquetSuggestions | Подбор букета по запросу | Авт. (10/мин) | query | 200 | 401, 422, 429 |

## Бизнес-правила

| № | Правило | Где реализовано | Ответ при нарушении |
|---|---------|-----------------|---------------------|
| 1 | Нельзя заказать больше цветов, чем есть на складе; остатки списываются при оформлении и возвращаются при отмене | `OrderService::writeOffFlowers` (транзакция, `lockForUpdate`) | 409 + `details.shortages` |
| 2 | Статус заказа меняется только по цепочке `new → assembling → ready → delivering → delivered`, отмена — до начала доставки | `OrderStatus::allowedTransitions` | 409 |
| 3 | Статус может выставить только роль, которой он разрешён; курьер работает только со своими доставками | `OrderStatus::rolesAllowedToSet`, `OrderPolicy::updateStatus` | 403 |
| 4 | Доставка в рабочее время 09:00–21:00, интервал ≥ 60 мин, начало не раньше чем через 3 ч | `OrderService::assertDeliveryTime`, `StoreOrderRequest` | 422 |
| 5 | Индивидуальный букет: ≥ 3 цветов, не больше вместимости упаковки, нечётное количество, цветы и упаковка в продаже | `CustomBouquetService::checkRules` | 409 |
| 6 | Покупатель отменяет заказ и меняет открытку только в статусе «Новый» | `OrderService::cancel`, `updatePostcard` | 409 |
| 7 | Заказанный индивидуальный букет нельзя изменить или удалить; букет / цветок / упаковку, которые используются, нельзя удалить | сервисы + `restrictOnDelete` | 409 |
| 8 | Нельзя заказать снятый с продажи букет или чужой индивидуальный букет | `OrderService::resolveLines` | 409 / 403 |

## Расчёт стоимости

- Индивидуальный букет: Σ(цена цветка × количество) + цена упаковки + сборка 1 500 ₸. Все суммы — в тенге (₸).
- Заказ: сумма позиций + доставка 2 000 ₸ (бесплатно от 30 000 ₸) + открытка 500 ₸.
- Параметры — `config/shop.php`.

## Пример: оформление заказа

```http
POST /api/orders
Authorization: Bearer 3|xYz...
Accept: application/json
Content-Type: application/json

{
  "items": [
    {"bouquet_id": 3, "quantity": 1},
    {"custom_bouquet_id": 1, "quantity": 1}
  ],
  "delivery": {
    "address": "г. Екатеринбург, ул. Ленина, д. 10, кв. 5",
    "recipient_name": "Ольга",
    "recipient_phone": "+7 912 345-67-89",
    "delivery_date": "2026-11-20",
    "time_from": "12:00",
    "time_to": "14:00"
  },
  "postcard": {"text": "С днём рождения!", "design": "birthday", "signature": "Анна"},
  "comment": "Позвонить за 30 минут"
}
```

Ответ `201`:

```json
{
  "data": {
    "id": 16,
    "status": "new",
    "status_label": "Новый",
    "items_total": 7385,
    "delivery_price": 0,
    "postcard_price": 100,
    "total_price": 7485,
    "items": [{"type": "bouquet", "name": "Пионовая нежность", "quantity": 1, "unit_price": 5400, "subtotal": 5400}, "..."],
    "delivery": {"address": "...", "delivery_date": "2026-11-20", "time_from": "12:00", "time_to": "14:00", "courier_id": null},
    "postcard": {"design": "birthday", "design_label": "Праздничная", "text": "С днём рождения!", "signature": "Анна"},
    "next_statuses": [],
    "can_cancel": true
  }
}
```

Ответ `409` при нехватке цветов:

```json
{
  "message": "Недостаточно цветов на складе: Пион розовый «Sarah Bernhardt» — нужно 18, в наличии 12",
  "details": {"shortages": [{"flower_id": 5, "name": "Пион розовый «Sarah Bernhardt»", "required": 18, "available": 12}]}
}
```
