<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Data Harian per Bulan</title>
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
            height: 500px; /* Atur tinggi agar tidak tumpang tindih */
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
        
        .stats-grid-monthly {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin: 20px 0 0 0;
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
        
        .stat-card-sm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            min-height: 60px;
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
        
        .stat-value-sm {
            font-size: 1.1em;
            font-weight: bold;
            margin: 2px 0;
            line-height: 1.1;
        }
        
        .stat-label {
            font-size: 0.85em;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .stat-label-sm {
            font-size: 0.7em;
            opacity: 0.9;
            margin-bottom: 2px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .tab {
            padding: 12px 24px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .tab:hover {
            background: #e9ecef;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .back-button:hover {
            background: #5a6fd8;
        }
        
        .month-content {
            display: none;
        }
        
        .monthly-stats {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        /* CSS untuk card statistik bulanan */
        .monthly-stats-container {
            margin-top: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border-left: 4px solid #007bff;
        }

        .stats-grid-monthly {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin: 0;
        }

        .stat-card-monthly {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #dee2e6;
            transition: transform 0.2s ease;
        }

        .stat-card-monthly:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .stat-value-monthly {
            font-size: 1.4em;
            font-weight: bold;
            color: #2c3e50;
            margin: 5px 0;
            line-height: 1.2;
        }

        .stat-label-monthly {
            font-size: 0.8em;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        /* Warna khusus untuk setiap card */
        .stat-card-monthly:nth-child(1) {
            border-top: 3px solid #28a745;
        }

        .stat-card-monthly:nth-child(2) {
            border-top: 3px solid #ffc107;
        }

        .stat-card-monthly:nth-child(3) {
            border-top: 3px solid #dc3545;
        }

        .stat-card-monthly:nth-child(4) {
            border-top: 3px solid #17a2b8;
        }

        /* STYLE BARU UNTUK TABEL STATISTIK */
        .stats-table-container {
            margin-top: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stats-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #5a67d8;
        }

        .stats-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #dee2e6;
            background-color: white;
        }

        .stats-table tr:nth-child(even) td {
            background-color: #f8f9fa;
        }

        .stats-table tr:hover td {
            background-color: #e9ecef;
        }

        .stat-highlight {
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-total {
            background-color: #e7f3ff !important;
            font-weight: bold;
        }

        .stat-average {
            color: #28a745;
        }

        .stat-max {
            color: #dc3545;
            font-weight: bold;
        }

        .stat-days {
            color: #6f42c1;
        }

        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                text-align: center;
            }
            
            .chart-container {
                height: auto; /* Mengubah tinggi menjadi auto */
            }
            
            .chart-wrapper {
                height: 350px;
            }
            
            .stats-grid-monthly {
                grid-template-columns: repeat(2, 1fr);
            }

            .stats-table {
                font-size: 0.9em;
            }
            
            .stats-table th, 
            .stats-table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center; color: #2c3e50; margin-bottom: 30px; font-size: 1.8em;">
            📊 Analisis Data Harian per Bulan
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
                    <div class="stat-label">Total Semua Bulan</div>
                    <div class="stat-value">{{ number_format($totalAll, 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Bulan dengan Data</div>
                    <div class="stat-value">{{ $monthsWithData }} dari 12</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Rata-rata per Bulan</div>
                    <div class="stat-value">{{ number_format($monthlyAverage, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="stats-container">
            <div class="chart-title">📅 Pilih Bulan untuk Ditampilkan</div>
            <div class="tabs" id="monthTabs">
                </div>
        </div>

        <div id="monthlyChartsContainer">
            </div>

        <div class="chart-container">
            <div class="chart-title">📈 Perbandingan Total per Bulan</div>
            <div class="chart-wrapper">
                <canvas id="monthlyComparisonChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <div class="chart-title">📈 Trend Harian (Semua Bulan)</div>
            <div class="chart-wrapper">
                <canvas id="dailyTrendChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Data dari PHP
        const chartData = @json($chartData);
        const monthlyStats = @json($monthlyStats);
        
        console.log('Data dari PHP:', chartData);

        // Fungsi untuk format angka
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        // Warna untuk chart
        const colorPalette = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40',
            '#C9CBCF', '#7CFC00', '#20B2AA', '#FF6B6B', '#6A5ACD', '#FFD700'
        ];

        // Objek untuk menyimpan instance chart agar tidak dirender ulang
        const charts = {};

        // Fungsi untuk membuat tab dan konten (grafik + tabel)
        function createTabsAndCharts() {
            const monthTabsContainer = document.getElementById('monthTabs');
            const monthlyChartsContainer = document.getElementById('monthlyChartsContainer');
            let firstTab = true;

            for (const month in chartData) {
                // Buat tab
                const tab = document.createElement('div');
                tab.className = 'tab';
                tab.setAttribute('data-month', month);
                tab.textContent = month;
                monthTabsContainer.appendChild(tab);

                // Buat wadah konten (grafik dan statistik)
                const monthContent = document.createElement('div');
                monthContent.className = 'month-content';
                monthContent.id = `content-${month}`;
                
                const stats = monthlyStats[month] || { total: 0, average: 0, max: 0, min: 0, days_with_data: 0 };
                
                monthContent.innerHTML = `
                    <div class="chart-container">
                        <div class="chart-title">📊 Data Harian Bulan ${month}</div>
                        <div class="chart-wrapper">
                            <canvas id="chartCanvas-${month}"></canvas>
                        </div>
                    </div>
                    
                    <div class="stats-table-container">
                        <h3 style="text-align: center; color: #2c3e50; margin: 20px 0; font-size: 1.2em; padding: 15px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                            📈 Statistik Bulanan - ${month}
                        </h3>
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Metrik</th>
                                    <th>Nilai</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="stat-highlight">Total</td>
                                    <td class="stat-total">${formatNumber(stats.total)}</td>
                                    <td>Jumlah keseluruhan bulan ${month}</td>
                                </tr>
                                <tr>
                                    <td class="stat-highlight">Rata-rata</td>
                                    <td class="stat-average">${formatNumber(Math.round(stats.average))}</td>
                                    <td>Nilai rata-rata per hari</td>
                                </tr>
                                <tr>
                                    <td class="stat-highlight">Tertinggi</td>
                                    <td class="stat-max">${formatNumber(stats.max)}</td>
                                    <td>Nilai harian tertinggi</td>
                                </tr>
                                <tr>
                                    <td class="stat-highlight">Hari Berdata</td>
                                    <td class="stat-days">${stats.days_with_data}</td>
                                    <td>Jumlah hari dengan data</td>
                                </tr>
                                <tr>
                                    <td class="stat-highlight">Terendah</td>
                                    <td>${formatNumber(stats.min)}</td>
                                    <td>Nilai harian terendah (selain 0)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                `;
                
                monthlyChartsContainer.appendChild(monthContent);

                if (firstTab) {
                    tab.classList.add('active');
                    monthContent.style.display = 'block';
                    firstTab = false;
                }
            }
        }

        // Tangani klik tab
        document.addEventListener('DOMContentLoaded', () => {
            createTabsAndCharts();

            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.month-content').forEach(content => content.style.display = 'none');
                    
                    this.classList.add('active');
                    const month = this.getAttribute('data-month');
                    document.getElementById(`content-${month}`).style.display = 'block';
                    
                    // Render chart jika belum di-render
                    if (!charts[month]) {
                        renderMonthlyChart(month);
                    }
                });
            });

            // Render grafik perbandingan bulanan dan tren harian
            renderMonthlyComparisonChart();
            renderDailyTrendChart();
            
            // Render grafik bulan pertama
            const firstMonth = Object.keys(chartData)[0];
            if (firstMonth) {
                renderMonthlyChart(firstMonth);
            }
        });

        // Render chart untuk bulan tertentu
        function renderMonthlyChart(month) {
            if (charts[month]) {
                charts[month].destroy(); // Hancurkan chart lama jika ada
            }
            const ctx = document.getElementById(`chartCanvas-${month}`).getContext('2d');
            const dailyData = chartData[month];
            const days = Array.from({length: dailyData.length}, (_, i) => i + 1);
            
            charts[month] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: days.map(day => `Hari ${day}`),
                    datasets: [{
                        label: `Nilai Harian - ${month}`,
                        data: dailyData,
                        backgroundColor: dailyData.map(value => value > 0 ? colorPalette[0] : '#CCCCCC'),
                        borderColor: colorPalette[0],
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
                                    return `Hari ${context.dataIndex + 1}: ${formatNumber(context.raw)}`;
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
        }

        // Grafik Perbandingan Bulanan
        function renderMonthlyComparisonChart() {
            const monthlyCtx = document.getElementById('monthlyComparisonChart').getContext('2d');
            const months = Object.keys(monthlyStats);
            const monthlyTotals = months.map(month => monthlyStats[month].total);
            
            new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Total per Bulan',
                        data: monthlyTotals,
                        backgroundColor: colorPalette,
                        borderColor: colorPalette.map(color => color.replace('0.8', '1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Total: ${formatNumber(context.raw)}`;
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
        }

        // Grafik Trend Harian
        function renderDailyTrendChart() {
            const trendCtx = document.getElementById('dailyTrendChart').getContext('2d');
            const allDailyData = [];
            const allLabels = [];
            
            Object.entries(chartData).forEach(([month, dailyData]) => {
                dailyData.forEach((value, dayIndex) => {
                    if (value > 0) {
                        allDailyData.push(value);
                        allLabels.push(`${month} Hari ${dayIndex + 1}`);
                    }
                });
            });
            
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: allLabels,
                    datasets: [{
                        label: 'Trend Harian',
                        data: allDailyData,
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Nilai: ${formatNumber(context.raw)}`;
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
        }
    </script>
</body>
</html>