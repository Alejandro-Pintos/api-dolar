<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CotizacionController extends Controller
{
    private const CACHE_KEY = 'exchange_rate_pyg_ars';

    public function guarani(Request $request)
    {
        $amount = $request->query('amount');

        // Validación de entrada
        if ($amount === null || $amount === '') {
            return response()->json([
                'success' => false,
                'error' => 'Debe enviar un valor numérico válido en guaraníes.',
            ], 400);
        }

        $amount = filter_var($amount, FILTER_VALIDATE_FLOAT);

        if ($amount === false || $amount <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Debe enviar un valor numérico válido en guaraníes.',
            ], 400);
        }

        // Intentar obtener la tasa de cambio (cache o API)
        $rateData = $this->getExchangeRate();

        if (! $rateData) {
            return response()->json([
                'success' => false,
                'error' => 'Servicio de conversión no disponible',
            ], 503);
        }

        $result = $amount * $rateData['rate'];

        return response()->json([
            'success' => true,
            'data' => [
                'from' => 'PYG',
                'to' => 'ARS',
                'amount' => $amount,
                'result' => round($result, 2),
                'rate' => $rateData['rate'],
                'timestamp' => $rateData['timestamp'],
                'fallback' => $rateData['fallback'] ?? false,
            ],
        ]);
    }

    /**
     * Obtiene la tasa de cambio PYG → ARS
     * Usa cache primero, luego API primaria, fallback a two-step si falla
     */
    private function getExchangeRate(): ?array
    {
        $cacheKey = self::CACHE_KEY;
        $ttl = config('services.exchange_rate.cache_ttl', 10) * 60; // Convertir a segundos

        // Verificar cache primero
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Intentar API primaria (conversión directa PYG → ARS)
        $rateData = $this->fetchDirectRate();

        if ($rateData) {
            Cache::put($cacheKey, $rateData, $ttl);

            return $rateData;
        }

        // Fallback: conversión en dos pasos PYG → USD → ARS
        Log::warning('ExchangeRate-API falló, usando fallback PYG → USD → ARS');

        $rateData = $this->fetchFallbackRate();

        if ($rateData) {
            $rateData['fallback'] = true;
            Cache::put($cacheKey, $rateData, $ttl);

            return $rateData;
        }

        // Ambas APIs fallaron
        return null;
    }

    /**
     * Conversión directa PYG → ARS via ExchangeRate-API
     */
    private function fetchDirectRate(): ?array
    {
        $baseUrl = config('services.exchange_rate.url');
        $apiKey = config('services.exchange_rate.key');

        try {
            // ExchangeRate-API: obtiener tasas base en USD
            $url = "{$baseUrl}/latest/USD";

            if ($apiKey) {
                $url = "{$baseUrl}/latest/USD?access_key={$apiKey}";
            }

            $response = Http::get($url);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            // Verificar que tenemos las tasas necesarias
            $pyrRate = $data['rates']['PYG'] ?? null;
            $arsRate = $data['rates']['ARS'] ?? null;

            if (! $pyrRate || ! $arsRate) {
                return null;
            }

            // Calcular tasa directa PYG → ARS
            // Si 1 USD = X PYG y 1 USD = Y ARS
            // Entonces 1 PYG = Y/X ARS
            $rate = $arsRate / $pyrRate;

            return [
                'rate' => $rate,
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('ExchangeRate-API error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Fallback: conversión two-step PYG → USD → ARS
     */
    private function fetchFallbackRate(): ?array
    {
        try {
            // Paso 1: PYG → USD
            // Usamos ExchangeRate-API para obtener la tasa PYG/USD
            $baseUrl = config('services.exchange_rate.url');
            $apiKey = config('services.exchange_rate.key');

            $url = "{$baseUrl}/latest/PYG";
            if ($apiKey) {
                $url = "{$baseUrl}/latest/PYG?access_key={$apiKey}";
            }

            $response = Http::get($url);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();
            $usdRateFromPyg = $data['rates']['USD'] ?? null;

            if (! $usdRateFromPyg) {
                return null;
            }

            // Paso 2: USD → ARS (usando dolarapi.com)
            $dolarApiUrl = config('services.dolarapi.url');
            $dolarResponse = Http::get("{$dolarApiUrl}/oficial");

            if ($dolarResponse->failed()) {
                return null;
            }

            $dolarData = $dolarResponse->json();
            $arsRate = $dolarData['venta'] ?? null;

            if (! $arsRate) {
                return null;
            }

            // Calcular tasa final: PYG → ARS
            // (cantidad en PYG) / (cantidad de USD que equivale) * (valor de USD en ARS)
            // Simplified: rate = (1/pyr_to_usd) * ars_rate
            $rate = (1 / $usdRateFromPyg) * $arsRate;

            return [
                'rate' => $rate,
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('Fallback conversion error: '.$e->getMessage());

            return null;
        }
    }

    public function convertir(Request $request)
    {
        $valorUSD = $request->query('valor');
        $tipo = $request->query('tipo', 'oficial'); // Por defecto usamos "oficial"

        if (! $valorUSD || ! is_numeric($valorUSD)) {
            return response()->json(['error' => 'Debe enviar un valor numérico en dólares.'], 400);
        }

        // Obtener la URL base desde config/services.php
        $baseUrl = config('services.dolarapi.url');

        // Consumir la API externa
        $response = Http::get("{$baseUrl}/{$tipo}");

        if ($response->failed()) {
            return response()->json(['error' => 'No se pudo obtener la cotización.'], 500);
        }

        $data = $response->json();
        $cotizacion = $data['venta'] ?? null;

        if (! $cotizacion) {
            return response()->json(['error' => 'Cotización no disponible.'], 500);
        }

        $resultado = $valorUSD * $cotizacion;

        return response()->json([
            'tipo' => $tipo,
            'valor_dolar' => (float) $valorUSD,
            'cotizacion' => $cotizacion,
            'resultado_en_pesos' => round($resultado, 2),
        ]);
    }
}
