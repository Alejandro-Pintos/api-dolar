<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Dólar Paraguay</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    
    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */
        </style>
    @endif
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <header class="text-center mb-10">
            <h1 class="text-4xl font-bold text-slate-900 mb-2">API Dólar Paraguay</h1>
            <p class="text-slate-600">Cotizaciones en tiempo real USD/ARS y PYG/ARS</p>
        </header>

        <!-- Quote Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- USD Card -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 transition-all duration-300 hover:shadow-md" id="usd-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-lg">$</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Dólar USD</h2>
                            <p class="text-sm text-slate-500">Peso Argentino</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full" id="usd-badge">
                        Oficial
                    </span>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <p class="text-3xl font-bold text-slate-900" id="usd-price">---</p>
                        <p class="text-sm text-slate-500 mt-1">1 USD = ARS</p>
                    </div>
                    <div class="flex items-center gap-1" id="usd-variation">
                        <span class="text-sm">---</span>
                    </div>
                </div>
                <!-- USD Type Selector -->
                <div class="mt-4 flex gap-2">
                    <button class="type-btn flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 bg-blue-600 text-white" data-type="oficial" data-currency="usd">
                        Oficial
                    </button>
                    <button class="type-btn flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 bg-slate-100 text-slate-600 hover:bg-slate-200" data-type="blue" data-currency="usd">
                        Blue
                    </button>
                    <button class="type-btn flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 bg-slate-100 text-slate-600 hover:bg-slate-200" data-type="solidario" data-currency="usd">
                        Solidario
                    </button>
                </div>
            </div>

            <!-- PYG Card -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 transition-all duration-300 hover:shadow-md" id="pyg-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-lg">G</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Guaraní PYG</h2>
                            <p class="text-sm text-slate-500">Peso Argentino</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full" id="pyg-badge">
                        Referencial
                    </span>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <p class="text-3xl font-bold text-slate-900" id="pyg-price">---</p>
                        <p class="text-sm text-slate-500 mt-1">1 PYG = ARS</p>
                    </div>
                    <div class="flex items-center gap-1" id="pyg-variation">
                        <span class="text-sm">---</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Tabs -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Tab Navigation -->
            <div class="flex border-b border-slate-200">
                <button class="tab-btn flex-1 px-6 py-4 text-center font-medium transition-all duration-200 border-b-2 border-blue-600 text-blue-600 bg-blue-50" data-tab="converter">
                    Conversor
                </button>
                <button class="tab-btn flex-1 px-6 py-4 text-center font-medium transition-all duration-200 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50" data-tab="charts">
                    Gráficos
                </button>
            </div>

            <!-- Tab Content: Converter -->
            <div class="tab-content p-6" id="converter-content">
                <!-- Converter Type Tabs -->
                <div class="flex gap-4 mb-6">
                    <button class="converter-tab-btn px-4 py-2 rounded-lg font-medium transition-all duration-200 bg-blue-600 text-white" data-converter="pyg-ars">
                        PYG → ARS
                    </button>
                    <button class="converter-tab-btn px-4 py-2 rounded-lg font-medium transition-all duration-200 bg-slate-100 text-slate-600 hover:bg-slate-200" data-converter="usd-ars">
                        USD → ARS
                    </button>
                </div>

                <!-- PYG to ARS Converter -->
                <div class="converter-panel" id="pyg-ars-panel">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Monto en Guaraníes (PYG)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-medium">G</span>
                                <input type="number" id="pyg-input" placeholder="100.000" class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-lg" />
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Resultado en Pesos (ARS)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-medium">$</span>
                                <input type="text" id="pyg-result" readonly placeholder="0.00" class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg bg-slate-50 text-lg font-semibold text-green-600" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- USD to ARS Converter -->
                <div class="converter-panel hidden" id="usd-ars-panel">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Monto en Dólares (USD)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-medium">$</span>
                                <input type="number" id="usd-input" placeholder="100" class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-lg" />
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Resultado en Pesos (ARS)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-medium">$</span>
                                <input type="text" id="usd-result" readonly placeholder="0.00" class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg bg-slate-50 text-lg font-semibold text-blue-600" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Charts -->
            <div class="tab-content hidden p-6" id="charts-content">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- USD Chart -->
                    <div class="bg-slate-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4">Dólar USD → ARS</h3>
                        <div class="relative h-64">
                            <canvas id="usd-chart"></canvas>
                        </div>
                    </div>
                    <!-- PYG Chart -->
                    <div class="bg-slate-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4">Guaraní PYG → ARS</h3>
                        <div class="relative h-64">
                            <canvas id="pyg-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-8 text-center text-sm text-slate-500">
            <p>Actualización automática cada 60 segundos</p>
            <p class="mt-1">Última actualización: <span id="last-update">---</span></p>
        </footer>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-white/80 flex items-center justify-center z-50 hidden">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            <span class="text-slate-700 font-medium">Cargando...</span>
        </div>
    </div>
</body>
</html>
