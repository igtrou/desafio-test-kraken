<?php

use App\Infrastructure\MarketData\Providers\AlphaVantageProvider;
use App\Infrastructure\MarketData\Providers\AwesomeApiProvider;
use App\Infrastructure\MarketData\Providers\StooqProvider;
use App\Infrastructure\MarketData\Providers\YahooFinanceProvider;

return [
    'default' => env('MARKET_DATA_PROVIDER', 'awesome_api'),

    'providers' => [
        'alpha_vantage' => [
            'class' => AlphaVantageProvider::class,
            'api_key' => env('ALPHA_VANTAGE_KEY'),
            'base_uri' => env('ALPHA_VANTAGE_URL', 'https://www.alphavantage.co'),
            'currency' => env('ALPHA_VANTAGE_CURRENCY', 'USD'),
            'timezone' => env('ALPHA_VANTAGE_TIMEZONE', 'UTC'),
        ],
        'awesome_api' => [
            'class' => AwesomeApiProvider::class,
            'base_uri' => env('AWESOME_API_URL', 'https://economia.awesomeapi.com.br/json/last'),
            'quote_currency' => env('AWESOME_QUOTE_CURRENCY', 'USD'),
            'timezone' => env('AWESOME_API_TIMEZONE', 'America/Sao_Paulo'),
        ],
        'yahoo_finance' => [
            'class' => YahooFinanceProvider::class,
            'base_uri' => env('YAHOO_FINANCE_URL', 'https://query1.finance.yahoo.com'),
            'currency' => env('YAHOO_FINANCE_CURRENCY', 'USD'),
        ],
        'stooq' => [
            'class' => StooqProvider::class,
            'base_uri' => env('STOOQ_URL', 'https://stooq.com'),
            'currency' => env('STOOQ_CURRENCY', 'USD'),
        ],
    ],

    'fallbacks' => [
        'stock' => ['alpha_vantage', 'yahoo_finance', 'awesome_api'],
        'crypto' => ['awesome_api', 'alpha_vantage'],
        'currency' => ['awesome_api', 'alpha_vantage'],
    ],

    'crypto_symbols' => [
        'BTC', 'ETH', 'SOL', 'ADA', 'LTC', 'XRP', 'DOGE', 'BNB', 'AVAX', 'DOT', 'LINK',
    ],

    'currency_codes' => [
        'USD', 'BRL', 'EUR', 'GBP', 'JPY', 'CHF', 'CAD', 'AUD', 'NZD', 'CNY', 'MXN', 'ARS',
    ],
];
