@extends('layouts.appform')
@section('content')
  <h3 class="mb-4"><i class="bi bi-calendar-plus"></i> Tambah Konversi Tanggal</h3>
  
  @if(session('error'))
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
    </div>
  @endif
  
  <form action="{{ route('konversiTanggal.preview') }}" method="POST" id="konversiForm">
    @csrf
    
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <i class="bi bi-calendar-range"></i> Rentang Tanggal Masehi
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label"><i class="bi bi-calendar-date"></i> Tanggal Masehi Awal</label>
              <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control @error('tanggal_awal') is-invalid @enderror" 
                     value="{{ old('tanggal_awal') }}" required>
              @error('tanggal_awal')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label"><i class="bi bi-calendar-date"></i> Tanggal Masehi Akhir</label>
              <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control @error('tanggal_akhir') is-invalid @enderror" 
                     value="{{ old('tanggal_akhir') }}" required>
              @error('tanggal_akhir')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="alert alert-warning" id="dayCountAlert" style="display: none;">
              <i class="bi bi-exclamation-triangle"></i> 
              <span id="dayCountMessage"></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header bg-success text-white">
        <i class="bi bi-calendar-heart"></i> Tanggal Hijriah
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label"><i class="bi bi-calendar-month"></i> Bulan Hijriah</label>
              <select name="bulan_h" id="bulan_h" class="form-select @error('bulan_h') is-invalid @enderror" required>
                <option value="">-- Pilih Bulan Hijriah --</option>
                @foreach($hijriMonths as $monthNumber => $monthName)
                  <option value="{{ $monthNumber }}" {{ old('bulan_h') == $monthNumber ? 'selected' : '' }}>
                    {{ $monthNumber }} - {{ $monthName }}
                  </option>
                @endforeach
              </select>
              @error('bulan_h')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">Format: Angka - Nama Bulan</small>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label"><i class="bi bi-calendar-year"></i> Tahun Hijriah</label>
              <input type="number" name="tahun_h" id="tahun_h" class="form-control @error('tahun_h') is-invalid @enderror" 
                     value="{{ old('tahun_h') }}" placeholder="Masukkan tahun hijriah" 
                     min="1300" max="1500" required>
              @error('tahun_h')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">Contoh: 1445</small>
            </div>
          </div>
        </div>
        
        <!-- Preview format tanggal hijriah -->
        <div class="row mt-3">
          <div class="col-12">
            <div class="alert alert-info" id="formatPreview" style="display: none;">
              <i class="bi bi-info-circle"></i> 
              <strong>Preview Format:</strong> 
              <span id="previewText"></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="alert alert-info">
      <i class="bi bi-info-circle"></i> 
      <strong>Informasi:</strong> 
      <ul class="mb-0 mt-2">
        <li>Maksimal rentang tanggal adalah 30 hari</li>
        <li>Tahun hijriah harus antara 1300 - 1500</li>
        <li>Format tanggal hijriah akan menggunakan format YYYY-MM-DD (contoh: 1445-09-01)</li>
        <li>Klik tombol "Preview Konversi" untuk melihat hasil konversi sebelum menyimpan</li>
      </ul>
    </div>

    <div class="d-flex justify-content-between">
      <a href="{{ route('konversiTanggal.view') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left-circle"></i> Kembali
      </a>
      <button type="submit" class="btn btn-primary" id="submitBtn">
        <i class="bi bi-eye"></i> Preview Konversi
      </button>
    </div>

  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tanggalAwal = document.getElementById('tanggal_awal');
    const tanggalAkhir = document.getElementById('tanggal_akhir');
    const tahunH = document.getElementById('tahun_h');
    const bulanH = document.getElementById('bulan_h');
    const dayCountAlert = document.getElementById('dayCountAlert');
    const dayCountMessage = document.getElementById('dayCountMessage');
    const formatPreview = document.getElementById('formatPreview');
    const previewText = document.getElementById('previewText');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('konversiForm');

    // Mapping angka bulan ke nama bulan untuk preview
    const bulanHijriahMapping = {
        '01': 'Muharram',
        '02': 'Safar',
        '03': 'Rabiul Awal',
        '04': 'Rabiul Akhir',
        '05': 'Jumadil Awal',
        '06': 'Jumadil Akhir',
        '07': 'Rajab',
        '08': 'Sya\'ban',
        '09': 'Ramadhan',
        '10': 'Syawal',
        '11': 'Dzulqa\'dah',
        '12': 'Dzulhijjah'
    };

    // Fungsi untuk menghitung selisih hari
    function calculateDayDifference() {
        if (tanggalAwal.value && tanggalAkhir.value) {
            const start = new Date(tanggalAwal.value);
            const end = new Date(tanggalAkhir.value);
            
            // Hitung selisih dalam milidetik dan konversi ke hari
            const timeDiff = end.getTime() - start.getTime();
            const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // +1 untuk include tanggal awal
            
            return dayDiff;
        }
        return 0;
    }

    // Fungsi untuk update tampilan alert
    function updateDayCountAlert() {
        const dayDiff = calculateDayDifference();
        
        if (dayDiff > 0) {
            if (dayDiff > 30) {
                dayCountAlert.className = 'alert alert-danger';
                dayCountMessage.innerHTML = `<strong>Peringatan:</strong> Rentang tanggal terlalu panjang! Maksimal 30 hari, tetapi Anda memilih ${dayDiff} hari.`;
                dayCountAlert.style.display = 'block';
                submitBtn.disabled = true;
            } else {
                dayCountAlert.className = 'alert alert-success';
                dayCountMessage.innerHTML = `<strong>Info:</strong> Rentang tanggal: ${dayDiff} hari`;
                dayCountAlert.style.display = 'block';
                submitBtn.disabled = false;
            }
        } else {
            dayCountAlert.style.display = 'none';
            submitBtn.disabled = false;
        }
    }

    // Fungsi untuk update preview format
    function updateFormatPreview() {
        const monthNumber = bulanH.value;
        const year = tahunH.value;
        const monthName = bulanHijriahMapping[monthNumber] || '';
        
        if (monthNumber && year) {
            formatPreview.style.display = 'block';
            previewText.innerHTML = `Tanggal hijriah akan diformat sebagai: <strong>${year}-${monthNumber}-01</strong> (${monthName})`;
        } else {
            formatPreview.style.display = 'none';
        }
    }

    // Event listeners untuk tanggal
    tanggalAwal.addEventListener('change', function() {
        updateDayCountAlert();
        // Set tanggal akhir minimal sama dengan tanggal awal
        if (tanggalAkhir.value && new Date(tanggalAkhir.value) < new Date(this.value)) {
            tanggalAkhir.value = this.value;
        }
        tanggalAkhir.min = this.value;
    });

    tanggalAkhir.addEventListener('change', function() {
        updateDayCountAlert();
    });

    // Event listeners untuk bulan dan tahun hijriah
    bulanH.addEventListener('change', updateFormatPreview);
    tahunH.addEventListener('input', updateFormatPreview);

    // Validasi tahun hijriah
    tahunH.addEventListener('input', function() {
        const yearValue = parseInt(this.value);
        if (this.value && (yearValue < 1300 || yearValue > 1500)) {
            this.classList.add('is-invalid');
            submitBtn.disabled = true;
        } else {
            this.classList.remove('is-invalid');
            submitBtn.disabled = false;
        }
        updateFormatPreview();
    });

    // Validasi form sebelum submit
    form.addEventListener('submit', function(e) {
        const dayDiff = calculateDayDifference();
        const yearValue = parseInt(tahunH.value);

        // Validasi rentang hari
        if (dayDiff > 30) {
            e.preventDefault();
            alert('Rentang tanggal tidak boleh lebih dari 30 hari. Silakan pilih rentang yang lebih pendek.');
            tanggalAwal.focus();
            return false;
        }

        // Validasi tahun hijriah
        if (yearValue < 1300 || yearValue > 1500) {
            e.preventDefault();
            alert('Tahun hijriah harus antara 1300 - 1500. Silakan periksa kembali.');
            tahunH.focus();
            return false;
        }

        // Validasi tanggal akhir tidak boleh sebelum tanggal awal
        if (new Date(tanggalAkhir.value) < new Date(tanggalAwal.value)) {
            e.preventDefault();
            alert('Tanggal akhir tidak boleh sebelum tanggal awal.');
            tanggalAkhir.focus();
            return false;
        }

        return true;
    });

    // Inisialisasi alert jika ada nilai dari old input
    if (tanggalAwal.value && tanggalAkhir.value) {
        updateDayCountAlert();
    }
    
    // Inisialisasi preview format
    updateFormatPreview();
});
</script>
@endsection