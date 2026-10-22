@extends('layouts.app')
@section('title', 'Daftar Laporan')
@section('nav')
@include('layouts.nav')
@endsection
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-exclamation-triangle-fill"></i> Data Issue</h4>
  @include('layouts.notif')
  <!-- Form Pencarian -->
  <form action="{{ route('issue.view') }}" method="GET" class="mb-4 card card-body">
    <div class="row g-3">
      <div class="col-md-4">
        <label for="start_date" class="form-label">Tanggal Mulai</label>
        <input type="date" name="start_date" class="form-control" 
               value="{{ request('start_date') }}" id="start_date"> <!-- Tambahkan id -->
      </div>
      <div class="col-md-4">
        <label for="end_date" class="form-label">Tanggal Selesai</label>
        <input type="date" name="end_date" class="form-control" 
               value="{{ request('end_date') }}" id="end_date"> <!-- Tambahkan id -->
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

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted">
      Menampilkan {{ $issues->firstItem() }} - {{ $issues->lastItem() }} dari {{ $issues->total() }} data
    </div>
    <a href="{{ route('issue.insert') }}" class="btn btn-success">
      <i class="bi bi-plus-circle"></i> Tambah Issue
    </a>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th width="50">No</th>
          <th>Tanggal</th>
          <th>Deskripsi Masalah</th>
          <th>Bulan</th>
          <th>Tahun</th>
          <th>Status</th>
          <th>User</th>
          <th width="120">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($issues as $item)
        <tr>
          <td class="text-center">{{ $issues->firstItem() + $loop->index }}</td>
          <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
          <td>{{ $item->uraian }}</td>
          <td>{{ $item->bulan }}</td>
          <td>{{ $item->tahun }}</td>
          <td>{{ $item->status }}</td>
          <td>{{ $item->name }}</td>
          <td class="text-center">
            <a href="{{ route('issue.edit', $item->kd_issue) }}" class="btn btn-warning btn-sm" title="Edit">
              <i class="bi bi-pencil-square"></i>
            </a>
            <form action="{{ route('issue.destroy', $item->kd_issue) }}" method="POST" style="display:inline;">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm" 
                      onclick="return confirm('Apakah Anda yakin ingin menghapus issue ini?');" title="Hapus">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" class="text-center">Tidak ada data ditemukan</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="d-flex justify-content-center mt-3">
    {{ $issues->withQueryString()->links() }}
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