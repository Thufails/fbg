<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbandingan Target & Realisasi Antar Entitas - Laporan {{ $kd_laporan }}</title>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: #333;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        
        .description {
            font-size: 1.1rem;
            opacity: 0.9;
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
            height: 500px;
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
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
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
        
        .achieve {
            background-color: #d4edda !important;
            color: #155724;
        }
        
        .not-achieve {
            background-color: #f8d7da !important;
            color: #721c24;
        }
        
        .positive {
            color: #28a745;
        }
        
        .negative {
            color: #dc3545;
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
        
        .entity-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #4BC0C0;
        }
        
        .entity-card.achieve {
            border-left-color: #28a745;
        }
        
        .entity-card.not-achieve {
            border-left-color: #dc3545;
        }
        
        @media (max-width: 1200px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 450px;
            }
            
            .chart-wrapper {
                height: 350px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 12px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .chart-container {
                height: 400px;
                padding: 15px;
            }
            
            .chart-wrapper {
                height: 320px;
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
        <header>
            <h1>📊 Perbandingan Target & Realisasi Antar Entitas</h1>
            <p class="description">Visualisasi Performa Entitas untuk Laporan {{ $kd_laporan }}</p>
        </header>
        
        @if($usingSampleData)
        <div class="warning">
            ⚠️ Data tidak ditemukan di database. Menampilkan data contoh untuk demonstrasi.
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
                    <div class="stat-label">Pencapaian</div>
                    <div class="stat-value">{{ number_format($totalPercentage, 2, ',', '.') }}%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Entitas Achieve</div>
                    <div class="stat-value">{{ $achieveCount }} dari {{ count($chartData) }}</div>
                </div>
            </div>
        </div>

        <!-- Baris Chart: Perbandingan Target vs Realisasi -->
        <div class="chart-row">
            <!-- Grafik Batang Perbandingan -->
            <div class="chart-container">
                <div class="chart-title">📊 Target vs Realisasi per Entitas</div>
                <div class="chart-wrapper">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>

            <!-- Grafik Pie Pencapaian -->
            <div class="chart-container">
                <div class="chart-title">🥧 Status Pencapaian Entitas</div>
                <div class="chart-wrapper">
                    <canvas id="statusPieChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Grafik Persentase Pencapaian -->
        <div class="chart-container">
            <div class="chart-title">📈 Persentase Pencapaian per Entitas</div>
            <div class="chart-wrapper">
                <canvas id="percentageChart"></canvas>
            </div>
        </div>

        <!-- Grafik Gap per Entitas -->
        <div class="chart-container">
            <div class="chart-title">📊 Gap (Realisasi - Target) per Entitas</div>
            <div class="chart-wrapper">
                <canvas id="gapChart"></canvas>
            </div>
        </div>

        <!-- Kartu Entitas -->
        <div class="stats-container">
            <div class="chart-title">🏢 Detail Performa Entitas</div>
            <div class="chart-row">
                @foreach($chartData as $entitas => $data)
                <div class="entity-card {{ $data['keterangan'] === 'ACHIEVE' ? 'achieve' : 'not-achieve' }}">
                    <h3 style="margin: 0 0 15px 0; color: #2c3e50;">{{ $entitas }}</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <strong>Target:</strong><br>
                            {{ number_format($data['target'], 0, ',', '.') }}
                        </div>
                        <div>
                            <strong>Realisasi:</strong><br>
                            {{ number_format($data['realisasi'], 0, ',', '.') }}
                        </div>
                        <div>
                            <strong>Gap:</strong><br>
                            <span style="color: {{ $data['gap'] >= 0 ? '#28a745' : '#dc3545' }};">
                                {{ number_format($data['gap'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <strong>Pencapaian:</strong><br>
                            <span style="color: {{ $data['percentage'] >= 100 ? '#28a745' : '#dc3545' }};">
                                {{ number_format($data['percentage'], 2, ',', '.') }}%
                            </span>
                        </div>
                    </div>
                    <div style="margin-top: 15px; padding: 8px; background: {{ $data['keterangan'] === 'ACHIEVE' ? '#d4edda' : '#f8d7da' }}; border-radius: 5px; text-align: center;">
                        <strong>{{ $data['keterangan'] }}</strong>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Tabel Data Detail -->
        <div class="stats-container">
            <div class="chart-title">📋 Data Detail Entitas</div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Entitas</th>
                            <th>Target</th>
                            <th>Realisasi</th>
                            <th>Gap</th>
                            <th>Persentase</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chartData as $entitas => $data)
                        <tr class="{{ $data['keterangan'] === 'ACHIEVE' ? 'achieve' : 'not-achieve' }}">
                            <td><strong>{{ $entitas }}</strong></td>
                            <td>{{ number_format($data['target'], 0, ',', '.') }}</td>
                            <td>{{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                            <td style="color: {{ $data['gap'] >= 0 ? '#28a745' : '#dc3545' }};">
                                {{ number_format($data['gap'], 0, ',', '.') }}
                            </td>
                            <td style="color: {{ $data['percentage'] >= 100 ? '#28a745' : '#dc3545' }};">
                                {{ number_format($data['percentage'], 2, ',', '.') }}%
                            </td>
                            <td>{{ $data['keterangan'] }}</td>
                        </tr>
                        @endforeach
                        <tr style="background-color: #e3f2fd;">
                            <td><strong>TOTAL</strong></td>
                            <td><strong>{{ number_format($totalTarget, 0, ',', '.') }}</strong></td>
                            <td><strong>{{ number_format($totalRealisasi, 0, ',', '.') }}</strong></td>
                            <td><strong style="color: {{ $totalGap >= 0 ? '#28a745' : '#dc3545' }};">
                                {{ number_format($totalGap, 0, ',', '.') }}
                            </strong></td>
                            <td><strong style="color: {{ $totalPercentage >= 100 ? '#28a745' : '#dc3545' }};">
                                {{ number_format($totalPercentage, 2, ',', '.') }}%
                            </strong></td>
                            <td><strong>-</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Data dari PHP (Laravel)
        const chartData = @json($chartData);
        
        console.log('Data Entitas:', chartData);

        // Fungsi untuk format angka
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        // Siapkan data untuk chart
        const labels = Object.keys(chartData);
        const targetData = Object.values(chartData).map(item => item.target);
        const realisasiData = Object.values(chartData).map(item => item.realisasi);
        const gapData = Object.values(chartData).map(item => item.gap);
        const percentageData = Object.values(chartData).map(item => item.percentage);
        const statusData = Object.values(chartData).map(item => item.keterangan);

        // Hitung statistik status
        const statusCount = {
            ACHIEVE: statusData.filter(status => status === 'ACHIEVE').length,
            TIDAK_ACHIEVE: statusData.filter(status => status === 'TIDAK ACHIEVE').length
        };

        document.addEventListener('DOMContentLoaded', () => {
            // Grafik Perbandingan Target vs Realisasi
            const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
            new Chart(comparisonCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Target',
                            data: targetData,
                            backgroundColor: '#FF6384',
                            borderColor: '#FF6384',
                            borderWidth: 1
                        },
                        {
                            label: 'Realisasi',
                            data: realisasiData,
                            backgroundColor: '#36A2EB',
                            borderColor: '#36A2EB',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${formatNumber(context.raw)}`;
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
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });

            // Grafik Pie Status
            const pieCtx = document.getElementById('statusPieChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: ['ACHIEVE', 'TIDAK ACHIEVE'],
                    datasets: [{
                        data: [statusCount.ACHIEVE, statusCount.TIDAK_ACHIEVE],
                        backgroundColor: ['#28a745', '#dc3545'],
                        borderColor: ['#fff', '#fff'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.raw / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.raw} entitas (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Grafik Persentase Pencapaian
            const percentageCtx = document.getElementById('percentageChart').getContext('2d');
            new Chart(percentageCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Persentase Pencapaian (%)',
                        data: percentageData,
                        backgroundColor: percentageData.map(percent => percent >= 100 ? '#28a745' : '#dc3545'),
                        borderColor: percentageData.map(percent => percent >= 100 ? '#28a745' : '#dc3545'),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Pencapaian: ${context.raw.toFixed(2)}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });

            // Grafik Gap
            const gapCtx = document.getElementById('gapChart').getContext('2d');
            new Chart(gapCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Gap (Realisasi - Target)',
                        data: gapData,
                        backgroundColor: gapData.map(gap => gap >= 0 ? '#28a745' : '#dc3545'),
                        borderColor: gapData.map(gap => gap >= 0 ? '#28a745' : '#dc3545'),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Gap: ${formatNumber(context.raw)}`;
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
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>