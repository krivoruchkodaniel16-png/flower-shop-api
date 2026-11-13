# Тест-кейсы

Перед ручной проверкой: `php artisan migrate:fresh --seed`. Пароль тестовых пользователей — `password`.
Колонка «Автотест» указывает метод в `tests/`, который проверяет тот же сценарий (`php artisan test`).
Столбец «Фактический результат» заполняется при ручной проверке в Postman.

| № | Описание | Запрос | Входные данные | Ожидаемый результат | Автотест | Фактический результат |
|---|----------|--------|----------------|---------------------|----------|-----------------------|
| 1 | Проверка доступности API и БД | GET /api/health | — | 200, `database: ok` | AuthTest::health | |
| 2 | Регистрация покупателя | POST /api/register | name, email, password ×2 | 201, токен, роль customer, пароля нет в ответе | AuthTest::user_can_register | |
| 3 | Регистрация с занятым email и коротким паролем | POST /api/register | email существующий, password «123» | 422, ошибки по полям на русском | AuthTest::registration_validation | |
| 4 | Вход с неверным паролем | POST /api/login | неверный пароль | 401, «Неверный email или пароль» | AuthTest::login_with_wrong_password | |
| 5 | Выход и повторное использование токена | POST /api/logout, GET /api/user | токен | 200, затем 401 | AuthTest::logout_revokes | |
| 6 | Запрос без токена | GET /api/orders | — | 401, «Требуется авторизация» | AuthTest::request_without_token | |
| 7 | Фильтрация каталога по поводу и цене | GET /api/bouquets?occasion_id=1&price_max=5000 | — | 200, только подходящие букеты, `meta.total` | BouquetApiTest::catalog_filters | |
| 8 | Пагинация и сортировка | GET /api/bouquets?per_page=2&sort=price&order=desc | — | 2 записи, первая — самая дорогая, `meta.last_page` | BouquetApiTest::catalog_filters | |
| 9 | Сортировка по недопустимому полю | GET /api/bouquets?sort=password | — | 422, ошибка `sort` | BouquetApiTest::sort_field_whitelist | |
| 10 | Несуществующий букет | GET /api/bouquets/999 | — | 404, «Букет не найден» | BouquetApiTest::missing_bouquet | |
| 11 | Покупатель пытается создать букет | POST /api/bouquets (токен customer) | name, price | 403 | BouquetApiTest::customer_cannot_create | |
| 12 | Администратор создаёт букет с составом | POST /api/bouquets (токен admin) | name, price, flowers[], occasion_ids[] | 201, состав сохранён в bouquet_flower | BouquetApiTest::admin_creates_bouquet | |
| 13 | Цена букета ≤ 0 | POST /api/bouquets (admin) | price = 0 | 422, «Цена должна быть больше нуля.» | BouquetApiTest::price_must_be_positive | |
| 14 | Загрузка фото букета | POST /api/bouquets/1/image (multipart) | image.jpg | 200, `image_url` заполнен | BouquetApiTest::admin_uploads_image | |
| 15 | Удаление цветка, входящего в букет | DELETE /api/flowers/1 (admin) | — | 409, запись не удалена | BouquetApiTest::flower_used_in_bouquet | |
| 16 | Расчёт цены в конструкторе | POST /api/custom-bouquets/calculate | 3 × 100 ₽ + 2 × 200 ₽, упаковка 150 ₽ | 200, total = 1150 | CustomBouquetTest::calculates_price | |
| 17 | Чётное число цветов | POST /api/custom-bouquets | 4 цветка | 409, «…дарят нечётное…» | CustomBouquetTest::even_number | |
| 18 | Превышена вместимость упаковки | POST /api/custom-bouquets | 7 цветов, max_flowers = 5 | 409 | CustomBouquetTest::packaging_capacity | |
| 19 | Чужой индивидуальный букет | GET/DELETE /api/custom-bouquets/{id} (другой покупатель) | — | 403 | CustomBouquetTest::others_cannot_see | |
| 20 | Оформление заказа | POST /api/orders | букет ×2, доставка, открытка | 201, сумма 5100, остаток цветов уменьшился | OrderTest::order_is_created | |
| 21 | Нехватка цветов на складе | POST /api/orders | количество больше остатка | 409 + `details.shortages`, заказ не создан, остаток не изменён | OrderTest::cannot_order_more_flowers | |
| 22 | Доставка вне рабочего времени | POST /api/orders | 22:00–23:00 | 422, «Доставка выполняется с 09:00 до 21:00.» | OrderTest::delivery_working_hours | |
| 23 | Ошибки данных доставки | POST /api/orders | дата в прошлом, time_to < time_from, телефон «abc» | 422, ошибки по трём полям | OrderTest::delivery_validation_errors | |
| 24 | Отмена нового заказа | POST /api/orders/{id}/cancel | — | 200, статус cancelled, цветы вернулись на склад | OrderTest::customer_cancels | |
| 25 | Полный цикл: флорист → курьер | PATCH /api/orders/{id}/status | assembling, ready (florist); delivering, delivered (courier) | 200 на каждом шаге, курьер назначен, delivered_at заполнен | OrderTest::full_workflow | |
| 26 | Отмена после начала сборки | POST /api/orders/{id}/cancel | — | 409 | OrderTest::full_workflow | |
| 27 | Недопустимый переход статуса | PATCH /api/orders/{id}/status (admin) | new → delivered | 409, «Нельзя перевести заказ…» | OrderTest::invalid_status_transition | |
| 28 | Курьер пытается начать сборку; покупатель — открыть чужой заказ | PATCH …/status, GET /api/orders/{id} | — | 403, 403; список чужих заказов пуст | OrderTest::courier_cannot_assemble | |
| 29 | Заказ снятого с продажи букета | POST /api/orders | is_active = false | 409 | OrderTest::inactive_bouquet | |
| 30 | Текст открытки от ИИ | POST /api/ai/postcard-text | повод, получатель | 200, `source: ai` (заглушка Http::fake) | AiTest::postcard_text_from_ai | |
| 31 | ИИ недоступен | POST /api/ai/postcard-text | сервис отвечает 503 | 200, `source: template` | AiTest::falls_back | |
| 32 | Подбор букета по запросу | POST /api/ai/bouquet-suggestions | «на юбилей маме, пастельные тона, до 3000 рублей» | 200, первый — пастельный букет ≤ 3000 ₽ для мамы, дорогие исключены | AiTest::bouquet_suggestion | |
| 33 | Отчёт администратора | GET /api/stats (admin) | — | 200, by_status, revenue, top_bouquets, low_stock | — (ручная проверка) | |
| 34 | Отчёт под ролью покупателя | GET /api/stats (customer) | — | 403 | — (ручная проверка) | |
