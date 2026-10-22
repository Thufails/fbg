<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan - PT. FBG</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .card {
            margin-bottom: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            font-weight: 600;
            padding: 15px 20px;
        }
        .card-title {
            margin-bottom: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .text-success {
            color: #28a745 !important;
            font-weight: 600;
        }
        .text-danger {
            color: #dc3545 !important;
            font-weight: 600;
        }
        .chart-container {
            width: 100%; 
            margin: 0 auto;
            height: 400px; /* Diperbesar dari 300px */
        }
        .large-chart {
            height: 500px; /* Grafik yang lebih besar */
        }
        .table-responsive {
            border-radius: 5px;
            overflow: hidden;
        }
        .currency {
            text-align: right;
            font-family: monospace;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 700;
        }
        .indent-1 {
            padding-left: 20px;
        }
        .indent-2 {
            padding-left: 40px;
        }
        .summary-total {
            background-color: #e9ecef;
            font-weight: 700;
        }
        .chart-wrapper {
            position: relative;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <h1 class="text-center mb-4">Laporan Bulanan</h1>
        @include('layouts.notif')
        {{-- Bagian Tabel Utama --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Tabel Laporan Keuangan</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Nama Akun</th>
                                <th class="text-right">Target</th>
                                <th class="text-right">% Thd Pendapatan</th>
                                <th class="text-right">Realisasi</th>
                                <th class="text-right">% Thd Pendapatan</th>
                                <th class="text-right">GAP</th>
                                <th class="text-right">%</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData as $data)
                            <tr>
                                @if(strip_tags($data['nama_akun']) == 'Laba Kotor' || strip_tags($data['nama_akun']) == 'NPM (Net Profit Margin)')
                                    <td class="font-weight-bold">{!! $data['nama_akun'] !!}</td>
                                @elseif($data['level'] == 1)
                                    <td class="font-weight-bold">{!! $data['nama_akun'] !!}</td>
                                @else
                                    <td class="indent-1">{!! $data['nama_akun'] !!}</td>
                                @endif
                                
                                <td class="currency">{{ number_format($data['target'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($data['persen_target'], 2) }}%</td>
                                <td class="currency">{{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($data['persen_realisasi'], 2) }}%</td>
                                <td class="currency">{{ number_format($data['gap'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($data['percentage'], 2) }}%</td>
                                <td class="{{ $data['keterangan'] == 'ACHIEVE' ? 'text-success' : 'text-danger' }}">
                                    {{ $data['keterangan'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if(isset($reportDataAnalysis))
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Analisis Performa Keuangan</h5>
            </div>
            <div class="card-body">
                    {{ $reportDataAnalysis }}
            </div>
        </div>
        @endif

        {{-- Grafik Target vs Realisasi Harian --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">GOAL VS REALITY</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="myChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Tabel Ringkasan --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Laporan Pendapatan Harian</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Tanggal</th>
                                <th class="text-right">Target</th>
                                <th class="text-right">% vs Total</th>
                                <th class="text-right">Realisasi</th>
                                <th class="text-right">% vs Total</th>
                                <th class="text-right">GAP</th>
                                <th class="text-right">% vs Target</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summaryData as $data)
                            <tr class="{{ isset($data['is_total']) ? 'summary-total' : '' }}">
                                <td>
                                    @if(isset($data['is_total']))
                                        {!! $data['nama_akun'] !!}
                                    @else
                                        {{ $data['nama_akun'] }}
                                    @endif
                                </td>
                                <td class="currency">Rp {{ number_format($data['target'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($data['percentage_vs_pendapatan_target'], 2) }}%</td>
                                <td class="currency">Rp {{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($data['percentage_vs_pendapatan_realisasi'], 2) }}%</td>
                                <td class="currency {{ $data['gap'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format($data['gap'], 0, ',', '.') }}
                                </td>
                                <td class="text-right {{ $data['percentage_vs_target'] >= 100 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($data['percentage_vs_target'], 2) }}%
                                </td>
                                <td class="{{ $data['keterangan'] == 'ACHIEVE' ? 'text-success' : 'text-danger' }}">
                                    {{ $data['keterangan'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        {{-- Grafik Perbandingan Realisasi Harian Antar Bulan --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Perbandingan Realisasi Harian Omzet Antar Bulan</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
        
        {{-- Tabel Ringkasan Grafik Perbandingan Bulanan --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Ringkasan Realisasi Bulanan</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-center">Akun</th>
                                @foreach($monthlySummaryRealisasi as $monthName => $total)
                                    <th class="text-center">{{ $monthName }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center font-weight-bold">Omzet</td>
                                @foreach($monthlySummaryRealisasi as $monthName => $total)
                                    <td class="currency">Rp {{ number_format($total, 0, ',', '.') }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Grafik Perbandingan Realisasi Antar Tahun --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Perbandingan Realisasi Omzet Antar Tahun</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="yearlyChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Grafik Perbandingan Realisasi Antar Entitas (Tren) --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Tren Realisasi Harian Antar Entitas</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="entityTrendChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Tabel Perbandingan Realisasi Antar Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Perbandingan Target & Realisasi Pendapatan Antar Entitas</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Cabang</th>
                                <th class="text-right">Target</th>
                                <th class="text-right">Realisasi</th>
                                <th class="text-right">%</th>
                                <th class="text-right">GAP</th>
                                <th>KET</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entitySummaryData as $entity => $data)
                            <tr>
                                <td>{{ $entity }}</td>
                                <td class="currency">Rp {{ number_format($data['target'], 0, ',', '.') }}</td>
                                <td class="currency">Rp {{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($data['percentage'], 2, ',', '.') }}%</td>
                                <td class="currency">{{ number_format($data['gap'], 0, ',', '.') }}</td>
                                <td class="{{ $data['keterangan'] == 'ACHIEVE' ? 'text-success' : 'text-danger' }}">
                                    {{ $data['keterangan'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Grafik Pie Kontribusi Realisasi per Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Kontribusi Realisasi per Entitas</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 450px;">
                    <canvas id="entityPieChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Grafik Batang Harga Pokok per Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Perbandingan Target dan Realisasi Harga Pokok Produksi per Entitas</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="hargaPokokBarChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Tabel Harga Pokok Produksi per Entitas --}}
        <div class="card mt-4">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Detail Harga Pokok Produksi per Entitas</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Cabang/Entitas</th>
                                <th class="text-right">Target (Rp)</th>
                                <th class="text-right">Realisasi (Rp)</th>
                                <th class="text-right">GAP (Rp)</th>
                                <th class="text-right">Persentase (%)</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($hargaPokokTableData) > 0 && $hargaPokokTableData[0]['cabang'] !== 'Tidak ada data')
                                @foreach($hargaPokokTableData as $data)
                                <tr>
                                    <td>{{ $data['cabang'] }}</td>
                                    <td class="currency">Rp {{ number_format($data['target'], 0, ',', '.') }}</td>
                                    <td class="currency">Rp {{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                                    <td class="currency {{ $data['gap'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        Rp {{ number_format($data['gap'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-right {{ $data['percentage'] >= 100 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($data['percentage'], 2) }}%
                                    </td>
                                    <td class="{{ $data['keterangan'] == 'ACHIEVE' ? 'text-success' : 'text-danger' }}">
                                        {{ $data['keterangan'] }}
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="fas fa-exclamation-circle"></i> 
                                        Tidak ada data harga pokok produksi untuk periode yang dipilih
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Grafik Batang Target vs Realisasi Harga Pokok per Bulan --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Perbandingan Target dan Realisasi Harga Pokok Produksi per Bulan</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="monthlyHargaPokokChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Grafik Perbandingan Produk Terjual --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Perbandingan Produk Terjual per Entitas</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="productSalesChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Grafik Pie Chart Produk Terlaris --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Produk Terlaris</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 450px;">
                    <canvas id="topProductsPieChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Tabel 10 Produk Terlaris --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">10 Produk Terlaris</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Nama Produk</th>
                                <th class="text-right">Jumlah Item Terjual</th>
                                <th class="text-right">Nilai Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topProductsSalesData as $product)
                            <tr>
                                <td>{{ $product->nama_produk }}</td>
                                <td class="text-right">{{ number_format($product->total_items, 0, ',', '.') }}</td>
                                <td class="currency">Rp {{ number_format($product->total_sales, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Grafik Beban Gaji per Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Beban Gaji per Entitas (Target vs Realisasi)</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="salaryChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Tabel Perbandingan Beban Gaji per Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Perbandingan Beban Gaji per Entitas</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Entitas</th>
                                <th class="text-right">Target</th>
                                <th class="text-right">Realisasi</th>
                                <th class="text-right">Gap</th>
                                <th class="text-right">%</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($salaryTableData as $data)
                            <tr>
                                <td>{{ $data['entitas'] }}</td>
                                <td class="currency">Rp {{ number_format($data['target'], 0, ',', '.') }}</td>
                                <td class="currency">Rp {{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                                <td class="currency">Rp {{ number_format($data['gap'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($data['percentage'], 2, ',', '.') }}%</td>
                                <td class="{{ $data['keterangan'] == 'ACHIEVE' ? 'text-success' : 'text-danger' }}">
                                    {{ $data['keterangan'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Grafik Beban Gaji Bulanan --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Beban Gaji Bulanan (Target vs Realisasi)</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="monthlySalaryChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Grafik Biaya Operasional per Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Perbandingan Biaya Operasional per Entitas</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="operationalCostChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Tabel Biaya Operasional per Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Biaya Operasional per Entitas</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Entitas</th>
                                <th class="text-right">Target (Rp)</th>
                                <th class="text-right">Realisasi (Rp)</th>
                                <th class="text-right">Gap (Rp)</th>
                                <th class="text-right">Persen Gap (%)</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($operationalCostData as $entitas => $data)
                            <tr>
                                <td>{{ $entitas }}</td>
                                <td class="currency">{{ number_format($data['target'], 0, ',', '.') }}</td>
                                <td class="currency">{{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                                <td class="currency">{{ number_format($data['gap'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($data['percentage'], 2) }}%</td>
                                <td class="{{ $data['keterangan'] == 'ACHIEVE' ? 'text-success' : 'text-danger' }}">
                                    {{ $data['keterangan'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Grafik Biaya Operasional Per Bulan --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Perbandingan Target dan Realisasi Biaya Operasional Per Bulan</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="monthlyOpCostChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Grafik Beban Pemasaran per Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Perbandingan Beban Pemasaran per Entitas</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="marketingCostChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Tabel Biaya Pemasaran per Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Biaya Pemasaran per Entitas</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Entitas</th>
                                <th class="text-right">Target (Rp)</th>
                                <th class="text-right">Realisasi (Rp)</th>
                                <th class="text-right">Gap (Rp)</th>
                                <th class="text-right">Persen Gap (%)</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($marketingCostData as $entitas => $data)
                            <tr>
                                <td>{{ $entitas }}</td>
                                <td class="currency">{{ number_format($data['target'], 0, ',', '.') }}</td>
                                <td class="currency">{{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                                <td class="currency">{{ number_format($data['gap'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($data['percentage'], 2) }}%</td>
                                <td class="{{ $data['keterangan'] == 'ACHIEVE' ? 'text-success' : 'text-danger' }}">
                                    {{ $data['keterangan'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Grafik Target dan Realisasi Bulanan (Keseluruhan) --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Perbandingan Target dan Realisasi Bulanan (Keseluruhan)</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="overallMonthlyChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Grafik Beban Lain-lain Bulanan --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Beban Lain-lain Bulanan</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="monthlyOtherCostChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Grafik Biaya Lain-lain per Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Biaya Lain-lain per Entitas</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="otherCostBarChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Tabel Biaya Lain-lain per Entitas --}}
        {{-- <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Biaya Lain-lain per Entitas</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Entitas</th>
                                <th class="text-right">Target</th>
                                <th class="text-right">Realisasi</th>
                                <th class="text-right">Gap</th>
                                <th class="text-right">Persentase</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($otherCostTableData as $item)
                            <tr>
                                <td>{{ $item['entitas'] }}</td>
                                <td class="currency">Rp {{ number_format($item['target'], 0, ',', '.') }}</td>
                                <td class="currency">Rp {{ number_format($item['realisasi'], 0, ',', '.') }}</td>
                                <td class="currency {{ $item['gap'] <= 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($item['gap'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($item['percentage'], 2) }}%</td>
                                <td class="{{ $item['keterangan'] == 'ACHIEVE' ? 'text-success' : 'text-danger' }}">
                                    {{ $item['keterangan'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div> --}}
        {{-- Tabel Biaya Lain-lain per Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Biaya Lain-lain per Entitas</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Entitas</th>
                                <th class="text-right">Target</th>
                                <th class="text-right">Realisasi</th>
                                <th class="text-right">Gap</th>
                                <th class="text-right">Persentase</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($otherCostTableData as $entitas => $data)
                            <tr>
                                <td>{{ $entitas }}</td>
                                <td class="currency">Rp {{ number_format($data['target'], 0, ',', '.') }}</td>
                                <td class="currency">Rp {{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                                <td class="currency {{ $data['gap'] <= 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($data['gap'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($data['percentage'], 2) }}%</td>
                                <td class="{{ $data['keterangan'] == 'ACHIEVE' ? 'text-success' : 'text-danger' }}">
                                    {{ $data['keterangan'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Grafik NPM per Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Net Profit Margin per Entitas</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="npmEntitasBarChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Tabel NPM per Entitas --}}
        {{-- <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Net Profit Margin per Entitas (Semua Beban Termasuk ZISWAF)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Entitas</th>
                                <th class="text-right">Target NPM</th>
                                <th class="text-right">Realisasi NPM</th>
                                <th class="text-right">Gap</th>
                                <th class="text-right">Persentase</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($npmEntitasTableData as $item)
                            <tr>
                                <td>{{ $item['entitas'] }}</td>
                                <td class="currency">Rp {{ number_format($item['target'], 0, ',', '.') }}</td>
                                <td class="currency">Rp {{ number_format($item['realisasi'], 0, ',', '.') }}</td>
                                <td class="currency {{ $item['gap'] >= 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($item['gap'], 0, ',', '.') }}</td>
                                <td class="text-right {{ $item['percentage'] >= 100 ? 'text-success' : 'text-danger' }}">{{ number_format($item['percentage'], 2) }}%</td>
                                <td class="{{ $item['keterangan'] == 'ACHIEVE' ? 'text-success' : 'text-danger' }}">
                                    {{ $item['keterangan'] }}
                                </td>
                            @endforeach
                            <!-- Total -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div> --}}

        {{-- Tabel NPM per Entitas --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Net Profit Margin per Entitas (Semua Beban Termasuk ZISWAF)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    {{-- <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Entitas</th>
                                <th class="text-right">Target NPM</th>
                                <th class="text-right">Realisasi NPM</th>
                                <th class="text-right">Gap</th>
                                <th class="text-right">Persentase</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($npmEntitasTableData as $entitas => $data)
                            <tr>
                                <td>{{ $entitas }}</td>
                                <td class="currency">Rp {{ number_format($data['target'], 0, ',', '.') }}</td>
                                <td class="currency">Rp {{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                                <td class="currency {{ $data['gap'] >= 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($data['gap'], 0, ',', '.') }}</td>
                                <td class="text-right {{ $data['percentage'] >= 100 ? 'text-success' : 'text-danger' }}">{{ number_format($data['percentage'], 2) }}%</td>
                                <td class="{{ $data['keterangan'] == 'ACHIEVE' ? 'text-success' : 'text-danger' }}">
                                    {{ $data['keterangan'] }}
                                </td>
                            </tr>
                            @endforeach
                            <!-- Total -->
                            <tr class="summary-total">
                                <td><strong>TOTAL</strong></td>
                                <td class="currency"><strong>Rp {{ number_format(array_sum(array_column($npmEntitasTableData, 'target')), 0, ',', '.') }}</strong></td>
                                <td class="currency"><strong>Rp {{ number_format(array_sum(array_column($npmEntitasTableData, 'realisasi')), 0, ',', '.') }}</strong></td>
                                <td class="currency {{ array_sum(array_column($npmEntitasTableData, 'gap')) >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>Rp {{ number_format(array_sum(array_column($npmEntitasTableData, 'gap')), 0, ',', '.') }}</strong>
                                </td>
                                <td class="text-right {{ (array_sum(array_column($npmEntitasTableData, 'realisasi')) / array_sum(array_column($npmEntitasTableData, 'target')) * 100) >= 100 ? 'text-success' : 'text-danger' }}">
                                    <strong>{{ number_format((array_sum(array_column($npmEntitasTableData, 'realisasi')) / array_sum(array_column($npmEntitasTableData, 'target')) * 100), 2) }}%</strong>
                                </td>
                                <td class="{{ array_sum(array_column($npmEntitasTableData, 'gap')) >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>{{ array_sum(array_column($npmEntitasTableData, 'gap')) >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE' }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table> --}}
                </div>
            </div>
        </div>

        {{-- Grafik Target vs Realisasi NPM per Bulan --}}
        <div class="card">
            <div class="card-header text-center">
                <h5 class="card-title mb-0">Grafik Target vs Realisasi NPM per Bulan</h5>
            </div>
            <div class="card-body">
                <div class="chart-container large-chart">
                    <canvas id="monthlyNpmChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    {{-- Script untuk Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
            // Data dari Controller
            const dailyRealisasi = @json($dailyRealisasi);
            const dailyTarget = @json($dailyTarget);

            // Label hari 1 sampai 31
            const labels = Array.from({length: 31}, (_, i) => i + 1);

            const data = {
                labels: labels,
                datasets: [
                    {
                        label: 'Realisasi',
                        data: dailyRealisasi,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        tension: 0.4
                    },
                    {
                        label: 'Target',
                        data: dailyTarget,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        tension: 0.4
                    }
                ]
            };

            const config = {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Grafik Target vs Realisasi Harian'
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Tanggal'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Nilai (dalam Rupiah)'
                            },
                            ticks: {
                                callback: function(value, index, values) {
                                    if (value >= 1000000000) {
                                        return (value / 1000000000) + ' M';
                                    } else if (value >= 1000000) {
                                        return (value / 1000000) + ' Jt';
                                    }
                                    return value;
                                }
                            }
                        }
                    }
                },
            };

            const myChart = new Chart(
                document.getElementById('myChart'),
                config
            );

            // Data untuk grafik perbandingan bulanan
            const monthlyDailyRealisasi = @json($monthlyDailyRealisasi);
            
            const monthlyDatasets = [];
            
            // Loop untuk membuat dataset untuk setiap bulan
            const colors = [
                'rgb(54, 162, 235)', // biru
                'rgb(255, 99, 132)', // merah
                'rgb(75, 192, 192)', // hijau
                'rgb(255, 159, 64)', // oranye
                'rgb(153, 102, 255)', // ungu
                'rgb(201, 203, 207)' // abu-abu
            ];

            let colorIndex = 0;
            for (const month in monthlyDailyRealisasi) {
                monthlyDatasets.push({
                    label: month,
                    data: monthlyDailyRealisasi[month],
                    borderColor: colors[colorIndex % colors.length],
                    backgroundColor: colors[colorIndex % colors.length],
                    tension: 0.4,
                    fill: false
                });
                colorIndex++;
            }

            const monthlyData = {
                labels: labels,
                datasets: monthlyDatasets
            };

            const monthlyConfig = {
                type: 'line',
                data: monthlyData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Perbandingan Realisasi Harian Omzet Antar Bulan'
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Tanggal'
                            }
                        },
                        y: {
                            ticks: {
                                callback: function(value, index, values) {
                                    if (value >= 1000000000) {
                                        return (value / 1000000000) + ' M';
                                    } else if (value >= 1000000) {
                                        return (value / 1000000) + ' Jt';
                                    }
                                    return value;
                                }
                            }
                        }
                    }
                },
            };

            const myMonthlyChart = new Chart(
                document.getElementById('monthlyChart'),
                monthlyConfig
            );

            // Data untuk grafik perbandingan antar tahun
            const yearlyMonthlyRealisasi = @json($yearlyMonthlyRealisasi);
            const yearlyLabels = @json($monthNames);

            const yearlyDatasets = [];
            const yearlyColors = [
                'rgb(54, 162, 235)',   // Biru
                'rgb(255, 99, 132)',   // Merah
                'rgb(75, 192, 192)',   // Hijau
                'rgb(255, 159, 64)',   // Oranye
                'rgb(153, 102, 255)',  // Ungu
                'rgb(201, 203, 207)'   // Abu-abu
            ];
            let yearlyColorIndex = 0;
            
            // Urutkan tahun dari yang paling lama ke yang paling baru
            const sortedYears = Object.keys(yearlyMonthlyRealisasi).sort();

            for (const year of sortedYears) {
                yearlyDatasets.push({
                    label: year,
                    data: yearlyMonthlyRealisasi[year],
                    borderColor: yearlyColors[yearlyColorIndex % yearlyColors.length],
                    backgroundColor: yearlyColors[yearlyColorIndex % yearlyColors.length],
                    tension: 0.4,
                    fill: false
                });
                yearlyColorIndex++;
            }

            const yearlyData = {
                labels: yearlyLabels,
                datasets: yearlyDatasets
            };

            const yearlyConfig = {
                type: 'line',
                data: yearlyData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Perbandingan Realisasi Omzet Antar Tahun'
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Bulan'
                            }
                        },
                        y: {
                            ticks: {
                                callback: function(value, index, values) {
                                    if (value >= 1000000000) {
                                        return (value / 1000000000) + ' M';
                                    } else if (value >= 1000000) {
                                        return (value / 1000000) + ' Jt';
                                    }
                                    return value;
                                }
                            }
                        }
                    }
                },
            };

            const myYearlyChart = new Chart(
                document.getElementById('yearlyChart'),
                yearlyConfig
            );

            //antar entitas

            const entityDailyRealisasi = @json($entityDailyRealisasi);
            const dateLabels = @json($dateLabels);
            const entityColors = [
                'rgb(255, 99, 132)', // Merah
                'rgb(54, 162, 235)', // Biru
                'rgb(255, 205, 86)', // Kuning
                'rgb(75, 192, 192)', // Hijau
                'rgb(153, 102, 255)', // Ungu
                'rgb(255, 159, 64)', // Oranye
                'rgb(201, 203, 207)' // Abu-abu
            ];
            
            let entityColorIndex = 0;
            const entityDatasets = [];

            for (const entity in entityDailyRealisasi) {
                if (Object.hasOwnProperty.call(entityDailyRealisasi, entity)) {
                    const dataValues = dateLabels.map(date => entityDailyRealisasi[entity][date] || 0);
                    
                    entityDatasets.push({
                        label: entity,
                        data: dataValues,
                        borderColor: entityColors[entityColorIndex % entityColors.length],
                        backgroundColor: 'rgba(0, 0, 0, 0)', // Transparan
                        tension: 0.4,
                        fill: false
                    });
                    entityColorIndex++;
                }
            }

            const entityTrendData = {
                labels: dateLabels,
                datasets: entityDatasets
            };

            const entityTrendConfig = {
                type: 'line',
                data: entityTrendData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Tren Realisasi Harian Antar Entitas'
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Tanggal'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nilai Realisasi'
                            },
                            ticks: {
                                callback: function(value, index, values) {
                                    if (value >= 1000000000) {
                                        return (value / 1000000000) + ' M';
                                    } else if (value >= 1000000) {
                                        return (value / 1000000) + ' Jt';
                                    }
                                    return value;
                                }
                            }
                        }
                    }
                },
            };

            const myEntityTrendChart = new Chart(
                document.getElementById('entityTrendChart'),
                entityTrendConfig
            );


            //pie
            const entityPieData = @json($entityPieData);
            const entityPieLabels = Object.keys(entityPieData);
            const entityPieValues = Object.values(entityPieData);

            const entityPieConfig = {
                type: 'pie',
                data: {
                    labels: entityPieLabels,
                    datasets: [{
                        data: entityPieValues,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 205, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                            'rgba(255, 159, 64, 0.6)',
                            'rgba(201, 203, 207, 0.6)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 205, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(201, 203, 207, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Kontribusi Realisasi kd_akun 01 per Entitas'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    const label = tooltipItem.label || '';
                                    const value = tooltipItem.raw;
                                    const total = tooltipItem.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(2);
                                    return `${label}: ${numberWithCommas(value)} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            };

            const hargaPokokSummaryData = @json($hargaPokokSummaryData);
            const hargaPokokLabels = Object.keys(hargaPokokSummaryData);
            const hargaPokokTarget = hargaPokokLabels.map(label => hargaPokokSummaryData[label]['target']);
            const hargaPokokRealisasi = hargaPokokLabels.map(label => hargaPokokSummaryData[label]['realisasi']);

            const hargaPokokBarData = {
                labels: hargaPokokLabels,
                datasets: [{
                    label: 'Target',
                    data: hargaPokokTarget,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Realisasi',
                    data: hargaPokokRealisasi,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            };

            const hargaPokokBarConfig = {
                type: 'bar',
                data: hargaPokokBarData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Perbandingan Target dan Realisasi Harga Pokok Produksi'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw;
                                    return `${label}: ${numberWithCommas(value)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    if (value >= 1000000000) {
                                        return (value / 1000000000) + ' M';
                                    } else if (value >= 1000000) {
                                        return (value / 1000000) + ' Jt';
                                    }
                                    return value;
                                }
                            }
                        }
                    }
                },
            };
            
            // Data untuk grafik harga pokok per bulan
            const monthlyHargaPokokChartData = @json($monthlyHargaPokokChartData);
            const monthlyHargaPokokLabels = Object.keys(monthlyHargaPokokChartData);
            const monthlyHargaPokokTarget = monthlyHargaPokokLabels.map(label => monthlyHargaPokokChartData[label].target);
            const monthlyHargaPokokRealisasi = monthlyHargaPokokLabels.map(label => monthlyHargaPokokChartData[label].realisasi);

            const monthlyHargaPokokData = {
                labels: monthlyHargaPokokLabels,
                datasets: [
                    {
                        label: 'Target',
                        data: monthlyHargaPokokTarget,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Realisasi',
                        data: monthlyHargaPokokRealisasi,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            };


            const monthlyHargaPokokConfig = {
                type: 'bar',
                data: monthlyHargaPokokData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Total Realisasi Harga Pokok Produksi per Bulan'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw;
                                    return `${label}: ${numberWithCommas(value)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    if (value >= 1000000000) {
                                        return (value / 1000000000) + ' M';
                                    } else if (value >= 1000000) {
                                        return (value / 1000000) + ' Jt';
                                    }
                                    return value;
                                }
                            }
                        }
                    }
                },
            };

            function numberWithCommas(x) {
                return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            };

            const myEntityPieChart = new Chart(
                document.getElementById('entityPieChart'),
                entityPieConfig
            );

            const myHargaPokokBarChart = new Chart(
                document.getElementById('hargaPokokBarChart'),
                hargaPokokBarConfig
            );

            const myMonthlyHargaPokokChart = new Chart(
                document.getElementById('monthlyHargaPokokChart'),
                monthlyHargaPokokConfig
            );
    

        document.addEventListener('DOMContentLoaded', function() {
            // Ambil data dari PHP
            const monthlyProductSales = @json($monthlyProductSales);
            const monthNames = @json($monthNames);
            const endMonth = {{ $endMonth }};

            // Siapkan data untuk Chart.js
            const labels = [];
            const salesCurrentMonth = [];
            const salesPreviousMonth = [];
            const previousMonth = endMonth === 1 ? 12 : endMonth - 1;
            
            // Iterasi melalui data untuk mengisi array
            for (const entity in monthlyProductSales) {
                labels.push(entity);
                const data = monthlyProductSales[entity];
                
                let currentMonthSales = 0;
                let previousMonthSales = 0;
                
                if (data[endMonth]) {
                    currentMonthSales = data[endMonth].total_sales;
                }
                if (data[previousMonth]) {
                    previousMonthSales = data[previousMonth].total_sales;
                }

                salesCurrentMonth.push(currentMonthSales);
                salesPreviousMonth.push(previousMonthSales);
            }
        
            const ctx = document.getElementById('productSalesChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: monthNames[previousMonth - 1],
                            data: salesPreviousMonth,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: monthNames[endMonth - 1],
                            data: salesCurrentMonth,
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Penjualan'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Entitas'
                            }
                        }
                    }
                }
            });  
        
        
                //Beban gaji per bulan
                const monthlySalaryChartData = @json($monthlySalaryChartData);
                const monthlySalaryLabels = Object.keys(monthlySalaryChartData);
                const monthlySalaryTarget = monthlySalaryLabels.map(label => monthlySalaryChartData[label].target);
                const monthlySalaryRealisasi = monthlySalaryLabels.map(label => monthlySalaryChartData[label].realisasi);

                const monthlySalaryCtx = document.getElementById('monthlySalaryChart').getContext('2d');
                const monthlySalaryChart = new Chart(monthlySalaryCtx, {
                    type: 'bar',
                    data: {
                        labels: monthlySalaryLabels,
                        datasets: [{
                            label: 'Target',
                            data: monthlySalaryTarget,
                            backgroundColor: 'rgba(75, 192, 192, 0.5)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Realisasi',
                            data: monthlySalaryRealisasi,
                            backgroundColor: 'rgba(153, 102, 255, 0.5)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // Biaya operasional
                const operationalCostData = @json($operationalCostData);
                const operationalCostLabels = Object.keys(operationalCostData);
                const operationalCostTarget = operationalCostLabels.map(label => operationalCostData[label].target);
                const operationalCostRealisasi = operationalCostLabels.map(label => operationalCostData[label].realisasi);

                const operationalCostCtx = document.getElementById('operationalCostChart').getContext('2d');
                const operationalCostChart = new Chart(operationalCostCtx, {
                    type: 'bar',
                    data: {
                        labels: operationalCostLabels,
                        datasets: [{
                            label: 'Target',
                            data: operationalCostTarget,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Realisasi',
                            data: operationalCostRealisasi,
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // biyaa lain
                // Grafik Biaya Lain-lain per Entitas
                // const otherCostCtx = document.getElementById('otherCostBarChart').getContext('2d');
                // const otherCostEntities = {!! json_encode(array_keys($otherCostData)) !!};
                // const otherCostTargetData = {!! json_encode(array_column($otherCostTableData, 'target')) !!};
                // const otherCostRealisasiData = {!! json_encode(array_column($otherCostTableData, 'realisasi')) !!};
                const otherCostCtx = document.getElementById('otherCostBarChart').getContext('2d');
                const otherCostEntities = {!! json_encode(array_keys($otherCostTableData)) !!};
                const otherCostTargetData = {!! json_encode(array_column($otherCostTableData, 'target')) !!};
                const otherCostRealisasiData = {!! json_encode(array_column($otherCostTableData, 'realisasi')) !!};

                new Chart(otherCostCtx, {
                    type: 'bar',
                    data: {
                        labels: otherCostEntities,
                        datasets: [
                            {
                                label: 'Target',
                                data: otherCostTargetData,
                                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Realisasi',
                                data: otherCostRealisasiData,
                                backgroundColor: 'rgba(255, 99, 132, 0.6)',
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
                                title: {
                                    display: true,
                                    text: 'Nilai (Rp)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Entitas'
                                }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'Perbandingan Target dan Realisasi Biaya Lain-lain per Entitas'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': Rp ' + context.raw.toLocaleString('id-ID');
                                    }
                                }
                            }
                        }
                    }
                });

                //NPM
                // Grafik NPM per Entitas
                // const npmEntitasCtx = document.getElementById('npmEntitasBarChart').getContext('2d');
                // const npmEntitasLabels = {!! json_encode(array_column($npmEntitasTableData, 'entitas')) !!};
                // const npmEntitasTarget = {!! json_encode(array_column($npmEntitasTableData, 'target')) !!};
                // const npmEntitasRealisasi = {!! json_encode(array_column($npmEntitasTableData, 'realisasi')) !!};
                const npmEntitasCtx = document.getElementById('npmEntitasBarChart').getContext('2d');
                const npmEntitasLabels = {!! json_encode(array_keys($npmEntitasTableData)) !!};
                const npmEntitasTarget = {!! json_encode(array_column($npmEntitasTableData, 'target')) !!};
                const npmEntitasRealisasi = {!! json_encode(array_column($npmEntitasTableData, 'realisasi')) !!};


                new Chart(npmEntitasCtx, {
                    type: 'bar',
                    data: {
                        labels: npmEntitasLabels,
                        datasets: [
                            {
                                label: 'Target NPM',
                                data: npmEntitasTarget,
                                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Realisasi NPM',
                                data: npmEntitasRealisasi,
                                backgroundColor: 'rgba(75, 192, 192, 0.6)',
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
                                title: {
                                    display: true,
                                    text: 'Nilai NPM (Rp)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    }
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Entitas'
                                }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'Perbandingan Target dan Realisasi Net Profit Margin per Entitas'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': Rp ' + context.raw.toLocaleString('id-ID');
                                    }
                                }
                            }
                        }
                    }
                });


                // Buat grafik
                const monthlyNpmCtx = document.getElementById('monthlyNpmChart');
                if (monthlyNpmCtx) {
                    new Chart(monthlyNpmCtx, monthlyNpmConfig);
                }

            });

            document.addEventListener('DOMContentLoaded', function() {
            const topProductsData = @json($topProductsData);
            
            // Siapkan data untuk Chart.js
            const labels = topProductsData.map(item => item.nama_produk);
            const data = topProductsData.map(item => item.total_items);
            
            // Warna acak untuk setiap irisan pie
            const backgroundColors = labels.map(() => 'rgba(' + Math.floor(Math.random() * 255) + ', ' + Math.floor(Math.random() * 255) + ', ' + Math.floor(Math.random() * 255) + ', 0.6)');
            const borderColors = labels.map(() => 'rgba(' + Math.floor(Math.random() * 255) + ', ' + Math.floor(Math.random() * 255) + ', ' + Math.floor(Math.random() * 255) + ', 1)');

            const ctx = document.getElementById('topProductsPieChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Produk Terlaris (Berdasarkan Jumlah Item)'
                        }
                    }
                }
            });
        });

        //Beban gaji
        document.addEventListener('DOMContentLoaded', function () {
        const salaryData = @json($salaryData);
        const labels = Object.keys(salaryData);
        const targetValues = labels.map(label => salaryData[label].target);
        const realisasiValues = labels.map(label => salaryData[label].realisasi);

        const ctx = document.getElementById('salaryChart').getContext('2d');
        const salaryChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Target',
                    data: targetValues,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Realisasi',
                    data: realisasiValues,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });

        document.addEventListener('DOMContentLoaded', function () {
            const operationalCostData = @json($monthlyOperationalCostChartData);
            const labels = Object.keys(operationalCostData);
            const targetValues = labels.map(label => operationalCostData[label].target);
            const realisasiValues = labels.map(label => operationalCostData[label].realisasi);

            const ctx = document.getElementById('monthlyOpCostChart').getContext('2d');
            const monthlyOpCostChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Target',
                        data: targetValues,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Realisasi',
                        data: realisasiValues,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });


        document.addEventListener('DOMContentLoaded', function () {
                const marketingCostData = @json($marketingCostData);
                const labels = Object.keys(marketingCostData);
                const targetValues = labels.map(label => marketingCostData[label].target);
                const realisasiValues = labels.map(label => marketingCostData[label].realisasi);

                const ctx = document.getElementById('marketingCostChart').getContext('2d');
                const marketingCostChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Target',
                            data: targetValues,
                            backgroundColor: 'rgba(255, 159, 64, 0.5)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Realisasi',
                            data: realisasiValues,
                            backgroundColor: 'rgba(75, 192, 192, 0.5)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const overallData = @json($monthlyOverallChartData);
            const labels = Object.keys(overallData);
            const targetValues = labels.map(label => overallData[label].target);
            const realisasiValues = labels.map(label => overallData[label].realisasi);

            const ctx = document.getElementById('overallMonthlyChart').getContext('2d');
            const overallMonthlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Target',
                        data: targetValues,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Realisasi',
                        data: realisasiValues,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Pastikan variabel ini ada dan tidak kosong dari controller
            const monthlyOtherCostChartData = @json($monthlyOtherCostChartData);
            
            // Ambil labels (nama bulan) dan data (target & realisasi)
            const labels = Object.keys(monthlyOtherCostChartData);
            const targetValues = labels.map(label => monthlyOtherCostChartData[label].target);
            const realisasiValues = labels.map(label => monthlyOtherCostChartData[label].realisasi);

            const ctx = document.getElementById('monthlyOtherCostChart').getContext('2d');
            
            // Buat konfigurasi grafik
            const monthlyOtherCostConfig = {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Target',
                        data: targetValues,
                        backgroundColor: 'rgba(255, 205, 86, 0.5)',
                        borderColor: 'rgba(255, 205, 86, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Realisasi',
                        data: realisasiValues,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    if (value >= 1000000000) {
                                        return (value / 1000000000) + ' M';
                                    } else if (value >= 1000000) {
                                        return (value / 1000000) + ' Jt';
                                    }
                                    return value;
                                }
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Perbandingan Target dan Realisasi Beban Lain-lain Bulanan'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw;
                                    return `${label}: ${numberWithCommas(value)}`;
                                }
                            }
                        }
                    }
                }
            };

            // Buat instansi grafik
            new Chart(ctx, monthlyOtherCostConfig);
        });

        // Pastikan Chart.js sudah dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('monthlyNpmChart').getContext('2d');

            // Data yang dikirim dari controller Laravel
            const monthlyNpmLabels = @json($monthlyNpmLabels);
            const npmTargetValues = @json($npmTargetValues);
            const npmRealisasiValues = @json($npmRealisasiValues);

            const npmChartData = {
                labels: monthlyNpmLabels,
                datasets: [{
                    label: 'Target NPM',
                    data: npmTargetValues,
                    backgroundColor: '#36A2EB', // Diubah untuk grafik batang
                    borderColor: '#36A2EB',
                    borderWidth: 1,
                }, {
                    label: 'Realisasi NPM',
                    data: npmRealisasiValues,
                    backgroundColor: '#FF6384', // Diubah untuk grafik batang
                    borderColor: '#FF6384',
                    borderWidth: 1,
                }]
            };

            const npmChartConfig = {
                type: 'bar', // Diubah dari 'line' menjadi 'bar'
                data: npmChartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Perbandingan Target dan Realisasi NPM Bulanan'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    // Format nilai menjadi mata uang Rupiah
                                    let value = context.raw;
                                    return label + new Intl.NumberFormat('id-ID', {
                                        style: 'currency',
                                        currency: 'IDR'
                                    }).format(value);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    // Format nilai sumbu Y menjadi mata uang Rupiah
                                    return new Intl.NumberFormat('id-ID', {
                                        style: 'currency',
                                        currency: 'IDR',
                                        notation: 'compact'
                                    }).format(value);
                                }
                            }
                        }
                    }
                },
            };

            // Buat instance grafik
            new Chart(ctx, npmChartConfig);
        });

    </script>
</body>
</html>