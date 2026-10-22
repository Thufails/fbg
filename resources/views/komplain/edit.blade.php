@extends('layouts.appform')
@section('content')
<div class="container">
  <h3 class="mb-4"><i class="bi bi-pencil-square text-primary"></i> Edit Komplain</h3>
  <form action="{{ route('komplain.update', $komplain->kd_komplain) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row g-3">
      <!-- Tanggal Komplain -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-date text-primary"></i> Tanggal</label>
        <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', $komplain->tanggal) }}" required>
      </div>

      <!-- Sumber Komplain -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-source text-primary"></i> Sumber</label>
        <select class="form-select" name="sumber" required>
          <option value="" disabled>Pilih Sumber</option>
          <option value="Pelanggan" {{ old('sumber', $komplain->sumber) == 'Pelanggan' ? 'selected' : '' }}>Pelanggan</option>
          <option value="Internal" {{ old('sumber', $komplain->sumber) == 'Internal' ? 'selected' : '' }}>Internal</option>
          <option value="Pemasok" {{ old('sumber', $komplain->sumber) == 'Pemasok' ? 'selected' : '' }}>Pemasok</option>
          <option value="Lainnya" {{ old('sumber', $komplain->sumber) == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
        </select>
      </div>

      <!-- Kategori Komplain -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-tag text-primary"></i> Kategori</label>
        <select class="form-select" name="kategori" required>
          <option value="" disabled>Pilih Kategori</option>
          <option value="Produk" {{ old('kategori', $komplain->kategori) == 'Produk' ? 'selected' : '' }}>Produk</option>
          <option value="Layanan" {{ old('kategori', $komplain->kategori) == 'Layanan' ? 'selected' : '' }}>Layanan</option>
          <option value="Teknis" {{ old('kategori', $komplain->kategori) == 'Teknis' ? 'selected' : '' }}>Teknis</option>
          <option value="Administrasi" {{ old('kategori', $komplain->kategori) == 'Administrasi' ? 'selected' : '' }}>Administrasi</option>
        </select>
      </div>

      <!-- Prioritas -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-exclamation-triangle text-primary"></i> Prioritas</label>
        <div class="form-radio-group">
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="prioritas" id="prioritas-rendah" value="rendah" {{ old('prioritas', $komplain->prioritas) == 'rendah' ? 'checked' : '' }}>
            <label class="form-check-label ms-2 text-success" for="prioritas-rendah">
              Rendah
            </label>
          </div>
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="prioritas" id="prioritas-sedang" value="sedang" {{ old('prioritas', $komplain->prioritas) == 'sedang' ? 'checked' : '' }}>
            <label class="form-check-label ms-2 text-warning" for="prioritas-sedang">
              Sedang
            </label>
          </div>
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="prioritas" id="prioritas-tinggi" value="tinggi" {{ old('prioritas', $komplain->prioritas) == 'tinggi' ? 'checked' : '' }}>
            <label class="form-check-label ms-2 text-danger" for="prioritas-tinggi">
              Tinggi
            </label>
          </div>
        </div>
      </div>

      <!-- Deskripsi Komplain -->
      <div class="col-12">
        <label class="form-label"><i class="bi bi-chat-left-text text-primary"></i> Deskripsi Komplain</label>
        <textarea name="uraian" class="form-control" rows="4" required>{{ old('uraian', $komplain->uraian) }}</textarea>
      </div>

      <!-- Status -->
      <div class="col-12">
        <label class="form-label"><i class="bi bi-clipboard-check text-primary"></i> Status</label>
        <div class="form-radio-group">
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="status" id="status-baru" value="0" {{ old('status', $komplain->status) == 0 ? 'checked' : '' }}>
            <label class="form-check-label ms-2" for="status-baru">
              <i class="bi bi-file-earmark-plus text-info"></i> Baru
            </label>
          </div>
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="status" id="status-proses" value="1" {{ old('status', $komplain->status) == 1 ? 'checked' : '' }}>
            <label class="form-check-label ms-2" for="status-proses">
              <i class="bi bi-gear text-warning"></i> Proses
            </label>
          </div>
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="status" id="status-selesai" value="2" {{ old('status', $komplain->status) == 2 ? 'checked' : '' }}>
            <label class="form-check-label ms-2" for="status-selesai">
              <i class="bi bi-check-circle text-success"></i> Selesai
            </label>
          </div>
        </div>
      </div>

      <!-- Tombol Aksi -->
      <div class="col-12 d-flex justify-content-between mt-4">
        <a href="{{ route('komplain.view') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left-circle"></i> Kembali
        </a>
        <div>
          <button type="submit" class="btn btn-primary me-2">
            <i class="bi bi-check-circle"></i> Simpan Perubahan
          </button>
          <a href="{{ route('komplain.detail', $komplain->kd_komplain) }}" class="btn btn-info">
            <i class="bi bi-eye"></i> Lihat Detail
          </a>
        </div>
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