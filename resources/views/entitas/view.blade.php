@extends('layouts.app')
@section('title', 'Master Entitas')
@section('nav')
@include('layouts.nav')
@endsection
@section('content')
  <h4 class="mb-3"><i class="bi bi-diagram-3-fill"></i> Data Entitas</h4>
  @include('layouts.notif')
  <a href="{{ route('entitas.insert') }}" class="btn btn-success mb-3">
    <i class="bi bi-plus-circle"></i> Tambah Entitas
  </a>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th>No</th>
          <th>Kode</th>
          <th>Nama Entitas</th>
          <th>Induk</th>
          <th>Keterangan</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($entitas as $item)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td>{{ $item->kd_entitas }}</td>
          <td>{{ $item->nama_entitas }}</td>
          <td>{{ $item->induk }}</td>
          <td>{{ $item->keterangan }}</td>
          <td>
            <a href="{{ route('entitas.edit', $item->kd_entitas) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i></a>
            <form action="{{ route('entitas.destroy', $item->kd_entitas) }}" method="POST" style="display:inline;">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus entitas ini?');">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>
       @endforeach
        <!-- Tambahkan data dari database -->
      </tbody>
    </table>
  </div>
</div>
@endsection
