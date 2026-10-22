<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik 24 - Target dan Realisasi NPM Per Bulan</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f4f7f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header-title {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.8em;
            font-weight: 600;
        }
        
        .header-subtitle {
            text-align: center;
            color: #6c757d;
            margin-bottom: 30px;
            font-size: 1.1em;
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
            height: 450px;
        }
        
        .chart-wrapper {
            position: relative;
            height: 350px;
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
            padding: 10px;
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
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .text-success {
            color: #28a745 !important;
            font-weight: bold;
        }
        
        .text-danger {
            color: #dc3545 !important;
            font-weight: bold;
        }
        
        @media (max-width: 1200px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 400px;
            }
            
            .chart-wrapper {
                height: 320px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 12px;
            }
            
            .stat-card {
                padding: 12px;
                min-height: 80px;
            }
            
            .stat-value {
                font-size: 1.3em;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            
            .stat-card {
                min-height: 70px;
                padding: 10px;
            }
            
            .stat-value {
                font-size: 1.2em;
            }
            
            .stat-label {
                font-size: 0.8em;
            }
            
            .chart-container {
                padding: 15px;
                height: 380px;
            }
            
            .chart-wrapper {
                height: 300px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                min-height: 65px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="header-title">
            📊 Grafik Perbandingan Target dan Realisasi NPM Per Bulan
        </h1>
        <p class="header-subtitle">
            Kode Laporan: {{ $kd_laporan }}
        </p>

        <div class="stats-container">
            <div class="chart-title">📋 Statistik Ringkasan</div>
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
                    <div class="stat-label">Selisih (Gap)</div>
                    <div class="stat-value {{ $totalGap >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($totalGap, 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Persentase</div>
                    <div class="stat-value {{ $grandPercentage >= 100 ? 'text-success' : 'text-danger' }}">{{ number_format($grandPercentage, 2, ',', '.') }}%</div>
                </div>
            </div>
        </div>

        @if($usingSampleData)
        <div class="warning">
            ⚠️ Data yang ditampilkan adalah data contoh karena tidak ada data yang ditemukan dari database.
        </div>
        @endif

        <div class="chart-row">
            <div class="chart-container">
                <div class="chart-title">📈 Tren Bulanan Target vs Realisasi</div>
                <div class="chart-wrapper">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">📊 Perbandingan Bulanan Target vs Realisasi</div>
                <div class="chart-wrapper">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>        

        <div class="stats-container">
            <div class="chart-title">📋 Data Detail Target dan Realisasi NPM Per Bulan</div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th>Target</th>
                            <th>Realisasi</th>
                            <th>Gap</th>
                            <th>Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bulanData as $data)
                        <tr>
                            <td><strong>{{ $data['label'] }}</strong></td>
                            <td>{{ number_format($data['target'], 0, ',', '.') }}</td>
                            <td>{{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                            <td class="{{ $data['gap'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($data['gap'], 0, ',', '.') }}
                            </td>
                            <td>{{ number_format($data['percentage'], 2, ',', '.') }}%</td>
                        </tr>
                        @endforeach
                        <tr style="background-color: #e3f2fd;">
                            <td><strong>TOTAL</strong></td>
                            <td><strong>{{ number_format($totalTarget, 0, ',', '.') }}</strong></td>
                            <td><strong>{{ number_format($totalRealisasi, 0, ',', '.') }}</strong></td>
                            <td class="{{ $totalGap >= 0 ? 'text-success' : 'text-danger' }}">
                                <strong>{{ number_format($totalGap, 0, ',', '.') }}</strong>
                            </td>
                            <td><strong>{{ number_format($grandPercentage, 2, ',', '.') }}%</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Data dari PHP
            const bulanData = @json($bulanData);
            const bulanLabels = bulanData.map(d => d.label);
            const targets = bulanData.map(d => d.target);
            const realisasi = bulanData.map(d => d.realisasi);
            
            // Warna untuk chart
            const colorPalette = {
                target: 'rgba(255, 99, 132, 0.7)',
                targetBorder: 'rgba(255, 99, 132, 1)',
                realisasi: 'rgba(54, 162, 235, 0.7)',
                realisasiBorder: 'rgba(54, 162, 235, 1)'
            };
            
            // Format angka untuk tooltip dan sumbu Y
            function formatNumber(num) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    notation: 'compact',
                    maximumSignificantDigits: 3
                }).format(num);
            }
            
            // Format angka lengkap untuk tooltip
            function formatNumberFull(num) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR'
                }).format(num);
            }
            
            // Konfigurasi options umum untuk chart
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai (IDR)'
                        },
                        ticks: {
                            callback: function(value) {
                                return formatNumber(value);
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                let value = context.raw;
                                return label + formatNumberFull(value);
                            }
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 13,
                                weight: 'bold'
                            },
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                }
            };
            
            // Grafik Garis Tren
            const lineCtx = document.getElementById('lineChart').getContext('2d');
            const lineChart = new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: bulanLabels,
                    datasets: [{
                        label: 'Target',
                        data: targets,
                        backgroundColor: colorPalette.target,
                        borderColor: colorPalette.targetBorder,
                        borderWidth: 2,
                        fill: false,
                        tension: 0.3,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }, {
                        label: 'Realisasi',
                        data: realisasi,
                        backgroundColor: colorPalette.realisasi,
                        borderColor: colorPalette.realisasiBorder,
                        borderWidth: 2,
                        fill: false,
                        tension: 0.3,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: commonOptions
            });
            
            // Grafik Batang Perbandingan
            const barCtx = document.getElementById('barChart').getContext('2d');
            const barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: bulanLabels,
                    datasets: [{
                        label: 'Target',
                        data: targets,
                        backgroundColor: colorPalette.target,
                        borderColor: colorPalette.targetBorder,
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.6,
                    }, {
                        label: 'Realisasi',
                        data: realisasi,
                        backgroundColor: colorPalette.realisasi,
                        borderColor: colorPalette.realisasiBorder,
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.6,
                    }]
                },
                options: commonOptions
            });
        });
    </script>
</body>
</html>