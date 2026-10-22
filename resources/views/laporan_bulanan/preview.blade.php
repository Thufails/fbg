@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Preview Laporan - {{ $kd_laporan }}</h3>
                    <div class="card-tools">
                        <button class="btn btn-success" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    @if(isset($error))
                        <div class="alert alert-danger">
                            {{ $error }}
                        </div>
                    @else
                        @foreach($grafikData as $kd_grafik => $data)
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">
                                        Grafik {{ $kd_grafik }}: {{ $data['title'] ?? 'Judul Tidak Diketahui' }}
                                    </h4>
                                </div>
                                <div class="card-body">
                                    @if(isset($data['error']))
                                        <div class="alert alert-warning">
                                            {{ $data['error'] }}
                                        </div>
                                    @else
                                        @switch($data['type'] ?? 'unknown')
                                            @case('financial_report')
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th>Nama Akun</th>
                                                                <th class="text-right">Target</th>
                                                                <th class="text-right">Realisasi</th>
                                                                <th class="text-right">GAP</th>
                                                                <th class="text-right">%</th>
                                                                <th>Keterangan</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($data['financialData'] as $item)
                                                                <tr>
                                                                    <td>{!! $item['nama_akun'] !!}</td>
                                                                    <td class="text-right">{{ number_format($item['target'], 0, ',', '.') }}</td>
                                                                    <td class="text-right">{{ number_format($item['realisasi'], 0, ',', '.') }}</td>
                                                                    <td class="text-right {{ $item['gap'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                                        {{ number_format($item['gap'], 0, ',', '.') }}
                                                                    </td>
                                                                    <td class="text-right">{{ number_format($item['percentage'], 2) }}%</td>
                                                                    <td class="{{ $item['keterangan'] == 'ACHIEVE' ? 'text-success' : 'text-danger' }}">
                                                                        {{ $item['keterangan'] }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                @break

                                            @case('daily_analysis')
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <h5>Data Harian per Bulan</h5>
                                                        <canvas id="chartDataHarian_{{ $kd_grafik }}" height="300"></canvas>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mt-4">
                                                    <div class="col-md-6">
                                                        <h5>Perbandingan Total per Bulan</h5>
                                                        <canvas id="chartPerbandinganBulanan_{{ $kd_grafik }}" height="250"></canvas>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5>Trend Harian</h5>
                                                        <canvas id="chartTrendHarian_{{ $kd_grafik }}" height="250"></canvas>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mt-4">
                                                    <div class="col-12">
                                                        <h5>Statistik Bulanan</h5>
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered table-sm">
                                                                <thead class="thead-light">
                                                                    <tr>
                                                                        <th>Bulan</th>
                                                                        <th class="text-right">Total</th>
                                                                        <th class="text-right">Rata-rata</th>
                                                                        <th class="text-right">Maksimum</th>
                                                                        <th class="text-right">Minimum</th>
                                                                        <th class="text-right">Hari Berdata</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($data['monthlyStats'] as $month => $stats)
                                                                        <tr>
                                                                            <td><strong>{{ $month }}</strong></td>
                                                                            <td class="text-right">{{ number_format($stats['total'], 0, ',', '.') }}</td>
                                                                            <td class="text-right">{{ number_format($stats['average'], 0, ',', '.') }}</td>
                                                                            <td class="text-right">{{ number_format($stats['max'], 0, ',', '.') }}</td>
                                                                            <td class="text-right">{{ number_format($stats['min'], 0, ',', '.') }}</td>
                                                                            <td class="text-right">{{ $stats['days_with_data'] }} hari</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                @break

                                            @case('daily_revenue')
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <h5>Grafik Pendapatan Harian</h5>
                                                        <canvas id="chartPendapatanHarian_{{ $kd_grafik }}" height="300"></canvas>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <h5>Ringkasan</h5>
                                                        <table class="table table-bordered">
                                                            <tr>
                                                                <th>Total Target</th>
                                                                <td class="text-right">{{ number_format($data['totalTarget'], 0, ',', '.') }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Total Realisasi</th>
                                                                <td class="text-right">{{ number_format($data['totalRealisasi'], 0, ',', '.') }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Selisih</th>
                                                                <td class="text-right {{ $data['totalGap'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                                    {{ number_format($data['totalGap'], 0, ',', '.') }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Persentase</th>
                                                                <td class="text-right">{{ number_format($data['totalPercentage'], 2) }}%</td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                                @break

                                            @default
                                                <div class="alert alert-info">
                                                    Tipe grafik "{{ $data['type'] ?? 'unknown' }}" ditampilkan dalam format sederhana.
                                                    <pre class="mt-2">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                        @endswitch

                                        @if(isset($data['analisis_text']))
                                            <div class="mt-3">
                                                <h5>Analisis:</h5>
                                                <div class="analisis-text p-3 bg-light rounded">
                                                    {!! nl2br(e($data['analisis_text'])) !!}
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Object untuk menyimpan instance chart
const chartInstances = {};

$(document).ready(function() {
    console.log('Initializing charts...');
    
    // Inisialisasi chart untuk setiap grafik
    @foreach($grafikData as $kd_grafik => $data)
        @if(isset($data['type']) && $data['type'] == 'daily_analysis')
            console.log('Initializing daily analysis chart {{ $kd_grafik }}');
            initializeDailyAnalysisCharts('{{ $kd_grafik }}', {!! json_encode($data) !!});
        @endif
        
        @if(isset($data['type']) && $data['type'] == 'daily_revenue')
            console.log('Initializing daily revenue chart {{ $kd_grafik }}');
            initializeDailyRevenueChart('{{ $kd_grafik }}', {!! json_encode($data) !!});
        @endif
    @endforeach
});

function initializeDailyAnalysisCharts(kd_grafik, data) {
    console.log('Initializing daily analysis charts for:', kd_grafik, data);
    
    // Pastikan data ada sebelum membuat chart
    if (!data.chartData || !data.monthlyTotals) {
        console.error('Data tidak valid untuk grafik', kd_grafik);
        return;
    }

    // Hapus chart lama jika ada
    if (chartInstances['dataHarian_' + kd_grafik]) {
        chartInstances['dataHarian_' + kd_grafik].destroy();
    }
    if (chartInstances['perbandinganBulanan_' + kd_grafik]) {
        chartInstances['perbandinganBulanan_' + kd_grafik].destroy();
    }
    if (chartInstances['trendHarian_' + kd_grafik]) {
        chartInstances['trendHarian_' + kd_grafik].destroy();
    }

    // Chart Data Harian
    const ctxHarian = document.getElementById('chartDataHarian_' + kd_grafik);
    if (ctxHarian) {
        try {
            chartInstances['dataHarian_' + kd_grafik] = new Chart(ctxHarian, {
                type: 'line',
                data: {
                    labels: Array.from({length: 31}, (_, i) => 'Hari ' + (i + 1)),
                    datasets: Object.keys(data.chartData).map((month, index) => ({
                        label: month,
                        data: data.chartData[month],
                        borderColor: getColor(index),
                        backgroundColor: getColor(index) + '20',
                        tension: 0.4,
                        fill: true
                    }))
                },
                options: getChartOptions('Data Harian per Bulan')
            });
            console.log('Chart data harian created for:', kd_grafik);
        } catch (error) {
            console.error('Error membuat chart data harian:', error);
        }
    }

    // Chart Perbandingan Bulanan
    const ctxBulanan = document.getElementById('chartPerbandinganBulanan_' + kd_grafik);
    if (ctxBulanan) {
        try {
            chartInstances['perbandinganBulanan_' + kd_grafik] = new Chart(ctxBulanan, {
                type: 'bar',
                data: {
                    labels: Object.keys(data.monthlyTotals),
                    datasets: [{
                        label: 'Total per Bulan',
                        data: Object.values(data.monthlyTotals),
                        backgroundColor: Object.keys(data.monthlyTotals).map((_, i) => getColor(i)),
                        borderColor: Object.keys(data.monthlyTotals).map((_, i) => getColor(i)),
                        borderWidth: 1
                    }]
                },
                options: getChartOptions('Perbandingan Total per Bulan')
            });
            console.log('Chart perbandingan bulanan created for:', kd_grafik);
        } catch (error) {
            console.error('Error membuat chart perbandingan bulanan:', error);
        }
    }

    // Chart Trend Harian
    const ctxTrend = document.getElementById('chartTrendHarian_' + kd_grafik);
    if (ctxTrend && data.allDailyData && data.allDailyData.length > 0) {
        try {
            const trendData = data.allDailyData.slice(0, 30); // Batasi data untuk kejelasan
            chartInstances['trendHarian_' + kd_grafik] = new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: trendData.map(item => item.month + ' ' + item.day),
                    datasets: [{
                        label: 'Nilai Harian',
                        data: trendData.map(item => item.value),
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 3
                    }]
                },
                options: getChartOptions('Trend Harian')
            });
            console.log('Chart trend harian created for:', kd_grafik);
        } catch (error) {
            console.error('Error membuat chart trend harian:', error);
        }
    }
}

