@extends('layouts.app')
@section('title','Daftar Target')
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-bullseye"></i> Data Target</h4>
  @include('layouts.notif')
  <!-- SUMMARY CARD -->
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
          <small class="opacity-75">
            @if(request('start_date') || request('end_date') || request('kd_akun') || request('kd_entitas'))
              Hasil filter
            @else
              Semua data
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
              <h6 class="card-title mb-1">Jumlah Record {{ $nama_akun }}</h6>
              <h4 class="mb-0">{{ number_format($target->total() ?? 0, 0, ',', '.') }}</h4>
            </div>
            <i class="bi bi-list-check fs-1 opacity-50"></i>
          </div>
          <small class="opacity-75">
            Data ditampilkan: {{ $target->count() }} dari {{ $target->total() }}
          </small>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card bg-success text-white shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-title mb-1">Rata-rata {{ $nama_akun }} per Hari</h6>
              <h4 class="mb-0">
                @php
                  $averagePerDay = ($target->total() > 0 && $totalTarget > 0) 
                    ? round($totalTarget / $target->total(), 2) 
                    : 0;
                @endphp
                Rp {{ number_format($averagePerDay, 0, ',', '.') }}
              </h4>
            </div>
            <i class="bi bi-calendar-day fs-1 opacity-50"></i>
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
      <form action="{{ route('target.view') }}" method="GET" class="mb-0">
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
              @foreach($akunList ?? [] as $akun)
                <option value="{{ $akun->kd_akun }}" {{ request('kd_akun') == $akun->kd_akun ? 'selected' : '' }}>
                  {{ $akun->kd_akun }} - {{ $akun->nama_akun }}
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
            <a href="{{ route('target.view') }}" class="btn btn-secondary w-100 mt-2">
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
      Menampilkan {{ $target->firstItem() ?? 0 }} - {{ $target->lastItem() ?? 0 }} dari {{ $target->total() ?? 0 }} Target
    </div>
    <div>
      <button id="bulkDeleteBtn" class="btn btn-danger me-2" disabled>
        <i class="bi bi-trash me-1"></i> Hapus yang Dipilih
      </button>
      <button id="bulkUpdateBtn" class="btn btn-primary me-2" disabled>
        <i class="bi bi-pencil-square me-1"></i> Update yang Dipilih
      </button>
      <a href="{{ route('target.insert') }}" class="btn btn-success">
        <i class="bi bi-plus-circle me-1"></i> Tambah Target
      </a>
    </div>
  </div>

  <!-- Data Table -->
  <div class="table-responsive">
    <form id="bulkDeleteForm" action="{{ route('target.bulkDestroy') }}" method="POST">
      @csrf
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
            <th>Nilai Target</th>
            <th>Hari</th>
            <th>Bulan</th>
            <th>Tahun</th>
            <th width="150">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($target as $item)
          <tr>
            <td class="text-center">
              <input type="checkbox" name="selected_ids[]" value="{{ $item->kd_target }}" class="form-check-input target-checkbox">
            </td>
            <td class="text-center">{{ ($target->currentPage() - 1) * $target->perPage() + $loop->iteration }}</td>
            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
            <td class="text-center">
              @if(isset($item->tanggal_hijriah) && !empty($item->tanggal_hijriah))
                {{ $item->tanggal_hijriah }}
              @else
                -
              @endif
            </td>
            <td>{{ $item->nama_entitas }}</td>
            <td>{{ $item->nama_akun }}</td>
            <td class="text-end">{{ number_format($item->nilai, 0, ',', '.') }}</td>
            <td>{{ $item->hari }}</td>
            <td>{{ $item->bulan }}</td>
            <td>{{ $item->tahun }}</td>
            <td class="text-center">
              <a href="{{ route('target.detail', $item->kd_target) }}" class="btn btn-info btn-sm" title="Detail">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="11" class="text-center py-4">
              @if(request('start_date') || request('end_date') || request('kd_akun') || request('kd_entitas'))
                Tidak ditemukan target dengan filter yang dipilih
              @else
                Tidak ada data target
              @endif
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </form>
  </div>

  <!-- Pagination -->
  @if($target->hasPages())
  <div class="d-flex justify-content-center mt-4">
    {{ $target->withQueryString()->links() }}
  </div>
  @endif
