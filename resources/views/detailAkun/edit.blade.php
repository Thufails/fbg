@extends('layouts.appform')
@section('content')
<div class="container">
  <h3 class="mb-4"><i class="bi bi-journal-bookmark-fill text-primary"></i> Edit Detail Akun</h3>
  <form action="{{ route('detailAkun.update', $detailAkun->kd_detail_akun) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row g-3">
      <!-- Kode Detail Akun (readonly) -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-key text-primary"></i> Kode Detail Akun</label>
        <input type="text" name="kd_detail_akun" class="form-control" value="{{ $detailAkun->kd_detail_akun }}" readonly>
        <input type="hidden" name="kd_akun" value="{{ $detailAkun->kd_akun }}">
      </div>

      <!-- Akun Induk (readonly) dengan data dari join -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-diagram-2 text-primary"></i> Akun Induk</label>
        <input type="text" class="form-control" 
               value="{{ $detailAkun->kd_akun }} - {{ $detailAkun->nama_akun }}" 
               readonly>
        <input type="hidden" name="kd_akun" value="{{ $detailAkun->kd_akun }}">
      </div>

      <!-- Nama Detail Akun -->
      <div class="col-12">
        <label class="form-label"><i class="bi bi-card-heading text-primary"></i> Nama Detail Akun</label>
        <input type="text" name="nama_detail_akun" class="form-control @error('nama_detail_akun') is-invalid @enderror" 
               value="{{ old('nama_detail_akun', $detailAkun->nama_detail_akun) }}" 
               placeholder="Masukkan nama detail akun" required>
        @error('nama_detail_akun')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <!-- Keterangan -->
      <div class="col-12">
        <label class="form-label"><i class="bi bi-card-text text-primary"></i> Keterangan</label>
        <textarea name="keterangan" class="form-control @error('keterangan') is-invalid @enderror" rows="3"
                  placeholder="Tambahkan keterangan jika diperlukan">{{ old('keterangan', $detailAkun->keterangan) }}</textarea>
        @error('keterangan')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <!-- Tombol Aksi -->
      <div class="col-12 d-flex justify-content-between mt-4">
        <a href="{{ route('akun.view') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left-circle"></i> Kembali
        </a>
        <div>
          <button type="submit" class="btn btn-primary me-2">
            <i class="bi bi-save"></i> Simpan Perubahan
          </button>
          <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
            <i class="bi bi-trash"></i> Hapus
          </button>
        </div>
      </div>
    </div>
  </form>

  <!-- Modal Konfirmasi Hapus -->
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Apakah Anda yakin ingin menghapus detail akun ini?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <form action="{{ route('detailAkun.destroy', $detailAkun->kd_detail_akun) }}" method="POST" style="display: inline;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Ya, Hapus</button>
          </form>
        </div>
      </div>
    </div>
  </div>
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