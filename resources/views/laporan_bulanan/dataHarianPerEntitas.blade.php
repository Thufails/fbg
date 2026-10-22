<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Harian per Entitas</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
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

        .controls {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .control-group label {
            font-weight: bold;
            color: #2c3e50;
        }

        .control-group select, .control-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .stat-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .stat-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .stat-label {
            color: #666;
        }

        .stat-value {
            font-weight: bold;
            color: #2c3e50;
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
            height: 500px;
            width: 100%;
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
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 1200px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .chart-row-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .chart-wrapper {
                height: 400px;
            }

            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            h1 {
                font-size: 1.8rem;
            }

            .data-table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Data Harian per Entitas</h1>
            <p class="description">Visualisasi Data Harian untuk Tiga Entitas</p>
        </header>

        @if($usingSampleData)
        <div class="warning">
            ⚠️ Data tidak ditemukan di database. Menampilkan data contoh untuk demonstrasi.
        </div>
        @endif

        <div class="controls">
            <div class="control-group">
                <label for="entitySelect">Pilih Entitas:</label>
                <select id="entitySelect">
                    <option value="all">Semua Entitas</option>
                    @foreach ($entities as $entity)
                    <option value="{{ $entity }}">{{ $entity }}</option>
                    @endforeach
                </select>
            </div>

            <div class="control-group">
                <label for="dateRange">Rentang Tanggal:</label>
                <select id="dateRange">
                    <option value="all">Semua Tanggal</option>
                    <option value="jan-mar">Januari - Maret 2025</option>
                    <option value="apr-jun">April - Juni 2025</option>
                    <option value="jul-sep">Juli - September 2025</option>
                </select>
            </div>

            <div class="control-group">
                <label for="chartType">Tipe Grafik:</label>
                <select id="chartType">
                    <option value="line">Garis</option>
                    <option value="bar">Batang</option>
                </select>
            </div>
        </div>

        <div class="stats-container">
            @foreach ($entities as $entity)
            <div class="stat-card">
                <div class="stat-title">{{ $entity }}</div>
                <div class="stat-item">
                    <span class="stat-label">Total:</span>
                    <span class="stat-value">Rp {{ number_format($totalByEntity[$entity] / 1000000, 2, ',', '.') }} Jt</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Rata-rata:</span>
                    <span class="stat-value">Rp {{ number_format($avgByEntity[$entity] / 1000000, 2, ',', '.') }} Jt</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Maksimum:</span>
                    <span class="stat-value">Rp {{ number_format($maxByEntity[$entity] / 1000000, 2, ',', '.') }} Jt</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Minimum:</span>
                    <span class="stat-value">Rp {{ number_format($minByEntity[$entity] / 1000000, 2, ',', '.') }} Jt</span>
                </div>
            </div>
            @endforeach
        </div>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('charts')">Grafik</div>
            <div class="tab" onclick="switchTab('table')">Tabel Data</div>
        </div>

        <div id="charts-tab" class="tab-content active">
            <!-- Baris 1: Grafik Data Harian per Entitas -->
            <div class="chart-row">
                <div class="chart-container">
                    <div class="chart-title">📈 Data Harian per Entitas (dalam Jutaan Rupiah)</div>
                    <div class="chart-wrapper">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Baris 2: Grafik Tren Kumulatif per Entitas -->
            <div class="chart-row">
                <div class="chart-container">
                    <div class="chart-title">📈 Tren Kumulatif per Entitas</div>
                    <div class="chart-wrapper">
                        <canvas id="cumulativeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Baris 3: Perbandingan Total per Entitas & Pie Chart -->
            <div class="chart-row-2">
                <div class="chart-container">
                    <div class="chart-title">📊 Perbandingan Total per Entitas</div>
                    <div class="chart-wrapper">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <div class="chart-title">🥧 Kontribusi Realisasi per Entitas</div>
                    <div class="chart-wrapper">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div id="table-tab" class="tab-content">
            <div class="chart-container">
                <div class="chart-title">📋 Data Harian per Entitas</div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                @foreach ($entities as $entity)
                                <th>{{ $entity }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datesWithData as $date)
                            <tr>
                                <td>{{ $date }}</td>
                                @foreach ($entities as $entity)
                                <td>Rp {{ isset($dailyData[$entity][$date]) ? number_format($dailyData[$entity][$date] / 1000000, 2, ',', '.') : '0' }}</td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer>
            <p>© 2023 Dashboard Data Harian | Dibuat dengan Chart.js</p>
        </footer>
    </div>

    <script>
        // Data dari PHP
        const dailyData = @json($dailyData);
        const entities = @json($entities);
        const datesWithData = @json($datesWithData);
        const totalByEntity = @json($totalByEntity);

        // Konversi format tanggal dari DD-MMM-YYYY ke YYYY-MM-DD untuk sorting
        function convertDateFormat(dateStr) {
            const months = {
                'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04', 'May': '05', 'Jun': '06',
                'Jul': '07', 'Aug': '08', 'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'
            };
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                const day = parts[0].padStart(2, '0');
                const month = months[parts[1]];
                const year = parts[2];
                return `${year}-${month}-${day}`;
            }
            return dateStr;
        }

        // Fungsi untuk format angka
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        // Siapkan data untuk chart
        const labels = datesWithData;

        // Warna untuk setiap entitas
        const colors = [
            { border: 'rgba(255, 99, 132, 1)', background: 'rgba(255, 99, 132, 0.7)' },
            { border: 'rgba(54, 162, 235, 1)', background: 'rgba(54, 162, 235, 0.7)' },
            { border: 'rgba(75, 192, 192, 1)', background: 'rgba(75, 192, 192, 0.7)' },
            { border: 'rgba(255, 159, 64, 1)', background: 'rgba(255, 159, 64, 0.7)' },
            { border: 'rgba(153, 102, 255, 1)', background: 'rgba(153, 102, 255, 0.7)' }
        ];

        // Dataset untuk grafik harian
        const datasets = entities.map((entity, index) => {
            return {
                label: entity,
                data: labels.map(date => dailyData[entity][date] ? dailyData[entity][date] / 1000000 : 0),
                borderColor: colors[index % colors.length].border,
                backgroundColor: colors[index % colors.length].background,
                borderWidth: 2,
                fill: false,
                tension: 0.1
            };
        });

        // Dataset untuk grafik perbandingan
        const comparisonData = entities.map((entity, index) => {
            const total = Object.values(dailyData[entity]).reduce((sum, value) => sum + value, 0) / 1000000;
            return {
                label: entity,
                data: [total],
                backgroundColor: colors[index % colors.length].background,
                borderColor: colors[index % colors.length].border,
                borderWidth: 1
            };
        });

        // Hitung total kontribusi per entitas untuk pie chart
        const pieChartData = entities.map((entity, index) => {
            const total = Object.values(dailyData[entity]).reduce((sum, value) => sum + value, 0) / 1000000;
            return {
                label: entity,
                data: total,
                color: colors[index % colors.length].background
            };
        });

        const totalAll = pieChartData.reduce((sum, item) => sum + item.data, 0);

        // Dataset untuk pie chart
        const pieDatasets = [{
            data: pieChartData.map(item => item.data),
            backgroundColor: pieChartData.map(item => item.color),
            borderColor: pieChartData.map(item => item.color.replace('0.7', '1')),
            borderWidth: 2
        }];

        // Inisialisasi chart
        let dailyChart, comparisonChart, pieChart, cumulativeChart;

        function initCharts() {
            // Grafik Harian
            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            dailyChart = new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
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
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
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
                                    label += 'Rp ' + formatNumber(context.raw) + ' Juta';
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            // Grafik Perbandingan
            const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
            comparisonChart = new Chart(comparisonCtx, {
                type: 'bar',
                data: {
                    labels: ['Total per Entitas'],
                    datasets: comparisonData
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

            // Pie Chart - Kontribusi Realisasi
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            pieChart = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: pieChartData.map(item => item.label),
                    datasets: pieDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    const percentage = ((value / totalAll) * 100).toFixed(1);
                                    return `${context.label}: Rp ${formatNumber(value)} Juta (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 12
                            },
                            formatter: (value, ctx) => {
                                const percentage = ((value / totalAll) * 100).toFixed(1);
                                return `${percentage}%`;
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });

            // Cumulative Chart - Tren Kumulatif
            const cumulativeCtx = document.getElementById('cumulativeChart').getContext('2d');
            
            // Hitung data kumulatif
            const cumulativeData = entities.map((entity, index) => {
                let cumulative = 0;
                return {
                    label: entity,
                    data: labels.map(date => {
                        const value = dailyData[entity][date] ? dailyData[entity][date] / 1000000 : 0;
                        cumulative += value;
                        return cumulative;
                    }),
                    borderColor: colors[index % colors.length].border,
                    backgroundColor: colors[index % colors.length].background.replace('0.7', '0.1'),
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                };
            });

            cumulativeChart = new Chart(cumulativeCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: cumulativeData
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
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
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
                                    label += 'Rp ' + formatNumber(context.raw) + ' Juta';
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }

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

        // Inisialisasi chart saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            initCharts();

            // Event listener untuk kontrol
            document.getElementById('entitySelect').addEventListener('change', updateCharts);
            document.getElementById('dateRange').addEventListener('change', updateCharts);
            document.getElementById('chartType').addEventListener('change', updateCharts);
        });

        // Fungsi untuk memperbarui chart berdasarkan pilihan
        function updateCharts() {
            const selectedEntity = document.getElementById('entitySelect').value;
            const dateRange = document.getElementById('dateRange').value;
            const chartType = document.getElementById('chartType').value;

            // Filter data berdasarkan entitas yang dipilih
            let filteredDatasets = datasets;
            if (selectedEntity !== 'all') {
                filteredDatasets = datasets.filter(dataset => dataset.label === selectedEntity);
            }

            // Filter data berdasarkan rentang tanggal
            let filteredLabels = labels;
            if (dateRange !== 'all') {
                filteredLabels = labels.filter(label => {
                    if (dateRange === 'jan-mar') return label.includes('Jan') || label.includes('Feb') || label.includes('Mar');
                    if (dateRange === 'apr-jun') return label.includes('Apr') || label.includes('May') || label.includes('Jun');
                    if (dateRange === 'jul-sep') return label.includes('Jul') || label.includes('Aug') || label.includes('Sep');
                    return true;
                });
            }

            // Perbarui grafik harian
            dailyChart.data.labels = filteredLabels;
            dailyChart.data.datasets = filteredDatasets.map(dataset => {
                return {
                    ...dataset,
                    data: filteredLabels.map(date => {
                        const originalIndex = labels.indexOf(date);
                        return originalIndex !== -1 ? dataset.data[originalIndex] : 0;
                    })
                };
            });
            dailyChart.config.type = chartType;
            dailyChart.update();

            // Perbarui grafik perbandingan
            comparisonChart.update();

            // Update cumulative chart
            let filteredCumulativeData = cumulativeChart.data.datasets;
            if (selectedEntity !== 'all') {
                filteredCumulativeData = cumulativeChart.data.datasets.filter(dataset => dataset.label === selectedEntity);
            }

            cumulativeChart.data.labels = filteredLabels;
            cumulativeChart.data.datasets = filteredCumulativeData.map(dataset => {
                return {
                    ...dataset,
                    data: filteredLabels.map(date => {
                        const originalIndex = labels.indexOf(date);
                        return originalIndex !== -1 ? dataset.data[originalIndex] : 0;
                    })
                };
            });
            cumulativeChart.update();
        }
    </script>
</body>
</html>