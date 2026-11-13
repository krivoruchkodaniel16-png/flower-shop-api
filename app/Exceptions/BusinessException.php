<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Нарушение бизнес-правила (нет цветов на складе, недопустимая смена статуса и т. п.).
 * Выводится в bootstrap/app.php в формате {"message": "..."} с кодом 409 (по умолчанию).
 */
class BusinessException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $status = 409,
        private readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /** Дополнительные данные для клиента (например, список недостающих цветов). */
    public function getDetails(): array
    {
        return $this->details;
    }
}
