<?php

namespace App\Http\Requests\Order;

use App\Enums\PostcardDesign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Оформление заказа (ключевой сценарий).
 *
 * {
 *   "items": [{"bouquet_id": 3, "quantity": 1}, {"custom_bouquet_id": 7, "quantity": 1}],
 *   "delivery": {"address": "...", "recipient_name": "...", "recipient_phone": "+7 900 000-00-00",
 *                "delivery_date": "2026-11-20", "time_from": "12:00", "time_to": "14:00"},
 *   "postcard": {"text": "С днём рождения!", "design": "birthday", "signature": "Анна"},
 *   "comment": "Позвонить заранее"
 * }
 */
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxDate = now()->addDays((int) config('shop.max_days_ahead'))->toDateString();

        return [
            'items' => ['required', 'array', 'min:1', 'max:10'],
            // в позиции указывается ровно одно: готовый букет ИЛИ индивидуальный (второе проверяет сервис)
            'items.*.bouquet_id' => ['nullable', 'required_without:items.*.custom_bouquet_id', 'integer', 'exists:bouquets,id'],
            'items.*.custom_bouquet_id' => ['nullable', 'required_without:items.*.bouquet_id', 'integer', 'exists:custom_bouquets,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],

            'delivery' => ['required', 'array'],
            'delivery.address' => ['required', 'string', 'min:10', 'max:255'],
            'delivery.recipient_name' => ['required', 'string', 'max:100'],
            'delivery.recipient_phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-()]{10,20}$/'],
            'delivery.delivery_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.$maxDate],
            'delivery.time_from' => ['required', 'date_format:H:i'],
            'delivery.time_to' => ['required', 'date_format:H:i', 'after:delivery.time_from'],

            'postcard' => ['nullable', 'array'],
            'postcard.text' => ['required_with:postcard', 'string', 'max:500'],
            'postcard.design' => ['nullable', Rule::enum(PostcardDesign::class)],
            'postcard.signature' => ['nullable', 'string', 'max:100'],

            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'состав заказа',
            'items.*.bouquet_id' => 'букет',
            'items.*.custom_bouquet_id' => 'индивидуальный букет',
            'items.*.quantity' => 'количество',
            'delivery' => 'доставка',
            'delivery.address' => 'адрес доставки',
            'delivery.recipient_name' => 'имя получателя',
            'delivery.recipient_phone' => 'телефон получателя',
            'delivery.delivery_date' => 'дата доставки',
            'delivery.time_from' => 'время с',
            'delivery.time_to' => 'время до',
            'postcard.text' => 'текст открытки',
            'postcard.design' => 'оформление открытки',
            'postcard.signature' => 'подпись',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Корзина пуста.',
            'items.*.bouquet_id.required_without' => 'Укажите готовый букет или индивидуальный букет.',
            'items.*.custom_bouquet_id.required_without' => 'Укажите готовый букет или индивидуальный букет.',
            'delivery.delivery_date.after_or_equal' => 'Дата доставки не может быть в прошлом.',
            'delivery.delivery_date.before_or_equal' => 'Заказ можно оформить не более чем на '.config('shop.max_days_ahead').' дней вперёд.',
            'delivery.time_to.after' => 'Время окончания интервала должно быть позже времени начала.',
            'delivery.recipient_phone.regex' => 'Телефон получателя указан в неверном формате.',
        ];
    }
}
