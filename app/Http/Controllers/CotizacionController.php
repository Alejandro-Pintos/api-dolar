<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CotizacionController extends Controller
{
    public function convertir(Request $request)
    {
        $valorUSD = $request->query('valor');
        $tipo = $request->query('tipo', 'oficial'); // Por defecto usamos "oficial"

        if (!$valorUSD || !is_numeric($valorUSD)) {
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

        if (!$cotizacion) {
            return response()->json(['error' => 'Cotización no disponible.'], 500);
        }

        $resultado = $valorUSD * $cotizacion;

        return response()->json([
            'tipo' => $tipo,
            'valor_dolar' => (float) $valorUSD,
            'cotizacion' => $cotizacion,
            'resultado_en_pesos' => round($resultado, 2)
        ]);
    }
}
