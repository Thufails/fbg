@extends('layouts.app')
@section('title','Daftar Tugas')
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-list-task"></i> Data Tugas</h4>
  @include('layouts.notif')
  <!-- Form Pencarian dengan Card -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form action="{{ route('tugas.view') }}" method="GET" class="mb-0">
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label for="start_date" class="form-label">Tanggal Mulai</label>
            <input type="date" name="start_date" class="form-control" 
                   value="{{ request('start_date') }}" id="start_date">
          </div>
          <div class="col-md-4">
            <label for="end_date" class="form-label">Tanggal Selesai</label>
            <input type="date" name="end_date" class="form-control" 
                   value="{{ request('end_date') }}" id="end_date">
          </div>
          <div class="col-md-4 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-search"></i> Cari
            </button>
            <a href="{{ route('issue.view') }}" class="btn btn-secondary">
              <i class="bi bi-arrow-counterclockwise"></i> Reset
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted">
      Menampilkan {{ $tugas->firstItem() ?? 0 }} - {{ $tugas->lastItem() ?? 0 }} dari {{ $tugas->total() ?? 0 }} tugas
    </div>
    <a href="{{ route('tugas.insert') }}" class="btn btn-success">
      <i class="bi bi-plus-circle me-1"></i> Tambah Tugas
    </a>
  </div>
  
  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th width="60">No</th>
          <th>Tanggal</th>
          <th>Deskripsi Tugas</th>
          <th>Bulan</th>
          <th>Tahun</th>
          <th>Status</th>
          <th>User</th>
          <th width="120">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($tugas as $item)
        <tr>
          <td class="text-center">{{ $loop->iteration + ($tugas->currentPage() - 1) * $tugas->perPage() }}</td>
          <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
          <td>{{ $item->uraian }}</td>
          <td>{{ $item->bulan }}</td>
          <td>{{ $item->tahun }}</td>
          <td>
            @if($item->status == 'Plan')
              <span class="badge bg-secondary">{{ $item->status }}</span>
            @elseif($item->status == 'Proses')
              <span class="badge bg-warning text-dark">{{ $item->status }}</span>
            @else
              <span class="badge bg-success">{{ $item->status }}</span>
            @endif
          </td>
          <td>{{ $item->name }}</td>
          <td class="text-center">
            <a href="{{ route('tugas.edit', $item->kd_tugas) }}" class="btn btn-warning btn-sm" title="Edit">
              <i class="bi bi-pencil-square"></i>
            </a>
            <form action="{{ route('tugas.destroy', $item->kd_tugas) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm" 
                      onclick="return confirm('Apakah Anda yakin ingin menghapus tugas ini?');" title="Hapus">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" class="text-center py-4">Tidak ada data tugas ditemukan</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Paginasi -->
  <div class="d-flex justify-content-center mt-4">
    {{ $tugas->withQueryString()->links() }}
  </div>
</div>

<script>
// Validasi tanggal di client side
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    if (startDate && endDate) {
        startDate.addEventListener('change', function() {
            if (endDate.value && this.value > endDate.value) {
                alert('Tanggal mulai tidak boleh setelah tanggal selesai');
                this.value = '';
            }
        });
        
        endDate.addEventListener('change', function() {
            if (startDate.value && this.value < startDate.value) {
                alert('Tanggal selesai tidak boleh sebelum tanggal mulai');
                this.value = '';
            }
        });
    }
});
</script>
@endsection