<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pendapatan Harian</title>
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
            max-width: 1200px;
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
        
        .table-scroll {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-top: 30px;
        }

        .data-table {
            min-width: 760px;
            width: 100%;
            border-collapse: collapse;
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
            <h1>📊 Laporan Pendapatan Harian</h1>
            <p class="description">Perbandingan Target dan Realisasi Pendapatan Harian</p>
        </header>
        
        @if(isset($error))
        <div class="warning">
            ⚠️ {{ $error }}
        </div>
        @endif
        
        @if($usingSampleData ?? false)
        <div class="warning">
            ⚠️ Data tidak ditemukan di database. Menampilkan data contoh untuk demonstrasi.
        </div>
        @endif
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-title">Total Target</div>
                <div class="stat-value">Rp {{ number_format($totalData['target'] / 1000000, 2, ',', '.') }} Juta</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Realisasi</div>
                <div class="stat-value">Rp {{ number_format($totalData['realisasi'] / 1000000, 2, ',', '.') }} Juta</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Selisih</div>
                <div class="stat-value {{ $totalGap >= 0 ? 'positive' : 'negative' }}">
                    Rp {{ number_format($totalGap / 1000000, 2, ',', '.') }} Juta
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Rata-rata Pencapaian</div>
                <div class="stat-value {{ $totalPercentage >= 100 ? 'positive' : 'negative' }}">
                    {{ number_format($totalPercentage, 2, ',', '.') }}%
                </div>
            </div>
        </div>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-title">Hari Achieve</div>
                <div class="stat-value positive">{{ $totalAchieve }} Hari</div>
                <div>{{ number_format($achievementRate, 1, ',', '.') }}% dari total hari</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Hari Tidak Achieve</div>
                <div class="stat-value negative">{{ $totalNotAchieve }} Hari</div>
                <div>{{ number_format(100 - $achievementRate, 1, ',', '.') }}% dari total hari</div>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-title">📈 Perbandingan Target dan Realisasi Harian (Bar Chart)</div>
            <div class="chart-wrapper">
                <canvas id="comparisonChart"></canvas>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-title">📈 Tren Target dan Realisasi Harian (Line Chart)</div>
            <div class="chart-wrapper">
                <canvas id="lineChart"></canvas>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-title">📊 Persentase Pencapaian Harian</div>
            <div class="chart-wrapper">
                <canvas id="percentageChart"></canvas>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-title">📋 Status Pencapaian Harian</div>
            <div class="chart-wrapper">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        
        <div class="table-scroll" role="region" aria-label="Tabel laporan harian" tabindex="0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Target (Rp Juta)</th>
                    <th>Realisasi (Rp Juta)</th>
                    <th>Selisih (Rp Juta)</th>
                    <th>Persentase Pencapaian</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="table-body">
                @foreach($data as $item)
                    @if(isset($item['is_total']) && $item['is_total'] === true)
                        @continue
                    @endif
                    
                    @php
                        $target = (float)$item['target'] / 1000000;
                        $realisasi = (float)$item['realisasi'] / 1000000;
                        $selisih = $realisasi - $target;
                        $persentase = $item['percentage_vs_target'];
                    @endphp
                    <tr>
                        <td><strong>{!! $item['nama_akun'] !!}</strong></td>
                        <td>Rp {{ number_format($target, 2, ',', '.') }}</td>
                        <td>Rp {{ number_format($realisasi, 2, ',', '.') }}</td>
                        <td class="{{ $selisih >= 0 ? 'positive' : 'negative' }}">
                            Rp {{ number_format($selisih, 2, ',', '.') }}
                        </td>
                        <td class="{{ $persentase >= 100 ? 'positive' : 'negative' }}">
                            {{ number_format($persentase, 2, ',', '.') }}%
                        </td>
                        <td class="{{ $item['keterangan'] == 'ACHIEVE' ? 'positive' : 'negative' }}">
                            <strong>{{ $item['keterangan'] }}</strong>
                        </td>
                    </tr>
                @endforeach
                <tr style="background-color: #e3f2fd;">
                    <td><strong>TOTAL</strong></td>
                    <td><strong>Rp {{ number_format($totalData['target'] / 1000000, 2, ',', '.') }}</strong></td>
                    <td><strong>Rp {{ number_format($totalData['realisasi'] / 1000000, 2, ',', '.') }}</strong></td>
                    <td><strong class="{{ $totalGap >= 0 ? 'positive' : 'negative' }}">
                        Rp {{ number_format($totalGap / 1000000, 2, ',', '.') }}
                    </strong></td>
                    <td><strong class="{{ $totalPercentage >= 100 ? 'positive' : 'negative' }}">
                        {{ number_format($totalPercentage, 2, ',', '.') }}%
                    </strong></td>
                    <td><strong>{{ $totalData['keterangan'] }}</strong></td>
                </tr>
            </tbody>
        </table>
        </div>
        
        <footer>
            <p>© {{ date('Y') }} Dashboard Laporan Pendapatan Harian | Dibuat dengan Chart.js</p>
        </footer>
    </div>

    <script>
        // Data dari PHP
        const labels = @json($labels);
        const targetData = @json($targetData);
        const realisasiData = @json($realisasiData);
        const percentageData = @json($percentageData);
        const statusColors = @json($statusColors);
        
        // Fungsi untuk format angka
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }
        
        // 1. Grafik Perbandingan Target vs Realisasi (Bar Chart)
        const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
        new Chart(comparisonCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Target',
                        data: targetData,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Realisasi',
                        data: realisasiData,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
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
                                return 'Rp ' + formatNumber(value);
                            }
                        },
                        title: {
                            display: true,
                            text: 'Juta Rupiah'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'Rp ' + context.raw.toLocaleString('id-ID');
                                return label;
                            }
                        }
                    }
                }
            }
        });
        
        // 2. Grafik Line untuk Target dan Realisasi
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Target',
                        data: targetData,
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Realisasi',
                        data: realisasiData,
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + formatNumber(value);
                            }
                        },
                        title: {
                            display: true,
                            text: 'Juta Rupiah'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'Rp ' + context.raw.toLocaleString('id-ID');
                                return label;
                            }
                        }
                    }
                }
            }
        });
        
        // 3. Grafik Persentase Pencapaian
        const percentageCtx = document.getElementById('percentageChart').getContext('2d');
        new Chart(percentageCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Persentase Pencapaian (%)',
                    data: percentageData,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(153, 102, 255, 1)',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        min: Math.min(...percentageData) - 2,
                        max: Math.max(...percentageData) + 2,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Persentase (%)'
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
        
        // 4. Grafik Status Pencapaian
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Persentase Pencapaian',
                    data: percentageData,
                    backgroundColor: statusColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        title: {
                            display: true,
                            text: 'Persentase (%)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
