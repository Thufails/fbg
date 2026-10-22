@extends('layouts.app')
@section('title','Master Akun')
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-person-lines-fill"></i> Daftar Akun</h4>
  
  <!-- Form Pencarian dengan Card -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form action="{{ route('akun.view') }}" method="GET" class="mb-0">
        <div class="row g-3 align-items-end">
          <div class="col-md-8">
            <label for="search" class="form-label">Cari Akun</label>
            <div class="input-group">
              <input type="text" name="search" class="form-control" 
                     placeholder="Masukkan nama akun..." 
                     value="{{ request('search') }}"
                     id="searchInput">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Cari
              </button>
              @if(request('search'))
              <a href="{{ route('akun.view') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
              </a>
              @endif
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Info Hasil Pencarian -->
  @if(request('search'))
  <div class="alert alert-info mb-3">
    <i class="bi bi-info-circle"></i> Menampilkan hasil pencarian untuk: <strong>"{{ request('search') }}"</strong>
    <a href="{{ route('akun.view') }}" class="float-end text-decoration-none">
      <i class="bi bi-x"></i> Hapus pencarian
    </a>
  </div>
  @endif

  <!-- Baris Info Jumlah Data dan Tombol Tambah -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted">
      Menampilkan {{ $akun->firstItem() ?? 0 }} - {{ $akun->lastItem() ?? 0 }} dari {{ $akun->total() ?? 0 }} akun
    </div>
    <a href="{{ route('akun.insert') }}" class="btn btn-success">
      <i class="bi bi-plus-circle me-1"></i> Tambah Akun
    </a>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th width="60">No</th>
          <th>Kode Akun</th>
          <th>Nama Akun</th>
          <th>Induk</th>
          <th>Keterangan</th>
          <th width="150">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($akun as $item)
        <tr>
          <td class="text-center">{{ ($akun->currentPage() - 1) * $akun->perPage() + $loop->iteration }}</td>
          <td class="fw-semibold">{{ $item->kd_akun }}</td>
          <td>{{ $item->nama_akun }}</td>
          <td class="text-center">
            @if($item->induk)
              <span class="badge bg-info">{{ $item->induk }}</span>
            @else
              <span class="text-muted">-</span>
            @endif
          </td>
          <td>{{ $item->keterangan ?? '-' }}</td>
          <td class="text-center">
            <a href="{{ route('akun.detail', $item->kd_akun) }}" class="btn btn-info btn-sm" title="Detail">
              <i class="bi bi-eye"></i>
            </a>
            <a href="{{ route('akun.edit', $item->kd_akun) }}" class="btn btn-warning btn-sm" title="Edit">
              <i class="bi bi-pencil-square"></i>
            </a>
            <form action="{{ route('akun.destroy', $item->kd_akun) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm" 
                      onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini?');" title="Hapus">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="text-center py-4">
            @if(request('search'))
              Akun dengan nama "{{ request('search') }}" tidak ditemukan
            @else
              Tidak ada data akun
            @endif
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  @if($akun->hasPages())
  <div class="d-flex justify-content-center mt-4">
    {{ $akun->withQueryString()->links() }}
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
  
  /* Style untuk badge */
  .badge.bg-info {
    background-color: #0dcaf0 !important;
    color: #000;
  }
  
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
// Auto focus ke input pencarian
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.focus();
        
        // Clear pencarian dengan tombol Esc
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Escape') {
                window.location.href = "{{ route('akun.view') }}";
            }
        });
    }
});
</script>
@endsection