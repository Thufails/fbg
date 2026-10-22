@extends('layouts.app')
@section('title', 'Daftar Target Detail')
@section('content')
<div class="container-fluid">
    <h4 class="mb-3"><i class="bi bi-search"></i> Data Target Detail</h4>
    @include('layouts.notif')
    
    <!-- SUMMARY CARD -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">
                                Total Target Detail 
                                @if(isset($namaAkunDisplay) && !empty($namaAkunDisplay))
                                    <small class="d-block">{{ $namaAkunDisplay }}</small>
                                @endif
                            </h6>
                            <h4 class="mb-0">Rp {{ number_format($totalNilai ?? 0, 0, ',', '.') }}</h4>
                        </div>
                        <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                    </div>
                    <small class="opacity-75">
                        @if(request('start_date') || request('end_date') || request('kd_detail_akun') || request('kd_entitas'))
                            Hasil filter
                        @else
                            Semua data (tahun {{ date('Y') }})
                        @endif
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Jumlah Record</h6>
                            <h4 class="mb-0">{{ number_format($totalRecord ?? 0, 0, ',', '.') }}</h4>
                        </div>
                        <i class="bi bi-list-check fs-1 opacity-50"></i>
                    </div>
                    <small class="opacity-75">
                        Data ditampilkan: {{ $detailTarget->count() }} dari {{ $detailTarget->total() }}
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Rata-rata per Record</h6>
                            <h4 class="mb-0">
                                Rp {{ number_format($rataRata ?? 0, 0, ',', '.') }}
                            </h4>
                        </div>
                        <i class="bi bi-graph-up fs-1 opacity-50"></i>
                    </div>
                    <small class="opacity-75">
                        Berdasarkan data yang difilter
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('detailTarget.view') }}" method="POST" class="mb-0">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label for="start_date" class="form-label">Tanggal Awal</label>
                        <input type="date" name="start_date" class="form-control" 
                            value="{{ request('start_date') }}" id="start_date">
                    </div>
                    <div class="col-md-2">
                        <label for="end_date" class="form-label">Tanggal Akhir</label>
                        <input type="date" name="end_date" class="form-control" 
                            value="{{ request('end_date') }}" id="end_date">
                    </div>
                    <div class="col-md-2">
                        <label for="kd_detail_akun" class="form-label">Detail Akun</label>
                        <select name="kd_detail_akun" class="form-select" id="kd_detail_akun">
                            <option value="">Semua Detail Akun</option>
                            @foreach($detailAkunList ?? [] as $detailAkun)
                                <option value="{{ $detailAkun->kd_detail_akun }}" {{ request('kd_detail_akun') == $detailAkun->kd_detail_akun ? 'selected' : '' }}>
                                    {{ $detailAkun->kd_detail_akun }} - {{ $detailAkun->nama_akun }} - {{ $detailAkun->nama_detail_akun }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="kd_entitas" class="form-label">Entitas</label>
                        <select name="kd_entitas" class="form-select" id="kd_entitas">
                            <option value="">Semua Entitas</option>
                            @foreach($entitasList ?? [] as $entitas)
                                <option value="{{ $entitas->kd_entitas }}" {{ request('kd_entitas') == $entitas->kd_entitas ? 'selected' : '' }}>
                                    {{ $entitas->kd_entitas }} - {{ $entitas->nama_entitas }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        @if(request('start_date') || request('end_date') || request('kd_detail_akun') || request('kd_entitas'))
                        <a href="{{ route('detailTarget.view') }}" class="btn btn-secondary w-100 mt-2">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-muted">
            Menampilkan {{ $detailTarget->firstItem() ?? 0 }} - {{ $detailTarget->lastItem() ?? 0 }} dari {{ $detailTarget->total() ?? 0 }} Target Detail
        </div>
        <div>
            <button id="bulkDeleteBtn" class="btn btn-danger me-2" disabled>
                <i class="bi bi-trash me-1"></i> Hapus yang Dipilih
            </button>
            <a href="{{ route('detailTarget.select') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i> Tambah Target Detail
            </a>
        </div>
    </div>

    <!-- Data Table -->
    <div class="table-responsive">
        <form id="bulkDeleteForm" action="{{ route('detailTarget.bulkDestroy') }}" method="POST">
            @csrf
            @method('DELETE')
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="checkAll" class="form-check-input">
                        </th>
                        <th width="60">No</th>
                        <th>Tanggal Masehi</th>
                        <th>Tanggal Hijriah</th>
                        <th>Entitas</th>
                        <th>Akun</th>
                        <th>Detail Akun</th>
                        <th>Nilai Target</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($detailTarget as $item)
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" name="selected_ids[]" value="{{ $item->kd_target_detail }}" class="form-check-input target-checkbox">
                        </td>
                        <td class="text-center">{{ ($detailTarget->currentPage() - 1) * $detailTarget->perPage() + $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                        <td class="text-center">
                            @if(isset($item->tanggal_hijriah) && !empty($item->tanggal_hijriah))
                                {{ $item->tanggal_hijriah }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $item->nama_entitas ?? '-' }}</td>
                        <td>{{ $item->nama_akun }}</td>
                        <td>{{ $item->nama_detail_akun ?? '-' }}</td>
                        <td class="text-end">{{ number_format($item->nilai, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            @if(request('start_date') || request('end_date') || request('kd_detail_akun') || request('kd_entitas'))
                                Tidak ditemukan target detail dengan filter yang dipilih
                            @else
                                Tidak ada data target detail
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </form>
    </div>

    <!-- Pagination -->
    @if($detailTarget->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $detailTarget->appends([
            'start_date' => request('start_date'),
            'end_date' => request('end_date'),
            'kd_detail_akun' => request('kd_detail_akun'),
            'kd_entitas' => request('kd_entitas')
        ])->links() }}
    </div>
    @endif
</div>

<style>
    .table {
        font-size: 0.9rem;
    }
    .table-dark {
        background-color: #212529;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    .card {
        border-radius: 0.5rem;
        border: 1px solid #dee2e6;
    }
    .text-end {
        text-align: right !important;
    }
    .form-check-input {
        cursor: pointer;
        margin: 0 auto;
        display: block;
    }
    .summary-card {
        transition: transform 0.2s;
    }
    .summary-card:hover {
        transform: translateY(-5px);
    }
    .opacity-50 {
        opacity: 0.5;
    }
    .opacity-75 {
        opacity: 0.75;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.target-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');

    // Check All functionality
    checkAll.addEventListener('change', function() {
        const isChecked = this.checked;
        checkboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateBulkActionButtons();
    });

    // Individual checkbox functionality
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!this.checked && checkAll.checked) {
                checkAll.checked = false;
            }
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkAll.checked = allChecked;
            updateBulkActionButtons();
        });
    });

    function updateBulkActionButtons() {
        const checkedCount = document.querySelectorAll('.target-checkbox:checked').length;
        bulkDeleteBtn.disabled = checkedCount === 0;
        
        bulkDeleteBtn.innerHTML = checkedCount > 0 
            ? `<i class="bi bi-trash me-1"></i> Hapus (${checkedCount})` 
            : `<i class="bi bi-trash me-1"></i> Hapus yang Dipilih`;
    }

    // Bulk delete button click handler
    bulkDeleteBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const checkedCount = document.querySelectorAll('.target-checkbox:checked').length;
        
        if (checkedCount > 0 && confirm(`Apakah Anda yakin ingin menghapus ${checkedCount} target detail yang dipilih?`)) {
            // Show loading state
            bulkDeleteBtn.disabled = true;
            bulkDeleteBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menghapus...`;
            
            // Submit form
            bulkDeleteForm.submit();
        }
    });
});
</script>
@endsection