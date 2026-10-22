<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Biaya Lain-lain per Entitas</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .stats-container {
            margin: 20px 0;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .chart-container {
            margin: 25px 0;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            position: relative;
        }
        
        .chart-wrapper {
            position: relative;
            height: 400px;
            width: 100%;
        }
        
        .chart-title {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.4em;
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin: 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            min-height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            margin: 5px 0;
            line-height: 1.2;
        }
        
        .stat-label {
            font-size: 0.85em;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 0.9em;
        }
        
        .chart-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .entitas-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 10px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #4BC0C0;
        }
        
        @media (max-width: 1200px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
            .chart-wrapper {
                height: 350px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .chart-container {
                padding: 15px;
            }
            .chart-wrapper {
                height: 300px;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center; color: #2c3e50; margin-bottom: 30px; font-size: 1.8em;">
            📊 Dashboard Biaya Lain-lain per Entitas
        </h1>

        @if($usingSampleData)
        <div class="warning">
            ⚠️ Data tidak ditemukan di database. Menampilkan data contoh untuk demonstrasi.
        </div>
        @endif

        @if(session('error'))
        <div class="warning">
            ⚠️ {{ session('error') }}
        </div>
        @endif

        <!-- Statistik Ringkasan -->
        <div class="stats-container">
            <div class="chart-title">📈 Statistik Ringkasan</div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Target</div>
                    <div class="stat-value">{{ number_format($totalTarget, 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Realisasi</div>
                    <div class="stat-value">{{ number_format($totalRealisasi, 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Gap</div>
                    <div class="stat-value">{{ number_format($totalGap, 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Persentase</div>
                    <div class="stat-value">{{ number_format($grandPercentage, 2, ',', '.') }}%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Entitas Tertinggi</div>
                    <div class="stat-value">{{ $topAchiever }}</div>
                </div>
            </div>
        </div>

        <!-- Grafik Target vs Realisasi -->
        <div class="chart-container">
            <div class="chart-title">📊 Perbandingan Target vs Realisasi</div>
            <div class="chart-wrapper">
                <canvas id="targetRealisasiChart"></canvas>
            </div>
        </div>

        <!-- Tabel Data Detail -->
        <div class="stats-container">
            <div class="chart-title">📋 Data Detail</div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Entitas</th>
                            <th>Target</th>
                            <th>Realisasi</th>
                            <th>Gap</th>
                            <th>Persentase</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entitasData as $data)
                        <tr>
                            <td><strong>{{ $data['label'] }}</strong></td>
                            <td>{{ number_format($data['target'], 0, ',', '.') }}</td>
                            <td>{{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                            <td style="color: {{ $data['gap'] > 0 ? '#28a745' : '#dc3545' }};">
                                {{ number_format($data['gap'], 0, ',', '.') }}
                            </td>
                            <td>{{ number_format($data['percentage'], 2, ',', '.') }}%</td>
                            <td>
                                <span style="color: {{ $data['keterangan'] === 'ACHIEVE' ? '#28a745' : '#dc3545' }};">
                                    <strong>{{ $data['keterangan'] }}</strong>
                                </span>
                            </td>
                        </tr>
                        @endforeach
                        <tr style="background-color: #e3f2fd;">
                            <td><strong>TOTAL</strong></td>
                            <td><strong>{{ number_format($totalTarget, 0, ',', '.') }}</strong></td>
                            <td><strong>{{ number_format($totalRealisasi, 0, ',', '.') }}</strong></td>
                            <td style="color: {{ $totalGap > 0 ? '#28a745' : '#dc3545' }};">
                                <strong>{{ number_format($totalGap, 0, ',', '.') }}</strong>
                            </td>
                            <td><strong>{{ number_format($grandPercentage, 2, ',', '.') }}%</strong></td>
                            <td>-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Data dari PHP
        const entitasData = @json($entitasData);
        const entitasLabels = entitasData.map(d => d.label);
        
        console.log('Data Dashboard:', entitasData);

        // Fungsi untuk format angka
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                notation: 'compact'
            }).format(num);
        }

        // Warna untuk chart
        const colorPalette = {
            target: '#FF6384',
            realisasi: '#36A2EB'
        };

        // Fungsi untuk membuat chart
        function createChart(ctx, type, data, options) {
            return new Chart(ctx, {
                type: type,
                data: data,
                options: {
                    ...options,
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            left: 10,
                            right: 10,
                            top: 10,
                            bottom: 10
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw;
                                    return `${label}: ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value)}`;
                                }
                            }
                        },
                        ...options.plugins
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatNumber(value);
                                }
                            },
                            ...options.scales?.y
                        }
                    },
                    ...options
                }
            });
        }

        // Inisialisasi chart setelah halaman selesai dimuat
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Menginisialisasi chart...');
            
            // Grafik Target vs Realisasi
            const trCtx = document.getElementById('targetRealisasiChart');
            if (trCtx) {
                createChart(trCtx, 'bar', {
                    labels: entitasLabels,
                    datasets: [{
                        label: 'Target',
                        data: entitasData.map(d => d.target),
                        backgroundColor: colorPalette.target,
                        borderColor: colorPalette.target,
                        borderWidth: 1
                    }, {
                        label: 'Realisasi',
                        data: entitasData.map(d => d.realisasi),
                        backgroundColor: colorPalette.realisasi,
                        borderColor: colorPalette.realisasi,
                        borderWidth: 1
                    }]
                }, {
                    scales: {
                        x: {
                            stacked: false
                        },
                        y: {
                            stacked: false
                        }
                    }
                });
                console.log('Chart Target vs Realisasi berhasil dibuat');
            } else {
                console.error('Canvas Target vs Realisasi tidak ditemukan');
            }
        });
    </script>
</body>
</html>