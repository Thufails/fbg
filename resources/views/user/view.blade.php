@extends('layouts.app')
@section('title', 'Master Users')
@section('content')
<div class="container-fluid">
    <h4 class="mb-3"><i class="bi bi-people-fill"></i> Daftar Users</h4>
    @include('layouts.notif')
    <!-- Form Pencarian dengan Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('user.view') }}" method="GET" class="mb-0">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="search" class="form-label">Cari User</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Masukkan nama atau email user..." 
                                   value="{{ request('search') }}"
                                   id="searchInput">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i> Cari
                            </button>
                            @if(request('search'))
                            <a href="{{ route('user.view') }}" class="btn btn-secondary">
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
        <a href="{{ route('user.view') }}" class="float-end text-decoration-none">
            <i class="bi bi-x"></i> Hapus pencarian
        </a>
    </div>
    @endif

    <!-- Baris Info Jumlah Data dan Tombol Tambah -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-muted">
            Menampilkan {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} dari {{ $users->total() ?? 0 }} user
        </div>
        <a href="{{ route('user.insert') }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> Tambah User
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th width="60">No</th>
                    <th>Nama User</th>                    
                    <th>Email</th>
                    <th>Level</th>
                    <th>Email Terverifikasi</th>
                    <th>Tanggal Dibuat</th>
                    <th>Tanggal Diupdate</th>
                    <th width="150">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $item)
                <tr>
                    <td class="text-center">{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $item->name }}</td>                    
                    <td>{{ $item->email }}</td>
                    <td class="fw-semibold">{{ $item->level }}</td>
                    <td class="text-center">
                        @if($item->email_verified_at)
                            <span class="badge bg-success">Terverifikasi</span>
                        @else
                            <span class="badge bg-warning text-dark">Belum verifikasi</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->formatted_created_at }}</td>
                    <td class="text-center">{{ $item->formatted_updated_at }}</td>
                    <td class="text-center">
                        {{-- <a href="#" class="btn btn-info btn-sm" title="Detail">
                            <i class="bi bi-eye"></i>
                        </a> --}}
                        {{-- <a href="{{ route('user.edit', $item->id) }}" class="btn btn-warning btn-sm" title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </a> --}}
                        <form action="{{ route('user.destroy', $item->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" 
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?');" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4">
                        @if(request('search'))
                            User dengan kata kunci "{{ request('search') }}" tidak ditemukan
                        @else
                            Tidak ada data user
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $users->withQueryString()->links() }}
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
                window.location.href = "{{ route('user.view') }}";
            }
        });
    }
});
</script>
@endsection