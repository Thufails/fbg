@extends('layouts.appform')
@section('content')
<div class="container">
  <h3 class="mb-4"><i class="bi bi-journal-bookmark-fill text-primary"></i> Tambah Detail Akun</h3>
  <form action="{{ route('detailAkun.store') }}" method="POST">
    @csrf
    <div class="row g-3">
      <!-- Nama Detail Akun -->
      <div class="col-12">
        <label class="form-label"><i class="bi bi-card-heading text-primary"></i> Akun : </label>
        <input value="{{ $kd_akun }}" type="hidden" name="kd_akun" class="form-control">{{ $nama_akun }}
      </div>
      <!-- Nama Detail Akun -->
      <div class="col-12">
        <label class="form-label"><i class="bi bi-card-heading text-primary"></i> Nama Detail Akun</label>
        <input type="text" name="nama_detail_akun" class="form-control" placeholder="Masukkan nama detail akun" required>
      </div>

      <!-- Keterangan -->
      <div class="col-12">
        <label class="form-label"><i class="bi bi-card-text text-primary"></i> Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="3" placeholder="Tambahkan keterangan jika diperlukan"></textarea>
      </div>

      <!-- Tombol Aksi -->
      <div class="col-12 d-flex justify-content-between mt-4">
        <a href="{{ route('akun.view') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left-circle"></i> Kembali
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save"></i> Simpan Data
        </button>
      </div>
    </div>
  </form>
</div>

<style>
  .form-radio-group {
    display: flex;
    gap: 1.5rem;
  }
  .form-radio-item {
    display: flex;
    align-items: center;
  }
</style>
@endsection