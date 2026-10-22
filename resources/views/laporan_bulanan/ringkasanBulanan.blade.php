<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan Realisasi Bulanan</title>
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
        <h1 style="text-align: center; color: #2c3e50; margin-bottom: 30px; font-size: 1.8em;">
            📊 Ringkasan Realisasi Pendapatan Bulanan
        </h1>

        @if($usingSampleData)
        <div class="warning">
            ⚠️ Data tidak ditemukan di database. Menampilkan data contoh untuk demonstrasi.
        </div>
        @endif

        <div class="stats-container">
            <div class="chart-title">📈 Statistik Ringkasan</div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Realisasi Tahunan</div>
                    <div class="stat-value">{{ number_format($totalRealisasi, 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Rata-rata per Bulan</div>
                    <div class="stat-value">{{ number_format($rataRata, 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Bulan Tertinggi</div>
                    <div class="stat-value">{{ $bulanTertinggi }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Nilai Tertinggi</div>
                    <div class="stat-value">{{ number_format($nilaiTertinggi, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="chart-row">
            <div class="chart-container">
                <div class="chart-title">📊 Realisasi per Bulan (Bar Chart)</div>
                <div class="chart-wrapper">
                    <canvas id="barChart"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">🥧 Distribusi per Bulan (Pie Chart)</div>
                <div class="chart-wrapper">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>

        <div class="chart-container">
            <div class="chart-title">📈 Trend Realisasi Bulanan (Line Chart)</div>
            <div class="chart-wrapper">
                <canvas id="lineChart"></canvas>
            </div>
        </div>

        <div class="stats-container">
            <div class="chart-title">📋 Data Detail Bulanan</div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th>Realisasi</th>
                            <th>Persentase</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chartData as $bulan => $realisasi)
                        <tr>
                            <td><strong>{{ $bulan }}</strong></td>
                            <td>{{ number_format($realisasi, 0, ',', '.') }}</td>
                            <td>
                                @php
                                $persentase = $totalRealisasi > 0 ? ($realisasi / $totalRealisasi) * 100 : 0;
                                @endphp
                                {{ number_format($persentase, 2, ',', '.') . '%' }}
                            </td>
                            <td>
                                @if($realisasi > 0)
                                    @if($realisasi == $nilaiTertinggi)
                                        🏆 Tertinggi
                                    @else
                                        ✅ Ada Data
                                    @endif
                                @else
                                    ❌ Tidak Ada Data
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        <tr style="background-color: #e3f2fd;">
                            <td><strong>TOTAL</strong></td>
                            <td><strong>{{ number_format($totalRealisasi, 0, ',', '.') }}</strong></td>
                            <td><strong>100%</strong></td>
                            <td><strong>-</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const chartData = @json($chartData);
        
        console.log('Data Bulanan:', chartData);

        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        const colorPalette = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40',
            '#7CFC00', '#20B2AA', '#FF6B6B', '#6A5ACD', '#FFD700', '#87CEEB'
        ];

        const labels = Object.keys(chartData);
        const dataValues = Object.values(chartData);
        const total = dataValues.reduce((sum, value) => sum + value, 0);

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
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const barCtx = document.getElementById('barChart').getContext('2d');
            createChart(barCtx, 'bar', {
                labels: labels,
                datasets: [{
                    label: 'Realisasi Bulanan',
                    data: dataValues,
                    backgroundColor: dataValues.map((value, index) => 
                        value > 0 ? colorPalette[index % colorPalette.length] : '#CCCCCC'
                    ),
                    borderColor: dataValues.map((value, index) => 
                        value > 0 ? colorPalette[index % colorPalette.length] : '#CCCCCC'
                    ),
                    borderWidth: 1
                }]
            }, {
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const percentage = total > 0 ? (value / total * 100).toFixed(2) : 0;
                                return `Realisasi: ${formatNumber(value)} (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatNumber(value);
                            }
                        },
                        grid: {
                            display: true
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
                }
            });

            const pieCtx = document.getElementById('pieChart').getContext('2d');
            createChart(pieCtx, 'pie', {
                labels: labels,
                datasets: [{
                    data: dataValues,
                    backgroundColor: dataValues.map((value, index) => 
                        value > 0 ? colorPalette[index % colorPalette.length] : '#CCCCCC'
                    ),
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            }, {
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 15,
                            padding: 15,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const percentage = context.dataset.data.reduce((a, b) => a + b, 0) > 0 ? 
                                    (value / context.dataset.data.reduce((a, b) => a + b, 0) * 100).toFixed(2) : 0;
                                return `${context.label}: ${formatNumber(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            });

            const lineCtx = document.getElementById('lineChart').getContext('2d');
            createChart(lineCtx, 'line', {
                labels: labels,
                datasets: [{
                    label: 'Trend Realisasi',
                    data: dataValues,
                    borderColor: '#36A2EB',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: dataValues.map(value => value > 0 ? '#36A2EB' : '#CCCCCC'),
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 2
                }]
            }, {
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const percentage = total > 0 ? (value / total * 100).toFixed(2) : 0;
                                return `Realisasi: ${formatNumber(value)} (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatNumber(value);
                            }
                        },
                        grid: {
                            display: true
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
                }
            });
        });
    </script>
</body>
</html>