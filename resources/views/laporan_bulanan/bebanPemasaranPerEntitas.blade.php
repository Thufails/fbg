<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beban Pemasaran per Entitas - Laporan {{ $kd_laporan }}</title>
    
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
        
        .branch-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 10px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #4BC0C0;
        }
        
        .branch-card.achieve {
            border-left-color: #28a745;
        }
        
        .branch-card.not-achieve {
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
            📊 Beban Pemasaran per Entitas
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
                    <div class="stat-label">Total Target</div>
                    <div class="stat-value">Rp {{ number_format($totalTarget, 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Realisasi</div>
                    <div class="stat-value">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Selisih</div>
                    <div class="stat-value {{ $totalGap >= 0 ? 'positive' : 'negative' }}">
                        Rp {{ number_format($totalGap, 0, ',', '.') }}
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pencapaian</div>
                    <div class="stat-value {{ $totalPercentage >= 100 ? 'positive' : 'negative' }}">
                        {{ number_format($totalPercentage, 2, ',', '.') }}%
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Entitas Achieve</div>
                    <div class="stat-value">{{ $achieveCount }} dari {{ count($chartData) }}</div>
                </div>
            </div>
        </div>

        <!-- Baris Chart: Perbandingan per Entitas -->
        <div class="chart-row">
            <!-- Grafik Batang Perbandingan -->
            <div class="chart-container">
                <div class="chart-title">📊 Target vs Realisasi Beban Pemasaran</div>
                <div class="chart-wrapper">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>

            <!-- Grafik Pie Status -->
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

        <!-- Grafik Selisih per Entitas -->
        <div class="chart-container">
            <div class="chart-title">📊 Selisih (Realisasi - Target) per Entitas</div>
            <div class="chart-wrapper">
                <canvas id="gapChart"></canvas>
            </div>
        </div>

        <!-- Kartu Entitas -->
        <div class="stats-container">
            <div class="chart-title">🏢 Detail Beban Pemasaran per Entitas</div>
            <div class="chart-row">
                @foreach($chartData as $entitas => $data)
                <div class="branch-card {{ $data['keterangan'] === 'ACHIEVE' ? 'achieve' : 'not-achieve' }}">
                    <h3 style="margin: 0 0 15px 0; color: #2c3e50;">{{ $entitas }}</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <strong>Target:</strong><br>
                            Rp {{ number_format($data['target'], 0, ',', '.') }}
                        </div>
                        <div>
                            <strong>Realisasi:</strong><br>
                            Rp {{ number_format($data['realisasi'], 0, ',', '.') }}
                        </div>
                        <div>
                            <strong>Selisih:</strong><br>
                            <span class="{{ $data['gap'] >= 0 ? 'positive' : 'negative' }}">
                                Rp {{ number_format($data['gap'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <strong>Pencapaian:</strong><br>
                            <span class="{{ $data['percentage'] >= 0 ? 'positive' : 'negative' }}">
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
            <div class="chart-title">📋 Data Detail per Entitas</div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Entitas</th>
                            <th>Target (Rp)</th>
                            <th>Realisasi (Rp)</th>
                            <th>Selisih (Rp)</th>
                            <th>Persentase</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chartData as $entitas => $data)
                        <tr class="{{ $data['keterangan'] === 'ACHIEVE' ? 'achieve' : 'not-achieve' }}">
                            <td><strong>{{ $entitas }}</strong></td>
                            <td>Rp {{ number_format($data['target'], 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                            <td class="{{ $data['gap'] >= 0 ? 'positive' : 'negative' }}">
                                Rp {{ number_format($data['gap'], 0, ',', '.') }}
                            </td>
                            <td class="{{ $data['percentage'] >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($data['percentage'], 2, ',', '.') }}%
                            </td>
                            <td>{{ $data['keterangan'] }}</td>
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
        
        console.log('Data Beban Pemasaran:', chartData);

        // Fungsi untuk format angka
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        // Siapkan data untuk chart
        const labels = Object.keys(chartData);
        const targetData = Object.values(chartData).map(item => parseFloat(item.target));
        const realisasiData = Object.values(chartData).map(item => parseFloat(item.realisasi));
        const gapData = Object.values(chartData).map(item => parseFloat(item.gap));
        const percentageData = Object.values(chartData).map(item => parseFloat(item.percentage));
        const statusData = Object.values(chartData).map(item => item.keterangan);

        // Hitung statistik status
        const statusCount = {
            ACHIEVE: statusData.filter(status => status === 'ACHIEVE').length,
            TIDAK_ACHIEVE: statusData.filter(status => status === 'TIDAK ACHIEVE').length
        };

        // Fungsi untuk membuat chart
        function createChart(ctx, type, data, customOptions) {
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'Rp ' + formatNumber(context.raw);
                                return label;
                            }
                        }
                    }
                }
            };
            
            // Gabungkan customOptions dengan defaultOptions
            const mergedOptions = {...defaultOptions, ...customOptions};
            
            return new Chart(ctx, {
                type: type,
                data: data,
                options: mergedOptions
            });
        }

        // 1. Grafik Perbandingan Target vs Realisasi
        const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
        createChart(comparisonCtx, 'bar', {
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
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        }, {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + formatNumber(value);
                        }
                    }
                }
            }
        });

        // 2. Grafik Pie Status Pencapaian
        const statusPieCtx = document.getElementById('statusPieChart').getContext('2d');
        createChart(statusPieCtx, 'pie', {
            labels: ['ACHIEVE', 'TIDAK ACHIEVE'],
            datasets: [{
                data: [statusCount.ACHIEVE, statusCount.TIDAK_ACHIEVE],
                backgroundColor: ['#4BC0C0', '#FF6384'],
                hoverBackgroundColor: ['#36A2A2', '#E04A72']
            }]
        }, {
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            return `${context.label}: ${context.raw} (${percentage}%)`;
                        }
                    }
                }
            }
        });

        // 3. Grafik Persentase Pencapaian
        const percentageCtx = document.getElementById('percentageChart').getContext('2d');
        createChart(percentageCtx, 'bar', {
            labels: labels,
            datasets: [{
                label: 'Persentase Pencapaian (%)',
                data: percentageData,
                backgroundColor: percentageData.map(p => p >= 0 ? 'rgba(75, 192, 192, 0.7)' : 'rgba(255, 99, 132, 0.7)'),
                borderColor: percentageData.map(p => p >= 0 ? 'rgba(75, 192, 192, 1)' : 'rgba(255, 99, 132, 1)'),
                borderWidth: 1
            }]
        }, {
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        });

        // 4. Grafik Selisih (Gap)
        const gapCtx = document.getElementById('gapChart').getContext('2d');
        createChart(gapCtx, 'bar', {
            labels: labels,
            datasets: [{
                label: 'Selisih (Realisasi - Target)',
                data: gapData,
                backgroundColor: gapData.map(g => g >= 0 ? 'rgba(255, 99, 132, 0.7)' : 'rgba(75, 192, 192, 0.7)'),
                borderColor: gapData.map(g => g >= 0 ? 'rgba(255, 99, 132, 1)' : 'rgba(75, 192, 192, 1)'),
                borderWidth: 1
            }]
        }, {
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + formatNumber(value);
                        }
                    }
                }
            }
        });

        // Responsive behavior
        window.addEventListener('resize', function() {
            // Charts will automatically resize due to Chart.js responsive options
        });
    </script>
</body>
</html>