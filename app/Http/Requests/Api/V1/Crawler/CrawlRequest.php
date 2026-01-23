<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Crawler;

use Illuminate\Foundation\Http\FormRequest;

final class CrawlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url'],
            'shop_domain' => ['required', 'string', 'max:255'],
            'selectors' => ['required', 'array'],
            'selectors.price' => ['required', 'array'],
            'selectors.currency' => ['sometimes', 'array'],
            'selectors.name' => ['sometimes', 'array'],
            'proxy' => ['sometimes', 'string', 'max:255'],
            'async' => ['sometimes', 'boolean'],
        ];
    }
}
