<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbandingan Target & Realisasi HPP - Laporan {{ $kd_laporan }}</title>
    
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
        
        .month-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #4BC0C0;
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
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .chart-container {
                height: 400px;
                padding: 15px;
            }
            
            .chart-wrapper {
                height: 320px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Perbandingan Target & Realisasi HPP</h1>
            <p class="description">Visualisasi Harga Pokok Produksi untuk Laporan {{ $kd_laporan }}</p>
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
                    <div class="stat-value {{ $totalGap >= 0 ? 'positive' : 'negative' }}">
                        {{ number_format($totalGap, 0, ',', '.') }}
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pencapaian</div>
                    <div class="stat-value {{ $totalPercentage >= 100 ? 'positive' : 'negative' }}">
                        {{ number_format($totalPercentage, 2, ',', '.') }}%
                    </div>
                </div>
            </div>
        </div>

        <!-- Baris Chart: Perbandingan Bulanan -->
        <div class="chart-row">
            <!-- Grafik Batang Perbandingan -->
            <div class="chart-container">
                <div class="chart-title">📊 Target vs Realisasi HPP per Bulan</div>
                <div class="chart-wrapper">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>

            <!-- Grafik Gap Bulanan -->
            <div class="chart-container">
                <div class="chart-title">📈 Gap (Realisasi - Target) per Bulan</div>
                <div class="chart-wrapper">
                    <canvas id="gapChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Grafik Persentase Pencapaian -->
        <div class="chart-container">
            <div class="chart-title">📊 Persentase Pencapaian per Bulan</div>
            <div class="chart-wrapper">
                <canvas id="percentageChart"></canvas>
            </div>
        </div>

        <!-- Kartu Bulanan -->
        <div class="stats-container">
            <div class="chart-title">📅 Detail Performa per Bulan</div>
            <div class="chart-row">
                @foreach($chartData as $bulan => $data)
                    @php
                        $target = (float)($data['target'] ?? 0);
                        $realisasi = (float)($data['realisasi'] ?? 0);
                        $gap = $realisasi - $target;
                        $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                    @endphp
                <div class="month-card">
                    <h3 style="margin: 0 0 15px 0; color: #2c3e50;">Bulan {{ $bulan }}</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <strong>Target:</strong><br>
                            {{ number_format($target, 0, ',', '.') }}
                        </div>
                        <div>
                            <strong>Realisasi:</strong><br>
                            {{ number_format($realisasi, 0, ',', '.') }}
                        </div>
                        <div>
                            <strong>Gap:</strong><br>
                            <span class="{{ $gap >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($gap, 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <strong>Pencapaian:</strong><br>
                            <span class="{{ $percentage >= 100 ? 'positive' : 'negative' }}">
                                {{ number_format($percentage, 2, ',', '.') }}%
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Tabel Data Detail -->
        <div class="stats-container">
            <div class="chart-title">📋 Data Detail per Bulan</div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th>Target</th>
                            <th>Realisasi</th>
                            <th>Gap</th>
                            <th>Persentase</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chartData as $bulan => $data)
                            @php
                                $target = (float)($data['target'] ?? 0);
                                $realisasi = (float)($data['realisasi'] ?? 0);
                                $gap = $realisasi - $target;
                                $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                                $status = $percentage >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
                            @endphp
                        <tr>
                            <td><strong>{{ $bulan }}</strong></td>
                            <td>{{ number_format($target, 0, ',', '.') }}</td>
                            <td>{{ number_format($realisasi, 0, ',', '.') }}</td>
                            <td class="{{ $gap >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($gap, 0, ',', '.') }}
                            </td>
                            <td class="{{ $percentage >= 100 ? 'positive' : 'negative' }}">
                                {{ number_format($percentage, 2, ',', '.') }}%
                            </td>
                            <td>
                                <span style="padding: 4px 8px; border-radius: 4px; background: {{ $percentage >= 100 ? '#d4edda' : '#f8d7da' }}; color: {{ $percentage >= 100 ? '#155724' : '#721c24' }};">
                                    {{ $status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                        <tr style="background-color: #e3f2fd;">
                            <td><strong>TOTAL</strong></td>
                            <td><strong>{{ number_format($totalTarget, 0, ',', '.') }}</strong></td>
                            <td><strong>{{ number_format($totalRealisasi, 0, ',', '.') }}</strong></td>
                            <td><strong class="{{ $totalGap >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalGap, 0, ',', '.') }}
                            </strong></td>
                            <td><strong class="{{ $totalPercentage >= 100 ? 'positive' : 'negative' }}">
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
        
        console.log('Data HPP:', chartData);

        // Fungsi untuk format angka
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        // Siapkan data untuk chart
        const labels = Object.keys(chartData);
        const targetData = Object.values(chartData).map(item => parseFloat(item.target || 0));
        const realisasiData = Object.values(chartData).map(item => parseFloat(item.realisasi || 0));
        
        // Hitung gap dan percentage
        const gapData = targetData.map((target, index) => realisasiData[index] - target);
        const percentageData = targetData.map((target, index) => 
            target > 0 ? (realisasiData[index] / target) * 100 : 0
        );

        document.addEventListener('DOMContentLoaded', () => {
            // Grafik Perbandingan Target vs Realisasi
            const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
            new Chart(comparisonCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Target HPP',
                            data: targetData,
                            backgroundColor: '#FF6384',
                            borderColor: '#FF6384',
                            borderWidth: 1
                        },
                        {
                            label: 'Realisasi HPP',
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
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>