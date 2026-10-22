@extends('layouts.app')
@section('title', 'Daftar Analisis')
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-file-earmark-bar-graph"></i> Data Analisis</h4>
  @include('layouts.notif')
  <a href="{{ route('analisis.insert') }}" class="btn btn-success mb-3">
    <i class="bi bi-plus-circle"></i> Tambah Analisis
  </a>

  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead class="table-dark">
        <tr>
          <th>No</th>
          <th>Tanggal Dibuat</th>
          <th>Uraian</th>
          <th>Bulan</th>
          <th>Tahun</th>
          <th>Dibuat Oleh</th>          
          <th width="180">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($analisis as $item)
        <tr>
          <td>{{ ($analisis->currentPage() - 1) * $analisis->perPage() + $loop->iteration }}</td>
          <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d-m-Y') }}</td>
          <td>{{ Str::limit($item->uraian, 50) }}</td>
          <td>{{ $item->bulan }}</td>
          <td>{{ $item->tahun }}</td>
          <td>{{ $item->name ?? 'N/A' }}</td>          
          <td class="text-center">
            <a href="{{ route('analisis.detail', $item->kd_analisis) }}" class="btn btn-info btn-sm" title="Detail">
              <i class="bi bi-eye"></i> 
            </a>
            
            <a href="{{ route('analisis.edit', $item->kd_analisis) }}" class="btn btn-warning btn-sm" title="Edit">
              <i class="bi bi-pencil-square"></i>
            </a>
            
            <form action="{{ route('analisis.destroy', $item->kd_analisis) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm" 
                      onclick="return confirm('Apakah Anda yakin ingin menghapus analisis ini?');" title="Hapus">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  @if($analisis->hasPages())
  <div class="d-flex justify-content-center mt-3">
    {{ $analisis->links() }}
  </div>
  @endif
</div>

<style>
  .table-dark {
    background: linear-gradient(135deg, #212529, #343a40);
  }
  
  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }
  
  .table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.05);
  }
</style>
@endsection