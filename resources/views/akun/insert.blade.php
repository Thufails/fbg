@extends('layouts.appform')
@section('content')
  <h3 class="mb-4"><i class="bi bi-person-plus"></i> Tambah Data Akun</h3>
  <form action="{{ route('akun.store') }}" method="POST">
    @csrf
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-key"></i> Kode Akun</label>
      <input type="text" name="kd_akun" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-person-badge"></i> Nama Akun</label>
      <input type="text" name="nama_akun" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-diagram-3"></i> Induk Akun</label>
      <select name="induk" class="form-select">
      <option value="">-- Pilih Akun Induk (opsional) --</option>
      @foreach($akunList as $induk)
        <option value="{{ $induk->kd_akun }}">
          {{ $induk->kd_akun }} - {{ $induk->nama_akun }}
        </option>
      @endforeach
    </select>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-plus-slash-minus"></i> Jenis Operasi</label>
      <div class="form-radio-group">
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="operasi" id="operasi-plus" value="+" checked>
          <label class="form-check-label ms-2" for="operasi-plus">
            <i class="bi bi-plus-circle text-success"></i> Penambahan (+)
          </label>
        </div>
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="operasi" id="operasi-minus" value="-">
          <label class="form-check-label ms-2" for="operasi-minus">
            <i class="bi bi-dash-circle text-danger"></i> Pengurangan (-)
          </label>
        </div>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-card-text"></i> Keterangan</label>
      <textarea name="keterangan" class="form-control" rows="3"></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-card-text"></i> Urutan</label>
       <input type="text" name="urutan" class="form-control" required>
    </div>

    <div class="d-flex justify-content-between">
      <a href="{{ route('akun.view') }}" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Kembali</a>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save2"></i> Simpan</button>
    </div>

  </form>
</div>
@endsection