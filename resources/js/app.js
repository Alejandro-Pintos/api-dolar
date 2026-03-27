import './bootstrap';
import Chart from 'chart.js/auto';

// ============================================
// Application State
// ============================================
const appState = {
    quotes: {
        usd: {
            oficial: { price: null, variation: null },
            blue: { price: null, variation: null },
            solidarity: { price: null, variation: null }
        },
        pyg: { rate: null, variation: null }
    },
    currentUsdType: 'oficial',
    currentTab: 'converter',
    currentConverter: 'pyg-ars',
    charts: {
        usd: null,
        pyg: null
    },
    history: {
        usd: [],
        pyg: []
    },
    refreshInterval: null,
    lastUpdate: null
};

// ============================================
// API Functions
// ============================================
async function fetchQuotes() {
    try {
        showLoading(true);
        
        // Fetch USD quotes
        const tipos = ['oficial', 'blue', 'solidario'];
        for (const tipo of tipos) {
            try {
                const response = await fetch(`/api/convertir?valor=1&tipo=${tipo}`);
                const data = await response.json();
                
                if (data.cotizacion) {
                    appState.quotes.usd[tipo === 'solidario' ? 'solidarity' : tipo].price = data.cotizacion;
                }
            } catch (error) {
                console.error(`Error fetching USD ${tipo}:`, error);
            }
        }
        
        // Fetch PYG quote (using a default amount to get the rate)
        try {
            const response = await fetch('/api/guarani?amount=1000');
            const data = await response.json();
            
            if (data.success && data.data) {
                appState.quotes.pyg.rate = data.data.rate;
            }
        } catch (error) {
            console.error('Error fetching PYG:', error);
        }
        
        appState.lastUpdate = new Date();
        renderQuotes();
        updateHistory();
        
    } catch (error) {
        console.error('Error fetching quotes:', error);
    } finally {
        showLoading(false);
    }
}

function updateHistory() {
    // Add current prices to history for charts
    const now = new Date();
    const timeLabel = now.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    
    // USD history
    const currentUsdPrice = appState.quotes.usd[appState.currentUsdType].price;
    if (currentUsdPrice) {
        appState.history.usd.push({ x: timeLabel, y: currentUsdPrice });
        if (appState.history.usd.length > 20) {
            appState.history.usd.shift();
        }
    }
    
    // PYG history
    const currentPygRate = appState.quotes.pyg.rate;
    if (currentPygRate) {
        appState.history.pyg.push({ x: timeLabel, y: currentPygRate });
        if (appState.history.pyg.length > 20) {
            appState.history.pyg.shift();
        }
    }
}

// ============================================
// Render Functions
// ============================================
function renderQuotes() {
    const usdPriceEl = document.getElementById('usd-price');
    const usdVariationEl = document.getElementById('usd-variation');
    const usdBadgeEl = document.getElementById('usd-badge');
    const pygPriceEl = document.getElementById('pyg-price');
    const pygVariationEl = document.getElementById('pyg-variation');
    const lastUpdateEl = document.getElementById('last-update');
    
    const usdData = appState.quotes.usd[appState.currentUsdType];
    const pygData = appState.quotes.pyg;
    
    // Render USD
    if (usdData.price) {
        usdPriceEl.textContent = formatCurrency(usdData.price, 'ARS');
        
        // Update type badge
        const typeLabels = {
            oficial: 'Oficial',
            blue: 'Blue',
            solidarity: 'Solidario'
        };
        usdBadgeEl.textContent = typeLabels[appState.currentUsdType];
    }
    
    // Render PYG
    if (pygData.rate) {
        pygPriceEl.textContent = formatCurrency(pygData.rate, 'ARS', 6);
    }
    
    // Render last update
    if (appState.lastUpdate) {
        lastUpdateEl.textContent = appState.lastUpdate.toLocaleString('es-AR');
    }
}

function formatCurrency(value, currency, decimals = 2) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(value);
}

// ============================================
// Converter Functions
// ============================================
function convertPygToArs(amount) {
    const rate = appState.quotes.pyg.rate;
    if (!rate || !amount) return null;
    return amount * rate;
}

function convertUsdToArs(amount) {
    const price = appState.quotes.usd[appState.currentUsdType].price;
    if (!price || !amount) return null;
    return amount * price;
}

// ============================================
// Chart Functions
// ============================================
function initCharts() {
    // USD Chart
    const usdCtx = document.getElementById('usd-chart');
    if (usdCtx) {
        appState.charts.usd = new Chart(usdCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'USD → ARS',
                    data: [],
                    borderColor: '#2563EB',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                }
            }
        });
    }
    
    // PYG Chart
    const pygCtx = document.getElementById('pyg-chart');
    if (pygCtx) {
        appState.charts.pyg = new Chart(pygCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'PYG → ARS',
                    data: [],
                    borderColor: '#16A34A',
                    backgroundColor: 'rgba(22, 163, 74, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                }
            }
        });
    }
}

function updateCharts() {
    if (appState.charts.usd && appState.history.usd.length > 0) {
        appState.charts.usd.data.labels = appState.history.usd.map(h => h.x);
        appState.charts.usd.data.datasets[0].data = appState.history.usd.map(h => h.y);
        appState.charts.usd.update('none');
    }
    
    if (appState.charts.pyg && appState.history.pyg.length > 0) {
        appState.charts.pyg.data.labels = appState.history.pyg.map(h => h.x);
        appState.charts.pyg.data.datasets[0].data = appState.history.pyg.map(h => h.y);
        appState.charts.pyg.update('none');
    }
}

