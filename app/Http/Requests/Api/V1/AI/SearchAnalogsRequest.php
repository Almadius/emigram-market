<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\AI;

use Illuminate\Foundation\Http\FormRequest;

final class SearchAnalogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'max_price' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
