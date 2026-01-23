<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Delivery;

use Illuminate\Foundation\Http\FormRequest;

final class CalculateDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'in:dhl,ups'],
            'from_country' => ['required', 'string', 'size:2'],
            'from_city' => ['required', 'string', 'max:255'],
            'from_postal_code' => ['required', 'string', 'max:20'],
            'to_country' => ['required', 'string', 'size:2'],
            'to_city' => ['required', 'string', 'max:255'],
            'to_postal_code' => ['required', 'string', 'max:20'],
            'weight' => ['required', 'numeric', 'min:0.01'],
            'value' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ];
    }
}
