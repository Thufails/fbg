@extends('layouts.app')
@section('title', 'Tambah Detail Target')
@section('content')
<div class="container-fluid">
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-cart-check"></i> Tambah Detail Target</h4>
        <a href="{{ route('detailTarget.select') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i> Kembali Pilih Tanggal
        </a>
      </div>
      
      <!-- Target Info -->
      <div class="card mb-4">
        <div class="card-header bg-light">
          <h5 class="mb-0">Informasi Target</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <p class="mb-1"><strong>Entitas:</strong></p>
              <p>{{ $target->nama_entitas }}</p>
            </div>
            <div class="col-md-4">
              <p class="mb-1"><strong>Akun:</strong></p>
              <p>{{ $target->kd_akun }} - {{ $target->nama_akun }}</p>
            </div>
            <div class="col-md-4">
              <p class="mb-1"><strong>Total Tanggal Dipilih:</strong></p>
              <p>{{ count($targetItems) }} tanggal</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Multi-Date Entry Form -->
      <div class="card mb-4">
        <div class="card-header bg-light">
          <h5 class="mb-0"><i class="bi bi-calendar-range"></i> Tambah Detail ke Beberapa Tanggal Sekaligus</h5>
        </div>
        <div class="card-body">
          <form id="multiDateForm" method="POST" action="{{ route('detailTarget.storeBulkDetails') }}">
            @csrf
            <input type="hidden" name="kd_target" value="{{ $target->kd_target }}"> 
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Pilih Tanggal</label>
                    <select id="dateSelector" name="dates[]" class="form-select" multiple required>
                        @foreach($targetItems as $item)
                            <option value="{{ $item['tanggal'] }}">
                                {{ \Carbon\Carbon::parse($item['tanggal'])->format('d-m-Y') }}
                            </option>
                        @endforeach
                    </select>
                    <div class="mt-2">
                        <small class="text-muted" id="selectedDatesCount">0 tanggal dipilih</small>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Detail Akun</label>
                    <select name="kd_detail_akun" class="form-select" required>
                        <option value="">Pilih Detail Akun</option>
                        @foreach($detailAkunList as $detailAkun)
                            <option value="{{ $detailAkun->kd_detail_akun }}">
                                {{ $detailAkun->kd_detail_akun }} - {{ $detailAkun->nama_detail_akun }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Nilai</label>
                    <input type="text" name="nilai" class="form-control" required>
                    <input type="hidden" name="nilai_raw">
                </div>
                
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for date selection
    $('#dateSelector').select2({
        placeholder: "Pilih tanggal",
        width: '100%'
    });

    // Update selected dates count
    $('#dateSelector').on('change', function() {
        const selectedDates = $(this).val() || [];
        $('#selectedDatesCount').text(`${selectedDates.length} tanggal dipilih`);
    });

    // Format numeric input
    const nilaiInput = document.querySelector('input[name="nilai"]');
    const nilaiRaw = document.querySelector('input[name="nilai_raw"]');
    
    nilaiInput.addEventListener('keyup', function() {
        let rawValue = this.value.replace(/[^0-9]/g, '');
        nilaiRaw.value = rawValue;
        if (rawValue) {
            this.value = parseInt(rawValue).toLocaleString('id-ID');
        } else {
            this.value = '';
        }
    });

    // Form submission
    $('#multiDateForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const selectedDates = $('#dateSelector').val() || [];
        const kdDetailAkun = form.find('[name="kd_detail_akun"]').val();
        const nilai = form.find('[name="nilai_raw"]').val() || form.find('[name="nilai"]').val().replace(/[^0-9]/g, '');
        
        // Validasi
        if (selectedDates.length === 0) {
            alert('Silakan pilih minimal satu tanggal');
            return;
        }
        
        if (!kdDetailAkun) {
            alert('Silakan pilih detail akun');
            return;
        }
        
        if (!nilai) {
            alert('Silakan isi nilai');
            return;
        }

        // Submit form secara normal
        form.unbind('submit').submit();
    });
});
</script>
@endsection
@endsection