@extends('layouts.app')
@section('title', 'Daftar Detail Realisasi')
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-check-circle"></i> Data Detail Realisasi</h4>
  @include('layouts.notif')
  <!-- Search Form -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form action="{{ route('detailRealisasi.view') }}" method="GET" class="mb-0">
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
              @foreach($targetDetailList ?? [] as $targetDetail)
                <option value="{{ $targetDetail->kd_detail_akun }}" {{ request('kd_detail_akun') == $targetDetail->kd_detail_akun ? 'selected' : '' }}>
                  {{ $targetDetail->nama_akun }} - {{ $targetDetail->nama_detail_akun }}
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
            <a href="{{ route('detailRealisasi.view') }}" class="btn btn-secondary w-100 mt-2">
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
      Menampilkan {{ $detailRealisasi->firstItem() ?? 0 }} - {{ $detailRealisasi->lastItem() ?? 0 }} dari {{ $detailRealisasi->total() ?? 0 }} Realisasi
    </div>
    <div>
      <button id="bulkDeleteBtn" class="btn btn-danger me-2" disabled>
        <i class="bi bi-trash me-1"></i> Hapus yang Dipilih
      </button>
      <a href="{{ route('detailRealisasi.select') }}" class="btn btn-success">
        <i class="bi bi-plus-circle me-1"></i> Tambah Realisasi
      </a>
    </div>
  </div>

  <!-- Data Table -->
  <div class="table-responsive">
    <form id="bulkDeleteForm" action="{{ route('detailRealisasi.bulkDestroy') }}" method="POST">
      @csrf
      @method('DELETE')
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark text-center">
          <tr>
            <th width="50">
              <input type="checkbox" id="checkAll" class="form-check-input">
            </th>
            <th width="60">No</th>
            <th>Tanggal</th>
            <th>Entitas</th>
            <th>Detail Akun</th>
            <th>Nilai Realisasi</th>
            <th>Hari</th>
            <th>Bulan</th>
            <th>Tahun</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($detailRealisasi as $item)
          <tr>
            <td class="text-center">
              <input type="checkbox" name="selected_ids[]" value="{{ $item->kd_detail_realisasi }}" class="form-check-input target-checkbox">
            </td>
            <td class="text-center">{{ ($detailRealisasi->currentPage() - 1) * $detailRealisasi->perPage() + $loop->iteration }}</td>
            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
            <td>{{ $item->nama_entitas ?? '-' }}</td>
            <td>{{ $item->nama_detail_akun }}</td>
            <td class="text-end">{{ number_format($item->nilai, 0, ',', '.') }}</td>
            <td>{{ $item->hari }}</td>
            <td>{{ $item->bulan }}</td>
            <td>{{ $item->tahun }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="text-center py-4">
              @if(request('start_date') || request('end_date') || request('kd_detail_akun') || request('kd_entitas'))
                Tidak ditemukan realisasi dengan filter yang dipilih
              @else
                Tidak ada data realisasi
              @endif
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </form>
  </div>

  <!-- Pagination -->
  @if($detailRealisasi->hasPages())
  <div class="d-flex justify-content-center mt-4">
    {{ $detailRealisasi->withQueryString()->links() }}
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
    
    if (checkedCount > 0 && confirm(`Apakah Anda yakin ingin menghapus ${checkedCount} realisasi yang dipilih?`)) {
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