<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Installment;

use Illuminate\Foundation\Http\FormRequest;

final class CalculateInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'months' => ['required', 'integer', 'min:1', 'max:36'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ];
    }
}
