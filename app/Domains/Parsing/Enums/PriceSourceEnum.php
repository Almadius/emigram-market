<?php

declare(strict_types=1);

namespace App\Domains\Parsing\Enums;

enum PriceSourceEnum: string
{
    case EXTENSION = 'extension';
    case WEBVIEW = 'webview';
    case CRAWLER = 'crawler';

    public function getLabel(): string
    {
        return match ($this) {
            self::EXTENSION => 'Browser Extension',
            self::WEBVIEW => 'Mobile WebView',
            self::CRAWLER => 'Background Crawler',
        };
    }
}





