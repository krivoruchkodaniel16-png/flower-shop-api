<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ИИ-сервис
    |--------------------------------------------------------------------------
    | Любой сервис с OpenAI-совместимым API (POST {base_url}/chat/completions):
    | локальная модель Ollama, YandexGPT (OpenAI-совместимый режим), OpenAI,
    | DeepSeek и др. Ключ хранится только в .env и никогда не передаётся
    | клиенту. Если сервис выключен или недоступен, работает встроенный
    | алгоритм (шаблоны открыток и разбор запроса по ключевым словам).
    */

    'ai' => [
        'enabled' => (bool) env('AI_ENABLED', false),
        'base_url' => rtrim((string) env('AI_BASE_URL', 'http://localhost:11434/v1'), '/'),
        'api_key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL', 'qwen2.5:7b'),
        'timeout' => (int) env('AI_TIMEOUT', 20),
    ],

];
