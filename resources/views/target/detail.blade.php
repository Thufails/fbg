@extends('layouts.app')
@section('title', 'Detail Target')
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-bullseye"></i> Detail Semua Target</h4>
  
  <div class="table-responsive">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Entitas</th>
          <th>Akun</th>
          <th>Nilai Target</th>
          <th>Detail</th>
        </tr>
      </thead>
      <tbody>
        @foreach($targets as $target)
        <tr>
          <td>{{ $target->tanggal }}</td>
          <td>{{ $target->entitas->nama_entitas }}</td>
          <td>{{ $target->akun->nama_akun }}</td>
          <td>{{ number_format($target->nilai, 0, ',', '.') }}</td>
          <td>
            <a href="{{ route('target.detail', $target->kd_target) }}" class="btn btn-sm btn-info">
              <i class="bi bi-eye"></i> Lihat
            </a>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection