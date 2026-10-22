<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Perbandingan Realisasi Omzet</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
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
            display: flex;
            flex-direction: column;
            gap: 20px;
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

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.4em;
            font-weight: 600;
        }

        .description {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            height: 500px;
        }

        .stats-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 15px;
            border: 1px solid #eee;
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

        .growth-positive {
            color: #28a745;
        }

        .growth-negative {
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

        .chart-wrapper {
            position: relative;
            height: 400px;
            width: 100%;
        }

        .navbar {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .chart-wrapper {
                height: 300px;
            }

            h1 {
                font-size: 1.8rem;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <h1>📊 Analisis Perbandingan Realisasi Omzet</h1>
            <p class="description">Visualisasi Data Omzet per Tahun</p>
        </header>

        <!-- Navigasi -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">Analisis Omzet</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">Perbandingan Omzet</a>
                        </li>
                        <!-- Tambahkan menu lain jika diperlukan -->
                    </ul>
                </div>
            </div>
        </nav>
        
        @if($usingSampleData)
        <div class="warning">
            ⚠️ Data tidak ditemukan di database. Menampilkan data contoh untuk demonstrasi.
        </div>
        @endif
        
        <!-- Container untuk statistik -->
        <div class="stats-container">
            <h2>Statistik Realisasi Omzet</h2>
            <div class="row">
                @foreach($stats['total_per_year'] as $year => $total)
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-title">Tahun {{ $year }}</div>
                        <div class="stat-item">
                            <span class="stat-label">Total:</span>
                            <span class="stat-value">Rp {{ $total }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Rata-rata:</span>
                            <span class="stat-value">Rp {{ $stats['average_per_year'][$year] }}</span>
                        </div>
                        @if(isset($stats['growth_rates'][$year]))
                        <div class="stat-item">
                            <span class="stat-label">Pertumbuhan:</span>
                            <span class="stat-value {{ $stats['growth_rates'][$year] >= 0 ? 'growth-positive' : 'growth-negative' }}">
                                {{ number_format($stats['growth_rates'][$year], 2) }}%
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
                
                <!-- Statistik Keseluruhan -->
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-title">Keseluruhan</div>
                        <div class="stat-item">
                            <span class="stat-label">Total:</span>
                            <span class="stat-value">Rp {{ $totalAll }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Rata-rata:</span>
                            <span class="stat-value">Rp {{ $averageAll }}</span>
                        </div>
                        @if(isset($growthRate))
                        <div class="stat-item">
                            <span class="stat-label">Pertumbuhan Terakhir:</span>
                            <span class="stat-value {{ $growthRate >= 0 ? 'growth-positive' : 'growth-negative' }}">
                                {{ number_format($growthRate, 2) }}%
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <div class="chart-container">
            <h2>📊 Perbandingan Realisasi Omzet per Bulan</h2>
            <div class="chart-wrapper">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <h2>📈 Tren Realisasi Omzet per Tahun</h2>
            <div class="chart-wrapper">
                <canvas id="lineChart"></canvas>
            </div>
        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const data = @json($chartData);
        const years = Object.keys(data);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        // Warna untuk setiap tahun
        const colors = [
            { border: 'rgba(255, 99, 132, 1)', background: 'rgba(255, 99, 132, 0.7)' },
            { border: 'rgba(54, 162, 235, 1)', background: 'rgba(54, 162, 235, 0.7)' },
            { border: 'rgba(75, 192, 192, 1)', background: 'rgba(75, 192, 192, 0.7)' },
            { border: 'rgba(255, 159, 64, 1)', background: 'rgba(255, 159, 64, 0.7)' },
            { border: 'rgba(153, 102, 255, 1)', background: 'rgba(153, 102, 255, 0.7)' }
        ];

        // Format angka dengan pemisah ribuan
        const formatNumber = (value) => {
            return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        };

        // --- Grafik Batang (Bar Chart) ---
        const barDatasets = years.map((year, index) => {
            const color = colors[index % colors.length];
            return {
                label: year,
                data: data[year].map(value => parseFloat(value)),
                backgroundColor: color.background,
                borderColor: color.border,
                borderWidth: 1,
            };
        });

        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: barDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Omzet (IDR)' },
                        ticks: {
                            callback: function(value) {
                                // Format angka dengan pemisah ribuan
                                return formatNumber(value);
                            }
                        }
                    },
                    x: {
                        title: { display: true, text: 'Bulan' }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                // Format tooltip dengan pemisah ribuan
                                return formatNumber(context.raw);
                            }
                        }
                    }
                }
            }
        });

        // --- Grafik Garis (Line Chart) ---
        const lineDatasets = years.map((year, index) => {
            const color = colors[index % colors.length];
            return {
                label: year,
                data: data[year].map(value => parseFloat(value)),
                fill: false,
                backgroundColor: color.border,
                borderColor: color.border,
                tension: 0.1
            };
        });
        
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: lineDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Omzet (IDR)' },
                        ticks: {
                            callback: function(value) {
                                // Format angka dengan pemisah ribuan
                                return formatNumber(value);
                            }
                        }
                    },
                    x: {
                        title: { display: true, text: 'Bulan' }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                // Format tooltip dengan pemisah ribuan
                                return formatNumber(context.raw);
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>