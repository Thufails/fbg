@extends('layouts.app')
@section('title','Master Akun')
@section('content')
  <h4 class="mb-3"><i class="bi bi-person-lines-fill"></i> Data Akun</h4>
  <a href="{{ route('akun.insert') }}" class="btn btn-success mb-3"><i class="bi bi-plus-circle"></i> Tambah Akun</a>

  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead class="table-dark">
        <tr>
          <th>No</th>
          <th>Kode Akun</th>
          <th>Nama Akun</th>
          <th>Induk</th>
          <th>Keterangan</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($akun as $item)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td>{{ $item->kd_akun }}</td>
          <td>{{ $item->nama_akun }}</td>
          <td>{{ $item->induk }}</td>
          <td>{{ $item->keterangan }}</td>
          <td>
            <a href="{{ route('akun.edit', $item->kd_akun) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i></a>
            <form action="{{ route('akun.destroy', $item->kd_akun) }}" method="POST" style="display:inline;">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>            
        @endforeach
        <!-- Data lainnya -->
      </tbody>
    </table>
  </div>
</div>
@endsection
