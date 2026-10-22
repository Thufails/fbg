@extends('layouts.app')
@section('title','Master Produk')
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-box-seam"></i> Daftar Produk</h4>
  @include('layouts.notif')
  <!-- Form Pencarian dengan Card -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form action="{{ route('produk.view') }}" method="GET" class="mb-0">
        <div class="row g-3 align-items-end">
          <div class="col-md-8">
            <label for="search" class="form-label">Cari Produk</label>
            <div class="input-group">
              <input type="text" name="search" class="form-control" 
                     placeholder="Masukkan nama produk..." 
                     value="{{ request('search') }}"
                     id="searchInput">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Cari
              </button>
              @if(request('search'))
              <a href="{{ route('produk.view') }}" class="btn btn-secondary">
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
    <a href="{{ route('produk.view') }}" class="float-end text-decoration-none">
      <i class="bi bi-x"></i> Hapus pencarian
    </a>
  </div>
  @endif

  <!-- Baris Info Jumlah Data dan Tombol Tambah -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted">
      Menampilkan {{ $produk->firstItem() ?? 0 }} - {{ $produk->lastItem() ?? 0 }} dari {{ $produk->total() ?? 0 }} produk
    </div>
    <a href="{{ route('produk.insert') }}" class="btn btn-success">
      <i class="bi bi-plus-circle me-1"></i> Tambah Produk
    </a>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th width="60">No</th>
          <th>Kode</th>
          <th>Nama</th>
          <th>Harga</th>
          <th>Keterangan</th>
          <th width="120">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($produk as $item)
        <tr>
          <td class="text-center">{{ ($produk->currentPage() - 1) * $produk->perPage() + $loop->iteration }}</td>
          <td>{{ $item->kd_produk }}</td>
          <td>{{ $item->nama_produk }}</td>
          <td>Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
          <td>{{ $item->keterangan }}</td>
          <td class="text-center">
            <a href="{{ route('produk.edit', $item->kd_produk) }}" class="btn btn-warning btn-sm" title="Edit">
              <i class="bi bi-pencil-square"></i>
            </a>
            <form action="{{ route('produk.destroy', $item->kd_produk) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm" 
                      onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');" title="Hapus">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="text-center py-4">
            @if(request('search'))
              Produk dengan nama "{{ request('search') }}" tidak ditemukan
            @else
              Tidak ada data produk
            @endif
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  @if($produk->hasPages())
  <div class="d-flex justify-content-center mt-4">
    {{ $produk->withQueryString()->links() }}
  </div>
  @endif
</div>

<script>
// Auto focus ke input pencarian
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.focus();
        
        // Clear pencarian dengan tombol Esc
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Escape') {
                window.location.href = "{{ route('produk.view') }}";
            }
        });
    }
});
</script>
@endsection