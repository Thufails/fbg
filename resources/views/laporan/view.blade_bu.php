@extends('layouts.app')
@section('title', 'Daftar Laporan')
@section('nav')
@include('layouts.nav')
@endsection
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-file-earmark-text"></i> Data Laporan</h4>
  @include('layouts.notif')
  <!-- Form Pencarian dengan Card - Hanya Tanggal -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form action="{{ route('laporan.view') }}" method="GET" class="mb-0">
        <div class="row g-3 align-items-end">
          <div class="col-md-5">
            <label for="start_date" class="form-label">Tanggal Awal</label>
            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" id="start_date">
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
            <a href="{{ route('laporan.view') }}" class="btn btn-secondary w-100 mt-2">
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
    <i class="bi bi-info-circle"></i> Menampilkan laporan dari tanggal 
    <strong>{{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d-m-Y') : 'Awal' }}</strong> 
    sampai 
    <strong>{{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d-m-Y') : 'Akhir' }}</strong>
    <a href="{{ route('laporan.view') }}" class="float-end text-decoration-none">
      <i class="bi bi-x"></i> Hapus filter
    </a>
  </div>
  @endif

  <!-- Baris Info Jumlah Data dan Tombol Tambah -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted">
      Menampilkan {{ $laporan->firstItem() ?? 0 }} - {{ $laporan->lastItem() ?? 0 }} dari {{ $laporan->total() ?? 0 }} laporan
    </div>
    <a href="{{ route('laporan.insert') }}" class="btn btn-success">
      <i class="bi bi-plus-circle me-1"></i> Tambah Laporan
    </a>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th width="60">No</th>
          <th>Tanggal Laporan</th>
          <th>Periode</th>
          <th>Dibuat Oleh</th>
          <th>Keterangan</th>
          <th width="200">Aksi</th> <!-- Increased width for action column -->
        </tr>
      </thead>
      <tbody>
        @forelse ($laporan as $item)
        <tr>
          <td class="text-center">{{ ($laporan->currentPage() - 1) * $laporan->perPage() + $loop->iteration }}</td>
          <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
          <td>
            {{ \Carbon\Carbon::parse($item->tanggal_awal)->format('d-m-Y') }} - 
            {{ \Carbon\Carbon::parse($item->tanggal_akhir)->format('d-m-Y') }}
          </td>
          <td>{{ $item->name ?? 'N/A' }}</td>
          <td>{{ Str::limit($item->keterangan, 50) }}</td>
          <td class="text-center">
            <div class="d-flex justify-content-center gap-1"> <!-- Flexbox for better alignment -->
              <a href="{{ route('laporan.detail', $item->kd_laporan) }}" class="btn btn-info btn-sm" title="Detail Laporan">
                <i class="bi bi-info-circle-fill"></i> Detail
              </a>
              <a href="{{ route('laporan.edit', $item->kd_laporan) }}" class="btn btn-warning btn-sm" title="Edit">
                <i class="bi bi-pencil-square"></i>Edit
              </a>
              <!-- Tombol Generate -->
              {{-- <a href="{{ route('laporan.json', $item->kd_laporan) }}" class="btn btn-success btn-sm" title="Generate Laporan">
                <i class="bi bi-gear-fill"></i>Create Files
              </a> --}}
              <a href="{{ route('laporan.final_report', $item->kd_laporan) }}" class="btn btn-secondary btn-sm" title="Lihat Laporan Bulanan">
                  <i class="bi bi-eye-fill"></i>Generate
              </a>              
              <form action="{{ route('laporan.destroy', $item->kd_laporan) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" 
                        onclick="return confirm('Apakah Anda yakin ingin menghapus laporan ini?');" title="Hapus">
                  <i class="bi bi-trash"></i>Delete
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="text-center py-4">
            @if(request('start_date') || request('end_date'))
              Tidak ditemukan laporan pada periode yang dipilih
            @else
              Tidak ada data laporan
            @endif
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  @if($laporan->hasPages())
  <div class="d-flex justify-content-center mt-4">
    {{ $laporan->withQueryString()->links() }}
  </div>
  @endif
</div>

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