// ============================================
// Tab Switching
// ============================================
function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            switchTab(tab);
        });
    });
    
    // Converter type tabs
    const converterTabs = document.querySelectorAll('.converter-tab-btn');
    converterTabs.forEach(btn => {
        btn.addEventListener('click', () => {
            const converter = btn.dataset.converter;
            switchConverter(converter);
        });
    });
    
    // USD type selector
    const typeButtons = document.querySelectorAll('.type-btn');
    typeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const type = btn.dataset.type;
            const currency = btn.dataset.currency;
            
            if (currency === 'usd') {
                appState.currentUsdType = type;
                updateTypeButtons();
                renderQuotes();
                // Update USD converter if active
                if (appState.currentConverter === 'usd-ars') {
                    const input = document.getElementById('usd-input');
                    if (input.value) {
                        handleUsdInput(input.value);
                    }
                }
            }
        });
    });
}

function switchTab(tab) {
    appState.currentTab = tab;
    
    // Update tab buttons
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
        if (btn.dataset.tab === tab) {
            btn.classList.add('border-blue-600', 'text-blue-600', 'bg-blue-50');
            btn.classList.remove('border-transparent', 'text-slate-500');
        } else {
            btn.classList.remove('border-blue-600', 'text-blue-600', 'bg-blue-50');
            btn.classList.add('border-transparent', 'text-slate-500');
        }
    });
    
    // Update tab content
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => {
        content.classList.add('hidden');
    });
    document.getElementById(`${tab}-content`).classList.remove('hidden');
    
    // Initialize charts when switching to charts tab
    if (tab === 'charts' && !appState.charts.usd) {
        initCharts();
        updateCharts();
    }
}

function switchConverter(converter) {
    appState.currentConverter = converter;
    
    // Update converter buttons
    const converterButtons = document.querySelectorAll('.converter-tab-btn');
    converterButtons.forEach(btn => {
        if (btn.dataset.converter === converter) {
            btn.classList.add('bg-blue-600', 'text-white');
            btn.classList.remove('bg-slate-100', 'text-slate-600');
        } else {
            btn.classList.remove('bg-blue-600', 'text-white');
            btn.classList.add('bg-slate-100', 'text-slate-600');
        }
    });
    
    // Show/hide converter panels
    const panels = document.querySelectorAll('.converter-panel');
    panels.forEach(panel => {
        panel.classList.add('hidden');
    });
    document.getElementById(`${converter}-panel`).classList.remove('hidden');
}

function updateTypeButtons() {
    const typeButtons = document.querySelectorAll('.type-btn');
    typeButtons.forEach(btn => {
        if (btn.dataset.currency === 'usd') {
            if (btn.dataset.type === appState.currentUsdType) {
                btn.classList.add('bg-blue-600', 'text-white');
                btn.classList.remove('bg-slate-100', 'text-slate-600');
            } else {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-slate-100', 'text-slate-600');
            }
        }
    });
}

// ============================================
// Input Handlers
// ============================================
function initInputHandlers() {
    const pygInput = document.getElementById('pyg-input');
    const usdInput = document.getElementById('usd-input');
    
    if (pygInput) {
        pygInput.addEventListener('input', (e) => {
            handlePygInput(e.target.value);
        });
    }
    
    if (usdInput) {
        usdInput.addEventListener('input', (e) => {
            handleUsdInput(e.target.value);
        });
    }
}

function handlePygInput(value) {
    const resultEl = document.getElementById('pyg-result');
    const amount = parseFloat(value);
    
    if (isNaN(amount) || amount <= 0) {
        resultEl.value = '';
        return;
    }
    
    const result = convertPygToArs(amount);
    if (result !== null) {
        resultEl.value = formatCurrency(result, 'ARS');
    }
}

function handleUsdInput(value) {
    const resultEl = document.getElementById('usd-result');
    const amount = parseFloat(value);
    
    if (isNaN(amount) || amount <= 0) {
        resultEl.value = '';
        return;
    }
    
    const result = convertUsdToArs(amount);
    if (result !== null) {
        resultEl.value = formatCurrency(result, 'ARS');
    }
}

// ============================================
// Auto-refresh
// ============================================
function startAutoRefresh() {
    // Refresh every 60 seconds
    appState.refreshInterval = setInterval(() => {
        fetchQuotes().then(() => {
            if (appState.currentTab === 'charts') {
                updateCharts();
            }
            // Also update converter results if there's input
            const pygInput = document.getElementById('pyg-input');
            const usdInput = document.getElementById('usd-input');
            if (pygInput.value) handlePygInput(pygInput.value);
            if (usdInput.value) handleUsdInput(usdInput.value);
        });
    }, 60000);
}

// Handle visibility change - refresh when tab becomes visible
function initVisibilityHandler() {
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            fetchQuotes();
        }
    });
}

// ============================================
// Loading State
// ============================================
function showLoading(show) {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        if (show) {
            overlay.classList.remove('hidden');
        } else {
            overlay.classList.add('hidden');
        }
    }
}

// ============================================
// Initialize Application
// ============================================
function initApp() {
    initTabs();
    initInputHandlers();
    initVisibilityHandler();
    fetchQuotes().then(() => {
        startAutoRefresh();
    });
}

// Start the app when DOM is ready
document.addEventListener('DOMContentLoaded', initApp);