</div>

<!-- Bulk Update Modal -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="bulkUpdateForm" action="{{ route('target.bulkUpdate') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="bulkUpdateModalLabel">Update Target Terpilih</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="update_nilai" class="form-label">Nilai Target</label>
            <input type="number" class="form-control" id="update_nilai" name="nilai" placeholder="Masukkan nilai target">
          </div>
          <div class="mb-3">
            <label for="update_keterangan" class="form-label">Keterangan (Opsional)</label>
            <textarea class="form-control" id="update_keterangan" name="keterangan" rows="3" placeholder="Masukkan keterangan"></textarea>
          </div>
          <input type="hidden" name="selected_ids" id="selected_ids_input">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <span id="updateButtonText">Update</span>
            <span id="updateSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
          </button>
        </div>
      </form>
    </div>
  </div>
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
  .badge {
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const checkAll = document.getElementById('checkAll');
  const checkboxes = document.querySelectorAll('.target-checkbox');
  const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
  const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
  const bulkDeleteForm = document.getElementById('bulkDeleteForm');
  const bulkUpdateModal = new bootstrap.Modal(document.getElementById('bulkUpdateModal'));
  const bulkUpdateForm = document.getElementById('bulkUpdateForm');
  const selectedIdsInput = document.getElementById('selected_ids_input');
  const updateSpinner = document.getElementById('updateSpinner');
  const updateButtonText = document.getElementById('updateButtonText');

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
      // Update select all checkbox state
      const allChecked = Array.from(checkboxes).every(cb => cb.checked);
      const someChecked = Array.from(checkboxes).some(cb => cb.checked);
      
      checkAll.checked = allChecked;
      checkAll.indeterminate = someChecked && !allChecked;
      
      updateBulkActionButtons();
    });
  });

  function updateBulkActionButtons() {
    const checkedCount = document.querySelectorAll('.target-checkbox:checked').length;
    bulkDeleteBtn.disabled = checkedCount === 0;
    bulkUpdateBtn.disabled = checkedCount === 0;
    
    bulkDeleteBtn.innerHTML = checkedCount > 0 
      ? `<i class="bi bi-trash me-1"></i> Hapus (${checkedCount})` 
      : `<i class="bi bi-trash me-1"></i> Hapus yang Dipilih`;
      
    bulkUpdateBtn.innerHTML = checkedCount > 0 
      ? `<i class="bi bi-pencil-square me-1"></i> Update (${checkedCount})` 
      : `<i class="bi bi-pencil-square me-1"></i> Update yang Dipilih`;
  }

  // Bulk delete button click handler
  bulkDeleteBtn.addEventListener('click', function(e) {
    e.preventDefault();
    const checkedCount = document.querySelectorAll('.target-checkbox:checked').length;
    
    if (checkedCount > 0 && confirm(`Apakah Anda yakin ingin menghapus ${checkedCount} target yang dipilih?`)) {
      // Show loading state
      bulkDeleteBtn.disabled = true;
      bulkDeleteBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menghapus...`;
      
      // Submit form
      bulkDeleteForm.submit();
    }
  });

  // Bulk update button click handler
  bulkUpdateBtn.addEventListener('click', function() {
    const checkedCheckboxes = document.querySelectorAll('.target-checkbox:checked');
    const selectedIds = Array.from(checkedCheckboxes).map(cb => cb.value);
    
    // Set selected IDs in hidden input
    selectedIdsInput.value = JSON.stringify(selectedIds);
    
    // Show modal
    bulkUpdateModal.show();
  });

  // Bulk update form submission
  bulkUpdateForm.addEventListener('submit', function(e) {
    // Show loading state
    updateSpinner.classList.remove('d-none');
    updateButtonText.textContent = 'Memproses...';
    bulkUpdateForm.querySelector('button[type="submit"]').disabled = true;
  });

  // Initial update
  updateBulkActionButtons();
});
</script>
@endsection