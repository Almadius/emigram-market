<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PriceResolveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'shop_domain' => ['required', 'string', 'max:255'],
            'product_url' => ['required', 'url', 'max:2048'],
            'price_store' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'source' => ['sometimes', 'string', 'in:extension,webview'],
            'context' => ['sometimes', 'array'],
        ];
    }
}
