@extends('layouts.app')
@section('title', 'Daftar Penjualan')
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-cash-stack"></i> Data Penjualan</h4>
  @include('layouts.notif')
  <!-- Search Form -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form action="{{ route('penjualan.view') }}" method="GET" class="mb-0">
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
          <div class="col-md-4">
            <label for="search" class="form-label">Cari Produk</label>
            <input type="text" name="search" class="form-control" 
                   value="{{ request('search') }}" placeholder="Cari produk...">
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-funnel me-1"></i> Filter
            </button>
            @if(request('start_date') || request('end_date') || request('search') || request('kd_entitas'))
            <a href="{{ route('penjualan.view') }}" class="btn btn-secondary w-100 mt-2">
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
      Menampilkan {{ $penjualan->firstItem() ?? 0 }} - {{ $penjualan->lastItem() ?? 0 }} dari {{ $penjualan->total() ?? 0 }} Penjualan
    </div>
    <div>
      <button id="bulkDeleteBtn" class="btn btn-danger me-2" disabled>
        <i class="bi bi-trash me-1"></i> Hapus yang Dipilih
      </button>
      <a href="{{ route('penjualan.insert') }}" class="btn btn-success me-2">
        <i class="bi bi-plus-circle me-1"></i> Tambah
      </a>
      <a href="{{ route('penjualan.cart') }}" class="btn btn-primary">
        <i class="bi bi-cart me-1"></i> Keranjang
        @if(session('penjualan.cart') && count(session('penjualan.cart')) > 0)
          <span class="badge bg-warning text-dark">{{ count(session('penjualan.cart')) }}</span>
        @endif
      </a>
    </div>
  </div>

  <!-- Data Table -->
  <div class="table-responsive">
    <form id="bulkDeleteForm" action="{{ route('penjualan.bulkDestroy') }}" method="POST">
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
            <th>Produk</th>
            <th>Harga</th>
            <th>Jumlah</th>
            <th>Subtotal</th>
            <th width="120">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($penjualan as $item)
          <tr>
            <td class="text-center">
              <input type="checkbox" name="selected_ids[]" value="{{ $item->kd_penjualan }}" class="form-check-input penjualan-checkbox">
            </td>
            <td class="text-center">{{ ($penjualan->currentPage() - 1) * $penjualan->perPage() + $loop->iteration }}</td>
            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
            <td>{{ $item->nama_entitas ?? '-' }}</td>
            <td>{{ $item->produk->nama_produk ?? '-' }}</td>
            <td class="text-end">{{ number_format($item->harga, 0, ',', '.') }}</td>
            <td class="text-center">{{ $item->jumlah }}</td>
            <td class="text-end">{{ number_format($item->harga * $item->jumlah, 0, ',', '.') }}</td>
            <td class="text-center">
              <a href="{{ route('penjualan.edit', $item->kd_penjualan) }}" class="btn btn-sm btn-warning">
                <i class="bi bi-pencil"></i>
              </a>
              {{-- <form action="{{ route('penjualan.destroy', $item->kd_penjualan) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus?')">
                  <i class="bi bi-trash"></i>
                </button>
              </form> --}}
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="text-center py-4">
              @if(request('start_date') || request('end_date') || request('search') || request('kd_entitas'))
                Tidak ditemukan penjualan dengan filter yang dipilih
              @else
                Tidak ada data penjualan
              @endif
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </form>
  </div>

  <!-- Pagination -->
  @if($penjualan->hasPages())
  <div class="d-flex justify-content-center mt-4">
    {{ $penjualan->withQueryString()->links() }}
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
  const checkboxes = document.querySelectorAll('.penjualan-checkbox');
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
      // Update select all checkbox state
      const allChecked = Array.from(checkboxes).every(cb => cb.checked);
      const someChecked = Array.from(checkboxes).some(cb => cb.checked);
      
      checkAll.checked = allChecked;
      checkAll.indeterminate = someChecked && !allChecked;
      
      updateBulkActionButtons();
    });
  });

  function updateBulkActionButtons() {
    const checkedCount = document.querySelectorAll('.penjualan-checkbox:checked').length;
    bulkDeleteBtn.disabled = checkedCount === 0;
    
    bulkDeleteBtn.innerHTML = checkedCount > 0 
      ? `<i class="bi bi-trash me-1"></i> Hapus (${checkedCount})` 
      : `<i class="bi bi-trash me-1"></i> Hapus yang Dipilih`;
  }

  // Bulk delete button click handler - HANYA SATU EVENT LISTENER
  bulkDeleteBtn.addEventListener('click', function(e) {
    e.preventDefault();
    const checkedCount = document.querySelectorAll('.penjualan-checkbox:checked').length;
    
    if (checkedCount > 0 && confirm(`Apakah Anda yakin ingin menghapus ${checkedCount} penjualan yang dipilih?`)) {
      // Show loading state
      bulkDeleteBtn.disabled = true;
      bulkDeleteBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menghapus...`;
      
      // Submit form dengan AJAX (opsional) atau regular form submission
      // Pilih salah satu metode di bawah ini:
      
      // METODE 1: Regular form submission (non-AJAX)
      bulkDeleteForm.submit();
      
      // METODE 2: AJAX submission (jika ingin menggunakan AJAX)
      /*
      const formData = new FormData(bulkDeleteForm);
      
      fetch(bulkDeleteForm.action, {
          method: 'POST',
          body: formData,
          headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              // Reload halaman jika sukses
              window.location.reload();
          } else {
              alert('Gagal menghapus: ' + data.message);
              bulkDeleteBtn.disabled = false;
              bulkDeleteBtn.innerHTML = `<i class="bi bi-trash me-1"></i> Hapus (${checkedCount})`;
          }
      })
      .catch(error => {
          console.error('Error:', error);
          alert('Terjadi kesalahan saat menghapus data');
          bulkDeleteBtn.disabled = false;
          bulkDeleteBtn.innerHTML = `<i class="bi bi-trash me-1"></i> Hapus (${checkedCount})`;
      });
      */
    }
  });

  // Initial update
  updateBulkActionButtons();
});
</script>
@endsection