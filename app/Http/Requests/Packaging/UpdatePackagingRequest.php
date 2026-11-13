<?php

namespace App\Http\Requests\Packaging;

/**
 * Правила те же, что при создании, но все поля необязательны (PUT/PATCH).
 */
class UpdatePackagingRequest extends StorePackagingRequest
{
}
