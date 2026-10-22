<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan - 2</title>
    
    <!-- CSS untuk PDF -->
    <style>
        /* Reset dan styling utama untuk PDF */
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 15px;
            background: #fff;
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        .container-fluid {
            width: 100%;
            margin: 0 auto;
        }
        
        .text-center {
            text-align: center !important;
        }
        
        .mb-4 {
            margin-bottom: 1rem !important;
        }
        
        h1 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
            page-break-after: avoid;
        }
        
        /* Styling untuk card */
        .card {
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            page-break-inside: avoid;
            background: #fff;
            break-inside: avoid;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 10px;
        }
        
        .card-body {
            padding: 10px;
        }
        
        /* Styling untuk tabel - Diperbaiki untuk PDF */
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 0;
            table-layout: fixed;
        }
        
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 3px 4px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .table th {
            background-color: #34495e !important;
            color: white;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f8f9fa;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        
        .table-dark th {
            background-color: #2c3e50 !important;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        
        .table-info {
            background-color: #d1ecf1;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        
        .table-info th {
            background-color: #17a2b8 !important;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        
        .text-end {
            text-align: right !important;
        }
        
        /* Styling untuk status */
        .achieved {
            color: #27ae60;
            font-weight: bold;
        }
        
        .not-achieved {
            color: #e74c3c;
            font-weight: bold;
        }
        
        /* Styling untuk row dan col */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -5px;
            margin-left: -5px;
        }
        
        .col-md-3, .col-md-4, .col-md-6, .col-md-8, .col-md-12 {
            position: relative;
            width: 100%;
            padding-right: 5px;
            padding-left: 5px;
        }
        
        .col-md-3 { flex: 0 0 25%; max-width: 25%; }
        .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; }
        .col-md-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
        .col-md-12 { flex: 0 0 100%; max-width: 100%; }
        
        /* Styling untuk alert */
        .alert {
            padding: 6px 8px;
            margin-bottom: 8px;
            border: 1px solid transparent;
            border-radius: 4px;
            font-size: 8px;
        }
        
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeaa7;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        
        /* Styling untuk chart container - penting untuk PDF */
        .chart-container {
            position: relative;
            width: 100%;
            height: 200px;
            page-break-inside: avoid;
        }
        
        .chart-container canvas {
            max-width: 100% !important;
            height: auto !important;
        }
        
        /* Styling untuk badge */
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 7px;
            font-weight: bold;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 3px;
        }
        
        .bg-primary {
            background-color: #3498db !important;
            color: white;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        
        .badge-selesai {
            background-color: #27ae60;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        
        .badge-belum-selesai {
            background-color: #e74c3c;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        
        /* Page break untuk section penting */
        .page-break {
            page-break-before: always;
        }
        
        .avoid-break {
            page-break-inside: avoid;
        }
        
        /* Styling untuk additional data section */
        .additional-data-section {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 2px solid #bdc3c7;
        }
        
        /* Styling untuk small text */
        small {
            font-size: 7px;
        }
        
        /* Styling untuk strong tag */
        strong {
            font-weight: bold;
        }
        
        /* Styling untuk pre tag (debug info) */
        pre {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 6px;
            font-size: 7px;
            overflow-x: auto;
            margin-bottom: 0;
        }
        
        /* Tombol Generate PDF */
        .no-print {
            display: none !important;
        }
        
        /* Media query untuk print/PDF */
        @media print {
            body {
                font-size: 9px;
                line-height: 1.2;
                margin: 0;
                padding: 10px;
            }
            
            .card {
                border: 1px solid #000;
                break-inside: avoid;
            }
            
            .table th {
                background-color: #444 !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            .avoid-break {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .page-break {
                page-break-before: always !important;
                break-before: page !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            h1, h2, h3, h4, h5, h6 {
                page-break-after: avoid;
            }
            
            table {
                page-break-inside: avoid;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            /* Optimasi untuk tabel lebar */
            .table-responsive {
                overflow-x: visible;
            }
        }
        
        /* Tambahan untuk memastikan tabel tidak melebar */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Untuk angka dengan format Rupiah */
        .currency {
            font-family: 'Courier New', monospace;
            text-align: right;
        }
    </style>
    
    <!-- Script untuk Chart.js dengan konfigurasi PDF -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Konfigurasi Chart.js untuk PDF
        Chart.defaults.font.family = "'DejaVu Sans', Arial, sans-serif";
        Chart.defaults.font.size = 8;
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
        Chart.defaults.plugins.legend.labels.boxWidth = 8;
        Chart.defaults.plugins.legend.labels.fontSize = 8;
        Chart.defaults.maintainAspectRatio = false;
        
        // Fungsi untuk memastikan chart terrender dengan baik sebelum PDF
        function ensureChartsReady() {
            return new Promise((resolve) => {
                const checkCharts = () => {
                    const charts = Object.values(Chart.instances);
                    const allRendered = charts.every(chart => chart.width > 0 && chart.height > 0);
                    
                    if (allRendered) {
                        resolve();
                    } else {
                        setTimeout(checkCharts, 100);
                    }
                };
                checkCharts();
            });
        }
        
        // Fungsi untuk konversi ke PDF
        function generatePDF() {
            // Tunggu hingga semua chart selesai dirender
            ensureChartsReady().then(() => {
                // Beri waktu tambahan untuk memastikan rendering sempurna
                setTimeout(() => {
                    window.print();
                }, 1000);
            });
        }
        
        // Inisialisasi chart setelah DOM siap
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi semua chart akan dilakukan di sini
            // Kode inisialisasi chart yang ada di HTML akan dipindahkan ke sini
            // untuk konsistensi dan kontrol yang lebih baik
        });
    </script>
</head>
<body>
    <!-- Tombol Generate PDF (akan disembunyikan saat print) -->
    <div class="no-print text-center mb-4">
        <button onclick="generatePDF()" class="btn btn-primary" style="background:#3498db;color:white;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:12px;">
            📄 Generate PDF
        </button>
        <p style="font-size:10px;margin-top:5px;">Pastikan untuk menggunakan pengaturan "Latar Belakang Grafik" saat mencetak</p>
    </div>
    
    <div class="container-fluid">
        <h1 class="text-center mb-4">Laporan Bulanan - 2</h1>
        
        <!-- Konten laporan akan ditempatkan di sini -->
        <!-- Saya akan menampilkan beberapa bagian sebagai contoh -->
        
        <div class="card avoid-break">
            <div class="card-header">
                Laporan Keuangan Target dan Realisasi
                <span class="badge bg-primary">Grafik 1</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th colspan="6" class="text-center">STATISTIK KEUANGAN</th>
                            </tr>
                            <tr>
                                <th>Total Target</th>
                                <th>Total Realisasi</th>
                                <th>Total Gap</th>
                                <th>Persentase</th>
                                <th>Achieve</th>
                                <th>Tidak Achieve</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-end currency">4.480.567.050</td>
                                <td class="text-end currency">5.234.293.558</td>
                                <td class="text-end achieved currency">753.726.508</td>
                                <td class="text-end">116.82%</td>
                                <td class="text-end achieved">8</td>
                                <td class="text-end not-achieved">0</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Konten lainnya tetap sama, tapi dengan class tambahan untuk format angka -->
                <!-- ... -->
                
            </div>
        </div>
        
        <!-- Card-card lainnya akan mengikuti pola yang sama -->
        <!-- ... -->
        
    </div>

    <!-- Script untuk inisialisasi chart -->
    <script>
        // Inisialisasi chart setelah halaman dimuat
        window.onload = function() {
            // Pastikan semua elemen DOM sudah tersedia
            // Kode inisialisasi chart dari HTML asli akan ditempatkan di sini
            // untuk memastikan konsistensi rendering
            
            console.log("Document loaded, initializing charts...");
            
            // Contoh inisialisasi chart (akan diganti dengan kode asli)
            // Chart inisialisasi dari kode asli akan ditempatkan di sini
        };
    </script>
</body>
</html>