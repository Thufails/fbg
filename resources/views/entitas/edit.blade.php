@extends('layouts.appform')
@section('title','Ubah Entitas')
@section('content')
  <h3 class="mb-4"><i class="bi bi-diagram-3"></i> Edit Data Entitas</h3>
  <form action="{{ route('entitas.update', $entitas->kd_entitas) }}" method="POST">
    @csrf
    @method('PUT') <!-- This is important for PUT requests -->
    
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-upc-scan"></i> Kode Entitas</label>
      <input type="text" name="kd_entitas" class="form-control" value="{{ old('kd_entitas', $entitas->kd_entitas) }}" readonly>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-building"></i> Nama Entitas</label>
      <input type="text" name="nama_entitas" class="form-control" value="{{ old('nama_entitas', $entitas->nama_entitas) }}" required>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-diagram-3"></i> Induk Entitas</label>
      <select name="induk" class="form-select">
        <option value="">-- Pilih Entitas Induk (opsional) --</option>
        @foreach($entitasList as $induk)
          <option value="{{ $induk->kd_entitas }}" {{ $induk->kd_entitas == $entitas->induk ? 'selected' : '' }}>
            {{ $induk->kd_entitas }} - {{ $induk->nama_entitas }}
          </option>
        @endforeach
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-tag"></i> Keterangan</label>
      <input type="text" name="keterangan" class="form-control" value="{{ old('keterangan', $entitas->keterangan) }}">
    </div>

    <div class="d-flex justify-content-between">
      <a href="{{ route('entitas.view') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left-circle"></i> Kembali
      </a>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-save2"></i> Simpan Perubahan
      </button>
    </div>
  </form>
</div>
@endsection