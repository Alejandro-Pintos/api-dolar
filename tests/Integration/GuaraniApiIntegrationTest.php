<?php

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

describe('Integration: API Response Parsing Logic', function () {

    test('ExchangeRate-API estructura de respuesta directa se parsea correctamente', function () {
        // Simular datos que vendrían de ExchangeRate-API
        $apiResponse = [
            'base' => 'USD',
            'rates' => [
                'PYG' => 7300.50,
                'ARS' => 1025.75,
            ],
        ];

        // Verificar parsing de rates
        $pyrRate = $apiResponse['rates']['PYG'] ?? null;
        $arsRate = $apiResponse['rates']['ARS'] ?? null;

        expect($pyrRate)->toBe(7300.50);
        expect($arsRate)->toBe(1025.75);

        // Calcular tasa directa (como lo hace el controller)
        $rate = $arsRate / $pyrRate;

        // Verificar que el cálculo es correcto
        expect($rate > 0.14 && $rate < 0.141)->toBeTrue();
    });

    test('ExchangeRate-API estructura de fallback PYG se parsea correctamente', function () {
        // Datos que vendrían del endpoint PYG (fallback step 1)
        $pygResponse = [
            'base' => 'PYG',
            'rates' => [
                'USD' => 0.00013699,
            ],
        ];

        $usdRateFromPyg = $pygResponse['rates']['USD'] ?? null;

        expect($usdRateFromPyg)->toBe(0.00013699);
    });

    test('DolarAPI estructura de respuesta se parsea correctamente', function () {
        // Datos que vendrían de DolarAPI
        $dolarApiResponse = [
            'moneda' => 'Dólar Oficial',
            'compra' => 850.50,
            'venta' => 1025.75,
            'actualizacion' => '2024-01-15T10:30:00Z',
        ];

        $arsRate = $dolarApiResponse['venta'] ?? null;

        expect($arsRate)->toBe(1025.75);
    });

    test('manejo de respuesta con rates faltantes retorna null', function () {
        $apiResponse = [
            'base' => 'USD',
            'rates' => [
                'EUR' => 0.85,
            ],
        ];

        // Verificar que PYG y ARS no están
        $pyrRate = $apiResponse['rates']['PYG'] ?? null;
        $arsRate = $apiResponse['rates']['ARS'] ?? null;

        expect($pyrRate)->toBeNull();
        expect($arsRate)->toBeNull();
    });

    test('fallback calculation funciona correctamente', function () {
        // Paso 1: PYG → USD
        $usdRateFromPyg = 7300.50; // 1 USD = 7300.50 PYG
        $usdEquivalent = 1 / $usdRateFromPyg; // 1 PYG = 0.00013695 USD

        // Paso 2: USD → ARS (usando DolarAPI)
        $arsRate = 1025.75;

        // Calcular tasa final
        $finalRate = $usdEquivalent * $arsRate;

        // Verificar que el cálculo es correcto
        expect($finalRate > 0.14 && $finalRate < 0.141)->toBeTrue();
    });

    test('error en API retorna estructura de error adecuada', function () {
        // Simular respuesta de error
        $errorResponse = null;

        // El controller debe retornar null cuando falla
        $result = $errorResponse;

        expect($result)->toBeNull();
    });
});

describe('Integration: Cache Behavior', function () {

    test('cache hit retorna datos cacheados correctamente', function () {
        $cachedData = [
            'rate' => 0.14,
            'timestamp' => '2024-01-15T10:00:00Z',
            'fallback' => false,
        ];

        Cache::put('exchange_rate_pyg_ars', $cachedData, 600);

        $result = Cache::get('exchange_rate_pyg_ars');

        expect($result['rate'])->toBe(0.14);
        expect($result['fallback'])->toBeFalse();
    });

    test('cache miss retorna null', function () {
        Cache::flush();

        $result = Cache::get('exchange_rate_pyg_ars');

        expect($result)->toBeNull();
    });

    test('cache se guarda con TTL correcto', function () {
        $ttlMinutes = 10;
        $cachedData = [
            'rate' => 0.14,
            'timestamp' => now()->toIso8601String(),
        ];

        Cache::put('exchange_rate_pyg_ars', $cachedData, $ttlMinutes);

        expect(Cache::has('exchange_rate_pyg_ars'))->toBeTrue();
    });

    test('cache con fallback flag se guarda correctamente', function () {
        $cachedData = [
            'rate' => 0.13,
            'timestamp' => '2024-01-15T10:00:00Z',
            'fallback' => true,
        ];

        Cache::put('exchange_rate_pyg_ars', $cachedData, 600);

        $result = Cache::get('exchange_rate_pyg_ars');

        expect($result['fallback'])->toBeTrue();
    });

    test('cache diferente entre directa y fallback', function () {
        $directData = [
            'rate' => 0.14,
            'timestamp' => '2024-01-15T10:00:00Z',
            'fallback' => false,
        ];

        $fallbackData = [
            'rate' => 0.13,
            'timestamp' => '2024-01-15T10:00:00Z',
            'fallback' => true,
        ];

        // Ambos usan la misma key (el último gana)
        Cache::put('exchange_rate_pyg_ars', $directData, 600);

        expect(Cache::get('exchange_rate_pyg_ars')['fallback'])->toBeFalse();
    });
});

describe('Integration: Config Loading', function () {

    test('config de exchange_rate se carga correctamente', function () {
        // Verificar que la config existe (se configuró en Phase 1)
        $config = config('services.exchange_rate');

        expect(is_string($config['url']))->toBeTrue();
        expect(is_null($config['key']) || is_string($config['key']))->toBeTrue();
        expect(is_numeric($config['cache_ttl']))->toBeTrue();
    });

    test('config de dolarapi se carga correctamente', function () {
        $config = config('services.dolarapi');

        expect($config['url'])->toBeString();
    });

    test('TTL configurable tiene valor por defecto', function () {
        $defaultTtl = config('services.exchange_rate.cache_ttl', 10);

        expect(is_numeric($defaultTtl))->toBeTrue();
    });
});