function initializeDailyRevenueChart(kd_grafik, data) {
    console.log('Initializing daily revenue chart for:', kd_grafik, data);
    
    // Hapus chart lama jika ada
    if (chartInstances['pendapatanHarian_' + kd_grafik]) {
        chartInstances['pendapatanHarian_' + kd_grafik].destroy();
    }

    const ctx = document.getElementById('chartPendapatanHarian_' + kd_grafik);
    if (ctx && data.labels && data.targetData && data.realisasiData) {
        try {
            chartInstances['pendapatanHarian_' + kd_grafik] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Target',
                            data: data.targetData,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Realisasi',
                            data: data.realisasiData,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: getChartOptions('Pendapatan Harian')
            });
            console.log('Chart pendapatan harian created for:', kd_grafik);
        } catch (error) {
            console.error('Error membuat chart pendapatan harian:', error);
        }
    } else {
        console.error('Data tidak valid untuk chart pendapatan harian:', kd_grafik);
    }
}

function getChartOptions(title) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                position: 'top',
                labels: {
                    font: {
                        size: 12
                    }
                }
            },
            title: { 
                display: true, 
                text: title,
                font: { 
                    size: 16,
                    weight: 'bold'
                },
                padding: 20
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('id-ID', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 2
                            }).format(context.parsed.y);
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000000) return (value / 1000000000).toFixed(1) + ' M';
                        if (value >= 1000000) return (value / 1000000).toFixed(1) + ' Jt';
                        if (value >= 1000) return (value / 1000).toFixed(1) + ' K';
                        return value;
                    },
                    font: {
                        size: 11
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        size: 11
                    }
                }
            }
        },
        animation: {
            duration: 1000,
            easing: 'easeOutQuart'
        }
    };
}

function getColor(index) {
    const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40',
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
    ];
    return colors[index % colors.length];
}

// Clean up charts when page is unloaded
window.addEventListener('beforeunload', function() {
    Object.values(chartInstances).forEach(chart => {
        if (chart && typeof chart.destroy === 'function') {
            chart.destroy();
        }
    });
});
</script>
@endsection

@section('styles')
<style>
.card {
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
.card-title {
    margin-bottom: 0;
    font-weight: 600;
}
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}
.text-success { color: #28a745 !important; }
.text-danger { color: #dc3545 !important; }
.analisis-text {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 4px solid #007bff;
    white-space: pre-wrap;
}
canvas {
    display: block;
    max-width: 100%;
}
</style>
@endsection