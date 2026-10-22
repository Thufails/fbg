<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan Target dan Realisasi</title>
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
            max-width: 1600px;
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
            padding: 12px;
            text-align: right;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
            text-align: center;
        }
        
        .data-table th:first-child,
        .data-table td:first-child {
            text-align: left;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .data-table tr:hover {
            background-color: #f1f3f5;
        }
        
        .account-level-1 {
            font-weight: bold;
            background-color: #e8f4fd !important;
        }
        
        .account-level-2 {
            padding-left: 30px !important;
        }
        
        .achieve {
            background-color: #d4edda !important;
        }
        
        .not-achieve {
            background-color: #f8d7da !important;
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
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            background: #f8f9fa;
            transition: background 0.3s;
        }
        
        .tab.active {
            background: #667eea;
            color: white;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
            
            .data-table {
                font-size: 0.9rem;
            }
            
            .data-table th, .data-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Laporan Keuangan Target dan Realisasi</h1>
            <p class="description">Perbandingan Target dan Realisasi per Akun</p>
        </header>
        
        @if($usingSampleData)
        <div class="warning">
            ⚠️ Data tidak ditemukan di database. Menampilkan data contoh untuk demonstrasi.
        </div>
        @endif
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-title">Total Target</div>
                <div class="stat-value">Rp {{ App\Http\Controllers\laporan_bulanan\laporanBulananController::formatInJutaan($totalTarget) }} Jt</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Realisasi</div>
                <div class="stat-value">Rp {{ App\Http\Controllers\laporan_bulanan\laporanBulananController::formatInJutaan($totalRealisasi) }} Jt</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Selisih</div>
                <div class="stat-value {{ $totalGap >= 0 ? 'positive' : 'negative' }}">
                    Rp {{ App\Http\Controllers\laporan_bulanan\laporanBulananController::formatInJutaan($totalGap) }} Jt
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Akun Achieve</div>
                <div class="stat-value">{{ $achieveCount }} dari {{ count($mainAccounts) }}</div>
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="switchTab('charts')">Grafik</div>
            <div class="tab" onclick="switchTab('table')">Tabel Data</div>
        </div>
        
        <div id="charts-tab" class="tab-content active">
            <div class="chart-row">
                <div class="chart-container">
                    <div class="chart-title">📈 Perbandingan Target dan Realisasi (dalam Jutaan)</div>
                    <div class="chart-wrapper">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-container">
                    <div class="chart-title">📊 Status Pencapaian Akun</div>
                    <div class="chart-wrapper">
                        <canvas id="statusPieChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="chart-container">
                <div class="chart-title">📉 Selisih (Realisasi - Target) per Akun (dalam Jutaan)</div>
                <div class="chart-wrapper">
                    <canvas id="gapChart"></canvas>
                </div>
            </div>
        </div>
        
        <div id="table-tab" class="tab-content">
            <div class="chart-container">
                <div class="chart-title">📋 Data Detail per Akun</div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Akun</th>
                                <th>Target (Rp)</th>
                                <th>Realisasi (Rp)</th>
                                <th>Selisih (Rp)</th>
                                <th>Persentase</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($financialData as $account)
                            <tr class="{{ $account['keterangan'] === 'ACHIEVE' ? 'achieve' : 'not-achieve' }} account-level-{{ $account['level'] }}">
                                <td>{!! $account['nama_akun'] !!}</td>
                                <td>Rp {{ App\Http\Controllers\laporan_bulanan\laporanBulananController::formatFullNumber($account['target']) }}</td>
                                <td>Rp {{ App\Http\Controllers\laporan_bulanan\laporanBulananController::formatFullNumber($account['realisasi']) }}</td>
                                <td class="{{ $account['gap'] >= 0 ? 'positive' : 'negative' }}">
                                    Rp {{ App\Http\Controllers\laporan_bulanan\laporanBulananController::formatFullNumber($account['gap']) }}
                                </td>
                                <td class="{{ $account['percentage'] >= 100 ? 'positive' : 'negative' }}">
                                    {{ number_format($account['percentage'], 2, ',', '.') }}%
                                </td>
                                <td>{{ $account['keterangan'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <footer>
            <p>© 2023 Laporan Keuangan | Dibuat dengan Chart.js</p>
        </footer>
    </div>

    <script>
        // Data dari PHP (dikonversi ke JavaScript)
        const financialData = @json($financialData);
        
        // Filter hanya akun level 1 (induk)
        const mainAccounts = financialData.filter(account => account.level === 1);
        
        // Fungsi untuk format angka
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }
        
        // Siapkan data untuk chart (dalam jutaan untuk skala yang lebih baik)
        const labels = mainAccounts.map(account => {
            // Hapus tag strong dari nama akun
            return account.nama_akun.replace(/<strong>|<\/strong>/g, '');
        });
        
        const targetData = mainAccounts.map(account => parseFloat(account.target) / 1000000);
        const realisasiData = mainAccounts.map(account => parseFloat(account.realisasi) / 1000000);
        
        // Hitung persentase pencapaian
        const percentageData = mainAccounts.map(account => parseFloat(account.percentage));
        
        // Hitung selisih (dalam jutaan)
        const gapData = mainAccounts.map(account => parseFloat(account.gap) / 1000000);
        
        // Hitung statistik status
        const statusCount = {
            ACHIEVE: mainAccounts.filter(account => account.keterangan === 'ACHIEVE').length,
            TIDAK_ACHIEVE: mainAccounts.filter(account => account.keterangan === 'TIDAK ACHIEVE').length
        };

        // 1. Grafik Perbandingan Target vs Realisasi
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
        
        // 2. Grafik Pie Status Pencapaian
        const statusPieCtx = document.getElementById('statusPieChart').getContext('2d');
        new Chart(statusPieCtx, {
            type: 'pie',
            data: {
                labels: ['ACHIEVE', 'TIDAK ACHIEVE'],
                datasets: [{
                    data: [statusCount.ACHIEVE, statusCount.TIDAK_ACHIEVE],
                    backgroundColor: ['#4BC0C0', '#FF6384'],
                    hoverBackgroundColor: ['#36A2A2', '#E04A72']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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
            }
        });

        // 3. Grafik Selisih (Gap)
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
        
        // Fungsi untuk beralih tab
        function switchTab(tabName) {
            // Sembunyikan semua tab content
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Tampilkan tab yang dipilih
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Update tab aktif
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelector(`.tab:nth-child(${tabName === 'charts' ? 1 : 2})`).classList.add('active');
        }
    </script>
</body>
</html>