<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beban Gaji Bulanan (Target vs Realisasi) - Laporan {{ $kd_laporan }}</title>
    
    <!-- Chart.js -->
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
        <h1 style="text-align: center; color: #2c3e50; margin-bottom: 30px; font-size: 1.8em;">
            📊 Beban Gaji Bulanan (Target vs Realisasi)
        </h1>

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
                    <div class="stat-label">Total Target Gaji</div>
                    <div class="stat-value">{{ number_format($totalTarget, 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Realisasi Gaji</div>
                    <div class="stat-value">{{ number_format($totalRealisasi, 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Selisih</div>
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
                <div class="stat-card">
                    <div class="stat-label">Jumlah Bulan</div>
                    <div class="stat-value">{{ count($chartData) }}</div>
                </div>
            </div>
        </div>

        <!-- Baris Chart: Perbandingan Bulanan -->
        <div class="chart-row">
            <!-- Grafik Batang Perbandingan -->
            <div class="chart-container">
                <div class="chart-title">📊 Target vs Realisasi Gaji per Bulan</div>
                <div class="chart-wrapper">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>

            <!-- Grafik Line Trend -->
            <div class="chart-container">
                <div class="chart-title">📈 Trend Gaji per Bulan</div>
                <div class="chart-wrapper">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Baris Chart: Selisih dan Persentase -->
        <div class="chart-row">
            <!-- Grafik Selisih Bulanan -->
            <div class="chart-container">
                <div class="chart-title">📉 Selisih (Realisasi - Target) per Bulan</div>
                <div class="chart-wrapper">
                    <canvas id="gapChart"></canvas>
                </div>
            </div>

            <!-- Grafik Persentase Pencapaian -->
            <div class="chart-container">
                <div class="chart-title">📊 Persentase Pencapaian per Bulan</div>
                <div class="chart-wrapper">
                    <canvas id="percentageChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Kartu Bulanan -->
        <div class="stats-container">
            <div class="chart-title">📅 Detail Beban Gaji per Bulan</div>
            <div class="chart-row">
                @foreach($chartData as $bulan => $data)
                @php
                    $target = (float)$data['target'];
                    $realisasi = (float)$data['realisasi'];
                    $gap = $realisasi - $target;
                    $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                    $status = $percentage >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
                @endphp
                <div class="month-card">
                    <h3 style="margin: 0 0 15px 0; color: #2c3e50;">Bulan {{ $bulan }}</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <strong>Target Gaji:</strong><br>
                            {{ number_format($target, 0, ',', '.') }}
                        </div>
                        <div>
                            <strong>Realisasi Gaji:</strong><br>
                            {{ number_format($realisasi, 0, ',', '.') }}
                        </div>
                        <div>
                            <strong>Selisih:</strong><br>
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
                    <div style="margin-top: 15px; padding: 8px; background: {{ $status === 'ACHIEVE' ? '#d4edda' : '#f8d7da' }}; border-radius: 5px; text-align: center;">
                        <strong style="color: {{ $status === 'ACHIEVE' ? '#155724' : '#721c24' }};">
                            {{ $status }}
                        </strong>
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
                            <th>Target Gaji</th>
                            <th>Realisasi Gaji</th>
                            <th>Selisih</th>
                            <th>Persentase</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chartData as $bulan => $data)
                        @php
                            $target = (float)$data['target'];
                            $realisasi = (float)$data['realisasi'];
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
                                <span style="padding: 4px 8px; border-radius: 4px; background: {{ $status === 'ACHIEVE' ? '#d4edda' : '#f8d7da' }}; color: {{ $status === 'ACHIEVE' ? '#155724' : '#721c24' }};">
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
        // Data dari PHP
        const chartData = @json($chartData);
        const gapData = @json($gapData);
        const percentageData = @json($percentageData);
        
        console.log('Data Gaji Bulanan:', chartData);

        // Fungsi untuk format angka
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        // Siapkan data untuk chart
        const labels = Object.keys(chartData);
        const targetData = Object.values(chartData).map(item => parseFloat(item.target));
        const realisasiData = Object.values(chartData).map(item => parseFloat(item.realisasi));
        const gapValues = Object.values(gapData);
        const percentageValues = Object.values(percentageData);

        // Warna untuk chart
        const colorPalette = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];

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
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Grafik Perbandingan Target vs Realisasi
            const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
            createChart(comparisonCtx, 'bar', {
                labels: labels,
                datasets: [
                    {
                        label: 'Target Gaji',
                        data: targetData,
                        backgroundColor: '#FF6384',
                        borderColor: '#FF6384',
                        borderWidth: 1
                    },
                    {
                        label: 'Realisasi Gaji',
                        data: realisasiData,
                        backgroundColor: '#36A2EB',
                        borderColor: '#36A2EB',
                        borderWidth: 1
                    }
                ]
            }, {
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
            });

            // Grafik Trend Line
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            createChart(trendCtx, 'line', {
                labels: labels,
                datasets: [
                    {
                        label: 'Target Gaji',
                        data: targetData,
                        borderColor: '#FF6384',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#FF6384',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        borderWidth: 2
                    },
                    {
                        label: 'Realisasi Gaji',
                        data: realisasiData,
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#36A2EB',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        borderWidth: 2
                    }
                ]
            }, {
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
            });

            // Grafik Selisih
            const gapCtx = document.getElementById('gapChart').getContext('2d');
            createChart(gapCtx, 'bar', {
                labels: labels,
                datasets: [{
                    label: 'Selisih (Realisasi - Target)',
                    data: gapValues,
                    backgroundColor: gapValues.map(gap => gap >= 0 ? '#28a745' : '#dc3545'),
                    borderColor: gapValues.map(gap => gap >= 0 ? '#28a745' : '#dc3545'),
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
                                return `Selisih: ${formatNumber(context.raw)}`;
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
            });

            // Grafik Persentase Pencapaian
            const percentageCtx = document.getElementById('percentageChart').getContext('2d');
            createChart(percentageCtx, 'bar', {
                labels: labels,
                datasets: [{
                    label: 'Persentase Pencapaian (%)',
                    data: percentageValues,
                    backgroundColor: percentageValues.map(percent => percent >= 100 ? '#28a745' : '#dc3545'),
                    borderColor: percentageValues.map(percent => percent >= 100 ? '#28a745' : '#dc3545'),
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
            });
        });
    </script>
</body>
</html>