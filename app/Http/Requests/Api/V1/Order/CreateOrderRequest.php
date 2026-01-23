<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

final class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'shop_domain' => ['required', 'string', 'exists:shops,domain'],
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ];
    }
}
