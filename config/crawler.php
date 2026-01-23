<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Crawler Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the background price crawler service.
    |
    */

    'interval_minutes' => env('CRAWLER_INTERVAL_MINUTES', 30),

    'timeout' => env('CRAWLER_TIMEOUT_SECONDS', 30),

    'max_retries' => env('CRAWLER_MAX_RETRIES', 3),

    'default_selectors' => [
        'price' => [
            '.price',
            '[data-price]',
            '.product-price',
            '.current-price',
        ],
        'currency' => [
            '.currency',
            '[data-currency]',
            '[itemprop="priceCurrency"]',
        ],
        'name' => [
            'h1',
            '.product-title',
            '[data-product-name]',
            '[itemprop="name"]',
        ],
    ],

    'user_agent' => env('CRAWLER_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'),

    /*
    |--------------------------------------------------------------------------
    | Optional Browser Worker (Playwright / Puppeteer)
    |--------------------------------------------------------------------------
    |
    | Best practice: heavy JS-rendered parsing is done by a separate worker
    | (Node.js + Playwright/Puppeteer). Laravel calls it via HTTP.
    |
    | If CRAWLER_WORKER_URL is empty, Laravel falls back to HTTP fetch + regex parsing.
    |
    */
    'worker' => [
        'url' => env('CRAWLER_WORKER_URL'),
        'token' => env('CRAWLER_WORKER_TOKEN'),
        'timeout' => env('CRAWLER_WORKER_TIMEOUT_SECONDS', 45),
    ],

    'proxies' => [
        // Add proxy list if needed
        // 'http://proxy1:port',
        // 'http://proxy2:port',
    ],
];


