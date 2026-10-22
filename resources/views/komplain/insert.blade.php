@extends('layouts.appform')
@section('content')
<div class="container">
  <h3 class="mb-4"><i class="bi bi-chat-square-text-fill text-primary"></i> Formulir Komplain</h3>
  <form action="{{ route('komplain.store') }}" method="POST">
    @csrf
    <div class="row g-3">
      <!-- Tanggal Komplain -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-date text-primary"></i> Tanggal</label>
        <input type="date" name="tanggal" class="form-control" required>
      </div>

      <!-- Sumber Komplain -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-source text-primary"></i> Sumber</label>
        <select class="form-select" name="sumber" required>
          <option value="" selected disabled>Pilih Sumber</option>
          <option value="Pelanggan"><i class="bi bi-person"></i> Pelanggan</option>
          <option value="Internal"><i class="bi bi-building"></i> Internal</option>
          <option value="Pemasok"><i class="bi bi-truck"></i> Pemasok</option>
          <option value="Lainnya"><i class="bi bi-three-dots"></i> Lainnya</option>
        </select>
      </div>

      <!-- Kategori Komplain -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-tag text-primary"></i> Kategori</label>
        <select class="form-select" name="kategori" required>
          <option value="" selected disabled>Pilih Kategori</option>
          <option value="Produk">Produk</option>
          <option value="Layanan">Layanan</option>
          <option value="Teknis">Teknis</option>
          <option value="Administrasi">Administrasi</option>
        </select>
      </div>

      <!-- Prioritas -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-exclamation-triangle text-primary"></i> Prioritas</label>
        <div class="form-radio-group">
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="prioritas" id="prioritas-rendah" value="rendah">
            <label class="form-check-label ms-2 text-success" for="prioritas-rendah">
              Rendah
            </label>
          </div>
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="prioritas" id="prioritas-sedang" value="sedang" checked>
            <label class="form-check-label ms-2 text-warning" for="prioritas-sedang">
              Sedang
            </label>
          </div>
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="prioritas" id="prioritas-tinggi" value="tinggi">
            <label class="form-check-label ms-2 text-danger" for="prioritas-tinggi">
              Tinggi
            </label>
          </div>
        </div>
      </div>

      <!-- Deskripsi Komplain -->
      <div class="col-12">
        <label class="form-label">Deskripsi Komplain</label>
        <textarea name="uraian" class="form-control" rows="4" placeholder="Jelaskan komplain Anda secara detail..." required></textarea>
      </div>
      <!-- Status -->
      <div class="col-12">
        <label class="form-label"> Status</label>
        <div class="form-radio-group">
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="status" id="status-baru" value="0" checked>
            <label class="form-check-label ms-2" for="status-baru">
              <i class="bi bi-file-earmark-plus text-info"></i> Baru
            </label>
          </div>
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="status" id="status-proses" value="1">
            <label class="form-check-label ms-2" for="status-proses">
              <i class="bi bi-gear text-warning"></i> Proses
            </label>
          </div>
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="status" id="status-selesai" value="2">
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
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-send-check"></i> Kirim Komplain
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