@extends('layouts.appform')
@section('content')
  <h3 class="mb-4"><i class="bi bi-diagram-3"></i> Tambah Data Entitas</h3>
  <form action="{{ route('entitas.store') }}" method="POST">
    @csrf
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-upc-scan"></i> Kode Entitas</label>
      <input type="text" name="kd_entitas" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-building"></i> Nama Entitas</label>
      <input type="text" name="nama_entitas" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-diagram-3"></i> Induk Entitas</label>
      <select name="induk" class="form-select">
      <option value="">-- Pilih Entitas Induk (opsional) --</option>
      @foreach($entitasList as $induk)
        <option value="{{ $induk->kd_entitas }}">
          {{ $induk->kd_entitas }} - {{ $induk->nama_entitas }}
        </option>
      @endforeach
    </select>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-tag"></i> Keterangan</label>
      <input type="text" name="keterangan" class="form-control">
    </div>

    <div class="d-flex justify-content-between">
      <a href="{{ route('entitas.view') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left-circle"></i> Kembali
      </a>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-save2"></i> Simpan
      </button>
    </div>

  </form>
</div>
@endsection
