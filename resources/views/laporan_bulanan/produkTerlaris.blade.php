<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Produk Terlaris</title>
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
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
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
            position: sticky;
            top: 0;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .data-table tr:hover {
            background-color: #f1f3f5;
        }
        
        .top-product {
            background-color: #fff8e1;
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
        
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 0.9em;
        }
        
        .chart-controls {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
            gap: 10px;
        }
        
        .chart-btn {
            padding: 8px 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .chart-btn:hover {
            background: #5a6fd5;
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
                font-size: 0.85rem;
            }
            
            .data-table th, .data-table td {
                padding: 10px 5px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            header {
                padding: 15px;
            }
            
            .chart-container {
                padding: 15px;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Laporan Produk Terlaris</h1>
            <p class="description">Analisis Penjualan Produk Berdasarkan Jumlah Terjual dan Pendapatan</p>
        </header>
        
        @if($usingSampleData)
        <div class="warning">
            ⚠️ Data tidak ditemukan di database. Menampilkan data contoh untuk demonstrasi.
        </div>
        @endif
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-title">Total Pendapatan</div>
                <div class="stat-value">Rp {{ number_format($totalPenjualan / 1000000, 2, ',', '.') }} Juta</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Item Terjual</div>
                <div class="stat-value">{{ number_format($totalItem, 0, ',', '.') }} Item</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Rata-rata Pendapatan per Produk</div>
                <div class="stat-value">Rp {{ number_format($averagePenjualan / 1000000, 2, ',', '.') }} Juta</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Rata-rata Item Terjual per Produk</div>
                <div class="stat-value">{{ number_format($averageItems, 1, ',', '.') }} Item</div>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-title">📈 Pendapatan per Produk (dalam Juta Rupiah)</div>
            <div class="chart-controls">
                <button class="chart-btn" onclick="changeChartType('salesChart', 'bar')">Batang Vertikal</button>
                <button class="chart-btn" onclick="changeChartType('salesChart', 'horizontalBar')">Batang Horizontal</button>
            </div>
            <div class="chart-wrapper">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-title">📊 Jumlah Item Terjual per Produk</div>
            <div class="chart-controls">
                <button class="chart-btn" onclick="changeChartType('itemsChart', 'bar')">Batang Vertikal</button>
                <button class="chart-btn" onclick="changeChartType('itemsChart', 'horizontalBar')">Batang Horizontal</button>
            </div>
            <div class="chart-wrapper">
                <canvas id="itemsChart"></canvas>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-title">🥧 Distribusi Pendapatan per Produk</div>
            <div class="chart-wrapper">
                <canvas id="pieChart"></canvas>
            </div>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Nama Produk</th>
                    <th>Jumlah Terjual</th>
                    <th>Total Pendapatan (Rp)</th>
                    <th>Pendapatan (Rp Juta)</th>
                    <th>Persentase dari Total</th>
                </tr>
            </thead>
            <tbody id="table-body">
                @php 
                $rank = 1;
                foreach ($chartData as $item): 
                    $percentage = $totalPenjualan > 0 ? ((int)($item['total_sales'] ?? 0) / $totalPenjualan) * 100 : 0;
                    $rowClass = $rank <= 3 ? 'top-product' : '';
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>{{ $rank }}</td>
                    <td><strong>{{ $item['nama_produk'] ?? 'Unknown Product' }}</strong></td>
                    <td>{{ number_format($item['total_items'] ?? 0, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item['total_sales'] ?? 0, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format(((int)($item['total_sales'] ?? 0)) / 1000000, 2, ',', '.') }}</td>
                    <td>{{ number_format($percentage, 2, ',', '.') }}%</td>
                </tr>
                @php 
                $rank++;
                endforeach; 
                @endphp
                <tr style="background-color: #e3f2fd;">
                    <td colspan="2"><strong>TOTAL</strong></td>
                    <td><strong>{{ number_format($totalItem, 0, ',', '.') }}</strong></td>
                    <td><strong>Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</strong></td>
                    <td><strong>Rp {{ number_format($totalPenjualan / 1000000, 2, ',', '.') }}</strong></td>
                    <td><strong>100%</strong></td>
                </tr>
            </tbody>
        </table>
        
        <footer>
            <p>© {{ date('Y') }} Dashboard Laporan Produk Terlaris | Dibuat dengan Chart.js</p>
            <p>Terakhir diperbarui: {{ date('d/m/Y H:i:s') }}</p>
        </footer>
    </div>

    <script>
        // Data dari PHP (dikirim melalui controller)
        const productNames = @json($productNames);
        const salesData = @json($salesData);
        const itemsData = @json($itemsData);
        const totalSales = @json($totalPenjualan);
        
        // Warna untuk grafik
        const colors = [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)',
            'rgba(199, 199, 199, 0.7)',
            'rgba(83, 102, 255, 0.7)',
            'rgba(40, 159, 64, 0.7)',
            'rgba(210, 99, 132, 0.7)'
        ];
        
        const borderColors = [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)',
            'rgba(199, 199, 199, 1)',
            'rgba(83, 102, 255, 1)',
            'rgba(40, 159, 64, 1)',
            'rgba(210, 99, 132, 1)'
        ];
        
        // Chart instances untuk referensi nanti
        let salesChartInstance, itemsChartInstance, pieChartInstance;
        
        // Fungsi untuk format angka
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }
        
        // Fungsi untuk mengubah tipe chart
        function changeChartType(chartId, type) {
            if (chartId === 'salesChart' && salesChartInstance) {
                salesChartInstance.destroy();
                initSalesChart(type);
            } else if (chartId === 'itemsChart' && itemsChartInstance) {
                itemsChartInstance.destroy();
                initItemsChart(type);
            }
        }
        
        // 1. Grafik Pendapatan per Produk
        function initSalesChart(type = 'bar') {
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            salesChartInstance = new Chart(salesCtx, {
                type: type === 'horizontalBar' ? 'bar' : type,
                data: {
                    labels: productNames,
                    datasets: [{
                        label: 'Pendapatan (Juta Rupiah)',
                        data: salesData,
                        backgroundColor: colors,
                        borderColor: borderColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: type === 'horizontalBar' ? 'y' : 'x',
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: type === 'horizontalBar' ? undefined : function(value) {
                                    return 'Rp ' + formatNumber(value);
                                },
                                maxRotation: type === 'horizontalBar' ? 0 : 45,
                                minRotation: type === 'horizontalBar' ? 0 : 45
                            },
                            title: {
                                display: true,
                                text: type === 'horizontalBar' ? 'Nama Produk' : 'Juta Rupiah'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: type === 'horizontalBar' ? function(value) {
                                    return 'Rp ' + formatNumber(value);
                                } : undefined
                            },
                            title: {
                                display: true,
                                text: type === 'horizontalBar' ? 'Juta Rupiah' : 'Nama Produk'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Pendapatan: Rp ' + context.raw.toLocaleString('id-ID') + ' Juta';
                                }
                            }
                        },
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        }
        
        // 2. Grafik Jumlah Item Terjual
        function initItemsChart(type = 'bar') {
            const itemsCtx = document.getElementById('itemsChart').getContext('2d');
            itemsChartInstance = new Chart(itemsCtx, {
                type: type === 'horizontalBar' ? 'bar' : type,
                data: {
                    labels: productNames,
                    datasets: [{
                        label: 'Jumlah Item Terjual',
                        data: itemsData,
                        backgroundColor: colors,
                        borderColor: borderColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: type === 'horizontalBar' ? 'y' : 'x',
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                maxRotation: type === 'horizontalBar' ? 0 : 45,
                                minRotation: type === 'horizontalBar' ? 0 : 45
                            },
                            title: {
                                display: true,
                                text: type === 'horizontalBar' ? 'Nama Produk' : 'Jumlah Item'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: type === 'horizontalBar' ? 'Jumlah Item' : 'Nama Produk'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Jumlah: ' + context.raw.toLocaleString('id-ID') + ' item';
                                }
                            }
                        },
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        }
        
        // 3. Pie Chart Distribusi Pendapatan
        function initPieChart() {
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            pieChartInstance = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: productNames,
                    datasets: [{
                        data: salesData,
                        backgroundColor: colors,
                        borderColor: borderColors,
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
                                    const value = context.raw;
                                    const percentage = ((value / totalSales) * 100).toFixed(1);
                                    return context.label + ': Rp ' + value.toLocaleString('id-ID') + ' Juta (' + percentage + '%)';
                                }
                            }
                        },
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 15,
                                padding: 15
                            }
                        }
                    }
                }
            });
        }
        
        // Inisialisasi semua chart saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            initSalesChart();
            initItemsChart();
            initPieChart();
        });
    </script>
</body>
</html>