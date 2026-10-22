@extends('layouts.app')
@section('title', 'Daftar Komplain')
@section('nav')
@include('layouts.nav')
@endsection
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-megaphone-fill"></i> Data Komplain</h4>
  @include('layouts.notif')
  <!-- Form Pencarian dengan Card - Hanya Tanggal -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form action="{{ route('komplain.view') }}" method="GET" class="mb-0">
        <div class="row g-3 align-items-end">
          <div class="col-md-5">
            <label for="start_date" class="form-label">Tanggal Awal</label>
            <input type="date" name="start_date" class="form-control" 
                   value="{{ request('start_date') }}" id="start_date">
          </div>
          <div class="col-md-5">
            <label for="end_date" class="form-label">Tanggal Akhir</label>
            <input type="date" name="end_date" class="form-control" 
                   value="{{ request('end_date') }}" id="end_date">
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-funnel me-1"></i> Filter
            </button>
            @if(request('start_date') || request('end_date'))
            <a href="{{ route('komplain.view') }}" class="btn btn-secondary w-100 mt-2">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </a>
            @endif
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Info Hasil Filter -->
  @if(request('start_date') || request('end_date'))
  <div class="alert alert-info mb-3">
    <i class="bi bi-info-circle"></i> Menampilkan komplain dari tanggal 
    <strong>{{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d-m-Y') : 'Awal' }}</strong> 
    sampai 
    <strong>{{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d-m-Y') : 'Akhir' }}</strong>
    <a href="{{ route('komplain.view') }}" class="float-end text-decoration-none">
      <i class="bi bi-x"></i> Hapus filter
    </a>
  </div>
  @endif

  <!-- Baris Info Jumlah Data dan Tombol Tambah -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted">
      Menampilkan {{ $komplain->firstItem() ?? 0 }} - {{ $komplain->lastItem() ?? 0 }} dari {{ $komplain->total() ?? 0 }} komplain
    </div>
    <a href="{{ route('komplain.insert') }}" class="btn btn-success">
      <i class="bi bi-plus-circle me-1"></i> Tambah Komplain
    </a>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th width="60">No</th>
          <th>Tanggal</th>
          <th>Deskripsi Komplain</th>
          <th>Bulan</th>
          <th>Tahun</th>
          <th>Status</th>
          <th>User</th>
          <th width="150">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($komplain as $item)
        <tr>
          <td class="text-center">{{ ($komplain->currentPage() - 1) * $komplain->perPage() + $loop->iteration }}</td>
          <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
          <td>{{ Str::limit($item->uraian, 50) }}</td>
          <td>{{ $item->bulan }}</td>
          <td>{{ $item->tahun }}</td>
          <td class="text-center">
            @if($item->status == 'Open')
              <span class="badge bg-danger"><i class="bi bi-exclamation-circle"></i> Open</span>
            @elseif($item->status == 'Proses')
              <span class="badge bg-warning text-dark"><i class="bi bi-arrow-repeat"></i> Proses</span>
            @else
              <span class="badge bg-success"><i class="bi bi-check-circle"></i> Closed</span>
            @endif
          </td>
          <td>{{ $item->name }}</td>
          <td class="text-center">
            <a href="{{ route('komplain.edit', $item->kd_komplain) }}" class="btn btn-warning btn-sm" title="Edit">
              <i class="bi bi-pencil-square"></i>
            </a>
            <form action="{{ route('komplain.destroy', $item->kd_komplain) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm" 
                      onclick="return confirm('Apakah Anda yakin ingin menghapus komplain ini?');" title="Hapus">
                <i class="bi bi-trash"></i>
              </button>
            </form>
            <a href="{{ route('komplain.detail', $item->kd_komplain) }}" class="btn btn-info btn-sm" title="Detail">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" class="text-center py-4">
            @if(request('start_date') || request('end_date'))
              Tidak ditemukan komplain pada periode yang dipilih
            @else
              Tidak ada data komplain
            @endif
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  @if($komplain->hasPages())
  <div class="d-flex justify-content-center mt-4">
    {{ $komplain->withQueryString()->links() }}
  </div>
  @endif
</div>

<style>
  /* Style untuk tabel */
  .table {
    font-size: 0.9rem;
  }
  
  .table-dark {
    background-color: #212529;
  }
  
  .table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.05);
  }
  
  /* Style untuk badge status */
  .badge.bg-danger { background-color: #dc3545 !important; }
  .badge.bg-warning { background-color: #ffc107 !important; }
  .badge.bg-success { background-color: #198754 !important; }
  
  /* Style untuk tombol aksi */
  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }
  
  /* Style untuk card pencarian */
  .card {
    border-radius: 0.5rem;
    border: 1px solid #dee2e6;
  }
  
  /* Style untuk alert pencarian */
  .alert-info {
    background-color: #e7f8ff;
    border-color: #b6e7ff;
    color: #055160;
  }
</style>

<script>
// Auto focus ke input tanggal awal saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    if (startDateInput) {
        startDateInput.focus();
    }
    
    // Validasi tanggal akhir tidak boleh sebelum tanggal awal
    const endDateInput = document.getElementById('end_date');
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            if (endDateInput.value && new Date(endDateInput.value) < new Date(this.value)) {
                endDateInput.value = this.value;
            }
        });
    }
});
</script>
@endsection