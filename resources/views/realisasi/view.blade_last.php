@extends('layouts.app')
@section('title', 'Daftar Realisasi')
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-check-circle"></i> Data Realisasi</h4>
   <!-- SUMMARY CARDS -->
  <div class="row mb-4">
      <div class="col-md-4">
          <div class="card bg-primary text-white shadow-sm">
              <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                      <div>
                          <h6 class="card-title mb-1">Total Target {{ $nama_akun }}</h6>
                          <h4 class="mb-0">Rp {{ number_format($totalTarget ?? 0, 0, ',', '.') }}</h4>
                      </div>
                      <i class="bi bi-bullseye fs-1 opacity-50"></i>
                  </div>
              </div>
          </div>
      </div>
      <div class="col-md-4">
          <div class="card bg-success text-white shadow-sm">
              <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                      <div>
                          <h6 class="card-title mb-1">Total Realisasi {{ $nama_akun }}</h6>
                          <h4 class="mb-0">Rp {{ number_format($totalRealisasi ?? 0, 0, ',', '.') }}</h4>
                      </div>
                      <i class="bi bi-check-circle fs-1 opacity-50"></i>
                  </div>
              </div>
          </div>
      </div>
      <div class="col-md-4">
          <div class="card bg-info text-white shadow-sm">
              <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                      <div>
                          <h6 class="card-title mb-1">Persentase Realisasi {{ $nama_akun }}</h6>
                          <h4 class="mb-0">{{ $persentaseRealisasi ?? 0 }}%</h4>
                      </div>
                      <i class="bi bi-percent fs-1 opacity-50"></i>
                  </div>
                  <div class="progress mt-2 bg-white bg-opacity-25" style="height: 5px;">
                      <div class="progress-bar bg-white" 
                          style="width: {{ min($persentaseRealisasi ?? 0, 100) }}%"></div>
                  </div>
              </div>
          </div>
      </div>
  </div>
  
  <!-- Search Form -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form action="{{ route('realisasi.view') }}" method="GET" class="mb-0">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label for="start_date" class="form-label">Tanggal Awal</label>
            <input type="date" name="start_date" class="form-control" 
                   value="{{ request('start_date') }}" id="start_date">
          </div>
          <div class="col-md-3">
            <label for="end_date" class="form-label">Tanggal Akhir</label>
            <input type="date" name="end_date" class="form-control" 
                   value="{{ request('end_date') }}" id="end_date">
          </div>
          <div class="col-md-2">
            <label for="kd_akun" class="form-label">Akun</label>
            <select name="kd_akun" class="form-select" id="kd_akun">
              <option value="">Semua Akun</option>
              @foreach($akunList ?? [] as $item)
                <option value="{{ $item->kd_akun }}" {{ request('kd_akun') == $item->kd_akun ? 'selected' : '' }}>
                  {{ $item->kd_akun }} - {{ $item->nama_akun }}
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
            @if(request('start_date') || request('end_date') || request('kd_akun') || request('kd_entitas'))
            <a href="{{ route('realisasi.view') }}" class="btn btn-secondary w-100 mt-2">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </a>
            @endif
          </div>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Tambahkan di bagian tombol action -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted">
        Menampilkan {{ $realisasi->firstItem() ?? 0 }} - {{ $realisasi->lastItem() ?? 0 }} dari {{ $realisasi->total() ?? 0 }} Realisasi
    </div>
    <div>
        <button id="bulkDeleteBtn" class="btn btn-danger me-2" disabled>
            <i class="bi bi-trash me-1"></i> Hapus yang Dipilih
        </button>
        <!-- TAMBAHKAN LINK INI -->
        <a href="{{ route('realisasi.inputMassal') }}" class="btn btn-info ms-2">
            <i class="bi bi-collection me-1"></i> Input Massal
        </a>
        <a href="{{ route('realisasi.select') }}" class="btn btn-outline-success ms-2">
            <i class="bi bi-list-ul me-1"></i> Pilih Realisasi
        </a>
    </div>
</div>

  <!-- Data Table -->
  <div class="table-responsive">
    <form id="bulkDeleteForm" action="{{ route('realisasi.bulkDestroy') }}" method="POST">
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
            <th>Kode Akun</th>
            <th>Nama Akun</th>
            <th>Target</th>
            <th>Realisasi</th>
            <th>Keterangan</th>
            <th width="100">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($realisasi as $item)
          <tr>
            <td class="text-center">
              <input type="checkbox" name="selected_ids[]" value="{{ $item->kd_realisasi }}" class="form-check-input target-checkbox">
            </td>
            <td class="text-center">{{ ($realisasi->currentPage() - 1) * $realisasi->perPage() + $loop->iteration }}</td>
            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
            <td>{{ $item->nama_entitas }}</td>
            <td>{{ $item->kd_akun }}</td>
            <td>{{ $item->nama_akun }}</td>
            <td class="text-end">{{ number_format($item->nilai_target, 0, ',', '.') }}</td>
            <td class="text-end">{{ number_format($item->nilai, 0, ',', '.') }}</td>
            <td>{{ $item->keterangan }}</td>
            <td class="text-center">
              <a href="{{ route('realisasi.edit', $item->kd_realisasi) }}" class="btn btn-sm btn-warning" title="Edit Realisasi">
                <i class="bi bi-pencil"></i>
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="10" class="text-center py-4">
              @if(request('start_date') || request('end_date') || request('kd_akun') || request('kd_entitas'))
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
  @if($realisasi->hasPages())
  <div class="d-flex justify-content-center mt-4">
    {{ $realisasi->withQueryString()->links() }}
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
  .harian-item {
    margin-bottom: 10px;
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
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