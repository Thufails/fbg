<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Target dan Realisasi Bulanan - Laporan {{ $kd_laporan }}</title>
    
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
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-title {
            font-size: 1rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .positive {
            color: #27ae60;
        }
        
        .negative {
            color: #e74c3c;
        }
        
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .chart-title {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.4em;
            font-weight: 600;
        }
        
        .chart-wrapper {
            position: relative;
            height: 400px;
            width: 100%;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .data-table th, .data-table td {
            padding: 15px;
            text-align: center;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .data-table tr:hover {
            background-color: #f1f3f5;
        }
        
        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #666;
            font-size: 0.9rem;
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
        }
        
        @media (max-width: 768px) {
            .chart-wrapper {
                height: 300px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Target dan Realisasi Bulanan</h1>
            <p class="description">Perbandingan Target dan Realisasi per Bulan - Laporan {{ $kd_laporan }}</p>
        </header>
        
        @if($usingSampleData)
        <div class="warning">
            ⚠️ Data tidak ditemukan di database. Menampilkan data contoh untuk demonstrasi.
        </div>
        @endif
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-title">Total Target</div>
                <div class="stat-value">Rp {{ number_format($totalTarget, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Realisasi</div>
                <div class="stat-value">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Selisih</div>
                <div class="stat-value {{ $totalGap >= 0 ? 'positive' : 'negative' }}">
                    Rp {{ number_format($totalGap, 0, ',', '.') }}
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Rata-rata Pencapaian</div>
                <div class="stat-value {{ $totalPercentage >= 100 ? 'positive' : 'negative' }}">
                    {{ number_format($totalPercentage, 2, ',', '.') }}%
                </div>
            </div>
        </div>

        <!-- Baris Chart Pertama: Grafik Batang dan Grafik Garis -->
        <div class="chart-row">
            <div class="chart-container">
                <div class="chart-title">📊 Perbandingan Target dan Realisasi per Bulan (dalam Jutaan)</div>
                <div class="chart-wrapper">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <div class="chart-title">📈 Tren Target dan Realisasi per Bulan (Garis)</div>
                <div class="chart-wrapper">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Baris Chart Kedua: Persentase dan Selisih -->
        <div class="chart-row">
            <div class="chart-container">
                <div class="chart-title">📊 Persentase Pencapaian per Bulan</div>
                <div class="chart-wrapper">
                    <canvas id="percentageChart"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <div class="chart-title">📉 Selisih (Realisasi - Target) per Bulan (dalam Jutaan)</div>
                <div class="chart-wrapper">
                    <canvas id="gapChart"></canvas>
                </div>
            </div>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Target (Rp)</th>
                    <th>Realisasi (Rp)</th>
                    <th>Selisih (Rp)</th>
                    <th>Persentase Pencapaian</th>
                </tr>
            </thead>
            <tbody>
                @foreach($chartData as $bulan => $data)
                @php
                    $target = (float)$data['target'];
                    $realisasi = (float)$data['realisasi'];
                    $selisih = $realisasi - $target;
                    $persentase = $target > 0 ? ($realisasi / $target) * 100 : 0;
                @endphp
                <tr>
                    <td><strong>{{ $bulan }}</strong></td>
                    <td>Rp {{ number_format($target, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($realisasi, 0, ',', '.') }}</td>
                    <td class="{{ $selisih >= 0 ? 'positive' : 'negative' }}">
                        Rp {{ number_format($selisih, 0, ',', '.') }}
                    </td>
                    <td class="{{ $persentase >= 100 ? 'positive' : 'negative' }}">
                        {{ number_format($persentase, 2, ',', '.') }}%
                    </td>
                </tr>
                @endforeach
                <tr style="background-color: #e3f2fd;">
                    <td><strong>TOTAL</strong></td>
                    <td><strong>Rp {{ number_format($totalTarget, 0, ',', '.') }}</strong></td>
                    <td><strong>Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</strong></td>
                    <td><strong class="{{ $totalGap >= 0 ? 'positive' : 'negative' }}">
                        Rp {{ number_format($totalGap, 0, ',', '.') }}
                    </strong></td>
                    <td><strong class="{{ $totalPercentage >= 100 ? 'positive' : 'negative' }}">
                        {{ number_format($totalPercentage, 2, ',', '.') }}%
                    </strong></td>
                </tr>
            </tbody>
        </table>
        
        <footer>
            <p>© {{ date('Y') }} Dashboard Target dan Realisasi | Dibuat dengan Chart.js</p>
        </footer>
    </div>

    <script>
        // Data dari PHP
        const chartData = @json($chartData);
        
        // Fungsi untuk format angka
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }
        
        // Siapkan data untuk chart (dalam jutaan untuk skala yang lebih baik)
        const labels = Object.keys(chartData);
        const targetData = Object.values(chartData).map(item => parseFloat(item.target) / 1000000);
        const realisasiData = Object.values(chartData).map(item => parseFloat(item.realisasi) / 1000000);
        
        // Hitung persentase pencapaian
        const percentageData = Object.values(chartData).map(item => {
            return ((parseFloat(item.realisasi) / parseFloat(item.target)) * 100).toFixed(2);
        });

        // Hitung selisih (dalam jutaan)
        const gapData = Object.values(chartData).map(item => {
            return (parseFloat(item.realisasi) - parseFloat(item.target)) / 1000000;
        });

        // 1. Grafik Perbandingan Target vs Realisasi (Batang)
        const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
        new Chart(comparisonCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Target (Juta Rp)',
                        data: targetData,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Realisasi (Juta Rp)',
                        data: realisasiData,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + formatNumber(value) + ' Jt';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label = label.replace('(Juta Rp)', '');
                                    label += ': ';
                                }
                                label += 'Rp ' + formatNumber(context.raw) + ' Juta';
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // 2. Grafik Garis untuk Tren Target dan Realisasi
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Target (Juta Rp)',
                        data: targetData,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4, // Memberikan kurva yang lebih smooth
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                        pointBorderColor: '#fff',
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Realisasi (Juta Rp)',
                        data: realisasiData,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                        pointBorderColor: '#fff',
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + formatNumber(value) + ' Jt';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                label = label.replace('(Juta Rp)', '');
                                label += ': Rp ' + formatNumber(context.raw) + ' Juta';
                                return label;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                }
            }
        });
        
        // 3. Grafik Persentase
        const percentageCtx = document.getElementById('percentageChart').getContext('2d');
        new Chart(percentageCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Persentase Pencapaian (%)',
                    data: percentageData,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: Math.min(...percentageData) - 1,
                        max: Math.max(...percentageData) + 1,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + '%';
                            }
                        }
                    }
                }
            }
        });

        // 4. Grafik Selisih (Gap)
        const gapCtx = document.getElementById('gapChart').getContext('2d');
        new Chart(gapCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Selisih (Realisasi - Target) dalam Juta Rp',
                    data: gapData,
                    backgroundColor: gapData.map(value => value >= 0 ? 'rgba(255, 99, 132, 0.7)' : 'rgba(75, 192, 192, 0.7)'),
                    borderColor: gapData.map(value => value >= 0 ? 'rgba(255, 99, 132, 1)' : 'rgba(75, 192, 192, 1)'),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + formatNumber(value) + ' Jt';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                label = label.replace('dalam Juta Rp', '');
                                label += ': Rp ' + formatNumber(context.raw) + ' Juta';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>