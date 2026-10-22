@extends('layouts.appform')
@section('content')
<div class="container">
  <h3 class="mb-4"><i class="bi bi-bullseye text-primary"></i> Input Target</h3>
  <form action="{{ route('target.store') }}" method="POST" id="targetForm">
    @csrf
    <div class="row g-3">
      <!-- Tanggal Target -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-date text-primary"></i> Tanggal Awal</label>
        <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control" required value="{{ old('tanggal_awal', date('Y-m-d')) }}">
      </div>

      <!-- Tanggal Akhir -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-date text-primary"></i> Tanggal Akhir</label>
        <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" required value="{{ old('tanggal_akhir', date('Y-m-d')) }}">
        <div id="dateError" class="invalid-feedback d-none">Tanggal akhir tidak boleh sebelum tanggal awal</div>
      </div>

      <!-- Jenis Periode -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-range text-primary"></i> Jenis Periode</label>
        <div class="form-radio-group">
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="jenis_periode" id="harian" value="harian" checked>
            <label class="form-check-label ms-2" for="harian">Harian</label>
          </div>
          <div class="form-radio-item">
            <input class="form-check-input" type="radio" name="jenis_periode" id="bulanan" value="bulanan">
            <label class="form-check-label ms-2" for="bulanan">Bulanan</label>
          </div>
        </div>
      </div>

      <!-- Kode Entitas -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-building text-primary"></i> Entitas</label>
        <select class="form-select" name="kd_entitas" required>
          <option value="" selected disabled>Pilih Entitas</option>
          @foreach($entitasList as $entitas)
            <option value="{{ $entitas->kd_entitas }}" {{ old('kd_entitas') == $entitas->kd_entitas ? 'selected' : '' }}>
              {{ $entitas->kd_entitas }} - {{ $entitas->nama_entitas }}
            </option>
          @endforeach
        </select>
      </div>

      <!-- Kode Akun -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-wallet2 text-primary"></i> Akun</label>
        <select class="form-select" name="kd_akun" required>
          <option value="" selected disabled>Pilih Akun</option>
          @foreach($akunList as $akun)
            <option value="{{ $akun->kd_akun }}" {{ old('kd_akun') == $akun->kd_akun ? 'selected' : '' }}>
              {{ $akun->kd_akun }} - {{ $akun->nama_akun }}
            </option>
          @endforeach
        </select>
      </div>

      <!-- Nilai Target -->
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-currency-dollar text-primary"></i> Nilai Target</label>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="text" name="nilai" class="form-control" id="nilaiInput" 
                placeholder="Masukkan nilai target" required value="{{ old('nilai') }}">
        </div>
        <small class="text-muted">Contoh: 1.000.000.000</small>
      </div>

      <!-- Keterangan -->
      <div class="col-12">
        <label class="form-label"><i class="bi bi-card-text text-primary"></i> Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="3" placeholder="Tambahkan keterangan jika diperlukan">{{ old('keterangan') }}</textarea>
      </div>

      <!-- Tombol Aksi -->
      <div class="col-12 d-flex justify-content-between mt-4">
        <a href="{{ route('target.view') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left-circle"></i> Kembali
        </a>
        <button type="submit" class="btn btn-primary" id="submitBtn">
          <i class="bi bi-save"></i> Simpan Target
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
  .input-group-text {
    background-color: #f8f9fa;
  }
  select.form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
  }
  .is-invalid {
    border-color: #dc3545;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format input nilai saat diketik
    const nilaiInput = document.getElementById('nilaiInput');
    if (nilaiInput) {
        nilaiInput.addEventListener('keyup', function(e) {
            // Hapus semua karakter non-digit
            let value = this.value.replace(/[^0-9]/g, '');
            
            // Format dengan pemisah ribuan
            if (value.length > 0) {
                value = parseInt(value, 10).toLocaleString('id-ID');
            }
            
            this.value = value;
        });
        
        // Sebelum form disubmit, hapus format pemisah ribuan
        const form = document.getElementById('targetForm');
        form.addEventListener('submit', function() {
            if (nilaiInput.value) {
                nilaiInput.value = nilaiInput.value.replace(/[^0-9]/g, '');
            }
        });
    }
    
    // Auto focus ke input tanggal awal saat halaman dimuat
    const tanggalAwalInput = document.querySelector('input[name="tanggal_awal"]');
    if (tanggalAwalInput) {
        tanggalAwalInput.focus();
    }

    // Validasi tanggal
    const form = document.getElementById('targetForm');
    const tanggalAwal = document.getElementById('tanggal_awal');
    const tanggalAkhir = document.getElementById('tanggal_akhir');
    const dateError = document.getElementById('dateError');
    const submitBtn = document.getElementById('submitBtn');

    function validateDates() {
        const startDate = new Date(tanggalAwal.value);
        const endDate = new Date(tanggalAkhir.value);
        
        if (startDate > endDate) {
            tanggalAkhir.classList.add('is-invalid');
            dateError.classList.remove('d-none');
            submitBtn.disabled = true;
            return false;
        } else {
            tanggalAkhir.classList.remove('is-invalid');
            dateError.classList.add('d-none');
            submitBtn.disabled = false;
            return true;
        }
    }

    // Validasi saat tanggal diubah
    tanggalAwal.addEventListener('change', validateDates);
    tanggalAkhir.addEventListener('change', validateDates);

    // Validasi saat form disubmit
    form.addEventListener('submit', function(e) {
        if (!validateDates()) {
            e.preventDefault();
            tanggalAkhir.focus();
        }
    });

    // Set tanggal akhir sama dengan tanggal awal saat pertama kali
    if (!tanggalAkhir.value && tanggalAwal.value) {
        tanggalAkhir.value = tanggalAwal.value;
    }
});
</script>
@endsection