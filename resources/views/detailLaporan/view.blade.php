<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Laba Rugi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            margin-bottom: 20px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table th {
            background-color: #343a40;
            color: white;
            text-align: center;
            vertical-align: middle;
        }
        .filter-section {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .achieve {
            color: green;
            font-weight: bold;
        }
        .overbudget {
            color: red;
            font-weight: bold;
        }
        .efisien {
            color: blue;
            font-weight: bold;
        }
        .tidak-achieve {
            color: orange;
            font-weight: bold;
        }
        .chart-container {
            height: 400px;
            margin-bottom: 30px;
        }
        .company-header {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header Perusahaan -->
        <div class="company-header">
            <h1>SINERGI BISNIS SOLUTION</h1>
            <h2>LAPORAN KEUANGAN</h2>
        </div>

        <!-- Filter Section -->
        <div class="card filter-section">
            <div class="card-body">
                <form id="filterForm">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="tahun" class="form-label">Tahun</label>
                            <select class="form-select" id="tahun" name="tahun">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="entitas" class="form-label">Entitas</label>
                            <select class="form-select" id="entitas" name="entitas">
                                <option value="">Semua Entitas</option>
                                @foreach($entitas as $entity)
                                    <option value="{{ $entity->kd_entitas }}">{{ $entity->nama_entitas }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bulan</label>
                            <div class="row">
                                @foreach($bulanList as $index => $bulan)
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input bulan-checkbox" type="checkbox" value="{{ $index + 1 }}" id="bulan{{ $index + 1 }}" checked>
                                            <label class="form-check-label" for="bulan{{ $index + 1 }}">
                                                {{ $bulan }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="button" id="filterButton" class="btn btn-primary">Tampilkan Data</button>
                            <button type="button" id="resetButton" class="btn btn-secondary">Reset Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Grafik Section -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">GRAFIK TARGET VS REALISASI</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="laporanChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabel Laporan Section -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">DATA LAPORAN LABA RUGI</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="laporanTable">
                        <thead>
                            <tr>
                                <th>BU/AN</th>
                                <th>TARGET</th>
                                <th>%</th>
                                <th>REALISASI</th>
                                <th>%</th>
                                <th>GAP</th>
                                <th>%</th>
                                <th>KETERANGAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data akan diisi oleh JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let laporanChart = null;
            
            // Format angka ke format Rupiah
            function formatRupiah(angka) {
                if (!angka) return '0';
                
                const number = parseFloat(angka);
                return new Intl.NumberFormat('id-ID', {
                    maximumFractionDigits: 0
                }).format(number);
            }

            // Format persentase
            function formatPersentase(angka) {
                if (!angka) return '0%';
                
                const number = parseFloat(angka);
                return number.toFixed(2) + '%';
            }

            // Tentukan class untuk keterangan
            function getKeteranganClass(keterangan) {
                if (keterangan.includes('ACHIEVE')) return 'achieve';
                if (keterangan.includes('OVERBUDGET')) return 'overbudget';
                if (keterangan.includes('EFISIEN')) return 'efisien';
                if (keterangan.includes('TIDAK')) return 'tidak-achieve';
                return '';
            }

            // Fungsi untuk memuat data tabel
            function loadTableData() {
                const selectedBulan = Array.from(document.querySelectorAll('.bulan-checkbox:checked'))
                    .map(checkbox => checkbox.value);
                const tahun = document.getElementById('tahun').value;
                const entitas = document.getElementById('entitas').value;

                fetch('/detailLaporan/get-laporan-data', {
                    method: 'get',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        bulan: selectedBulan,
                        tahun: tahun,
                        entitas: entitas
                    })
                })
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.querySelector('#laporanTable tbody');
                    tableBody.innerHTML = '';

                    data.data.forEach(item => {
                        const row = document.createElement('tr');
                        const keteranganClass = getKeteranganClass(item.keterangan);
                        
                        row.innerHTML = `
                            <td>${item.bu_an}</td>
                            <td class="text-end">${formatRupiah(item.target)}</td>
                            <td class="text-center">${formatPersentase(item.target_persen)}</td>
                            <td class="text-end">${formatRupiah(item.realisasi)}</td>
                            <td class="text-center">${formatPersentase(item.realisasi_persen)}</td>
                            <td class="text-end">${formatRupiah(item.gap)}</td>
                            <td class="text-center">${formatPersentase(item.gap_persen)}</td>
                            <td class="text-center ${keteranganClass}">${item.keterangan}</td>
                        `;
                        
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }

            // Fungsi untuk memuat data grafik
            function loadChartData() {
                const selectedBulan = Array.from(document.querySelectorAll('.bulan-checkbox:checked'))
                    .map(checkbox => checkbox.value);
                const tahun = document.getElementById('tahun').value;
                const entitas = document.getElementById('entitas').value;

                fetch('/detailLaporan/get-chart-data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        bulan: selectedBulan,
                        tahun: tahun,
                        entitas: entitas
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Hancurkan chart sebelumnya jika ada
                    if (laporanChart) {
                        laporanChart.destroy();
                    }

                    // Buat chart baru
                    const ctx = document.getElementById('laporanChart').getContext('2d');
                    laporanChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [
                                {
                                    label: 'Target',
                                    data: data.datasets[0].data,
                                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Realisasi',
                                    data: data.datasets[1].data,
                                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
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
                                            return 'Rp. ' + formatRupiah(value);
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
                                            label += 'Rp. ' + formatRupiah(context.raw);
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }

            // Fungsi untuk memuat semua data
            function loadAllData() {
                loadTableData();
                loadChartData();
            }

            // Event listener untuk tombol filter
            document.getElementById('filterButton').addEventListener('click', loadAllData);

            // Event listener untuk tombol reset
            document.getElementById('resetButton').addEventListener('click', function() {
                document.querySelectorAll('.bulan-checkbox').forEach(checkbox => {
                    checkbox.checked = true;
                });
                document.getElementById('tahun').value = '{{ date("Y") }}';
                document.getElementById('entitas').value = '';
                loadAllData();
            });

            // Muat data pertama kali
            loadAllData();
        });
    </script>
</body>
</html>