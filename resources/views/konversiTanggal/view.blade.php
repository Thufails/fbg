@extends('layouts.app')
@section('title', 'Konversi Tanggal')
@section('nav')
@include('layouts.nav')
@endsection
@section('content')
  <h4 class="mb-3"><i class="bi bi-calendar-event-fill"></i> Data Konversi Tanggal</h4>
  @include('layouts.notif')
  
  <div class="d-flex gap-2 mb-3">
    <a href="{{ route('konversiTanggal.create') }}" class="btn btn-success">
      <i class="bi bi-plus-circle"></i> Tambah Konversi
    </a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#hijriCalendarModal">
      <i class="bi bi-calendar-week"></i> Kalender Hijriah
    </button>
  </div>

  <!-- Search Form -->
  <!--form method="GET" action="{{ route('konversi_tanggal.popup') }}" class="mb-3">
    <div class="input-group" style="max-width: 300px;">
      <input type="text" name="search" class="form-control" placeholder="Cari tanggal..." value="{{ $search }}">
      <button class="btn btn-outline-secondary" type="submit">
        <i class="bi bi-search"></i>
      </button>
    </div>
  </form-->

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th>No</th>
          <th>Tanggal Masehi</th>
          <th>Tanggal Hijriah</th>
          <th>Hari H</th>
          <th>Bulan H</th>
          <th>Tahun H</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($konversi_tanggal as $item)
        <tr>
          <td class="text-center">{{ $loop->iteration + ($konversi_tanggal->currentPage() - 1) * $konversi_tanggal->perPage() }}</td>
          <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
          <td>{{ $item->tanggal_h }}</td>
          <td class="text-center">{{ $item->hari_h }}</td>
          <td>{{ $item->bulan_h }}</td>
          <td class="text-center">{{ $item->tahun_h }}</td>
          <td class="text-center">
            {{-- <a href="{{ route('konversiTanggal.edit', $item->id) }}" class="btn btn-warning btn-sm">
              <i class="bi bi-pencil-square"></i>
            </a> --}}
            <form action="{{ route('konversiTanggal.destroy', $item->id) }}" method="POST" style="display:inline;">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus konversi tanggal ini?');">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="d-flex justify-content-center">
    {{ $konversi_tanggal->links() }}
  </div>

  <!-- Hijri Calendar Modal -->
  <div class="modal fade" id="hijriCalendarModal" tabindex="-1" aria-labelledby="hijriCalendarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="hijriCalendarModalLabel">
            <i class="bi bi-calendar-week"></i> Kalender Hijriah
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0" style="min-height: 600px;">
          <!-- IFrame untuk memuat kalender -->
          <iframe 
            id="hijriCalendarFrame"
            src="public/tahun_hijriah.html"
            style="width: 100%; height: 600px; border: none;"
            title="Kalender Hijriah"
            loading="lazy"
            allowfullscreen
          ></iframe>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('styles')
<style>
  /* Style untuk modal kalender */
  #hijriCalendarModal .modal-dialog {
    max-width: 90%;
  }
  
  #hijriCalendarModal .modal-body {
    padding: 0;
  }
  
  /* Jika iframe gagal dimuat, tampilkan pesan error */
  .calendar-error {
    padding: 2rem;
    text-align: center;
    background: #f8f9fa;
  }
  
  /* Responsive untuk mobile */
  @media (max-width: 768px) {
    #hijriCalendarModal .modal-dialog {
      max-width: 95%;
      margin: 0.5rem;
    }
    
    #hijriCalendarFrame {
      height: 500px;
    }
  }
  
  @media (max-width: 576px) {
    #hijriCalendarFrame {
      height: 400px;
    }
  }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hijriCalendarModal = document.getElementById('hijriCalendarModal');
    const hijriCalendarFrame = document.getElementById('hijriCalendarFrame');
    
    // Deteksi ketika modal dibuka
    if (hijriCalendarModal) {
        hijriCalendarModal.addEventListener('show.bs.modal', function() {
            console.log('Modal kalender Hijriah dibuka');
            
            // Refresh iframe setiap kali modal dibuka
            if (hijriCalendarFrame) {
                const currentSrc = hijriCalendarFrame.src;
                hijriCalendarFrame.src = currentSrc; // Refresh iframe
            }
        });
        
        // Reset ketika modal ditutup
        hijriCalendarModal.addEventListener('hidden.bs.modal', function() {
            console.log('Modal kalender Hijriah ditutup');
        });
    }
    
    // Deteksi error loading iframe
    if (hijriCalendarFrame) {
        hijriCalendarFrame.addEventListener('error', function() {
            console.error('Gagal memuat kalender Hijriah');
            
            // Tampilkan pesan error
            const modalBody = document.querySelector('#hijriCalendarModal .modal-body');
            if (modalBody) {
                modalBody.innerHTML = `
                    <div class="calendar-error">
                        <div class="alert alert-danger">
                            <h5><i class="bi bi-exclamation-triangle"></i> Gagal Memuat Kalender</h5>
                            <p>File kalender Hijriah tidak dapat dimuat.</p>
                            <p class="mb-0">Pastikan file <strong>tahun_hijriah.html</strong> ada di folder:</p>
                            <code>public/tahun_hijriah.html</code>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-primary" onclick="loadSimpleCalendar()">
                                Tampilkan Kalender Sederhana
                            </button>
                        </div>
                    </div>
                `;
            }
        });
        
        // Deteksi ketika iframe berhasil dimuat
        hijriCalendarFrame.addEventListener('load', function() {
            console.log('Kalender Hijriah berhasil dimuat');
        });
    }
});

// Fungsi untuk menampilkan kalender sederhana jika iframe gagal
function loadSimpleCalendar() {
    const modalBody = document.querySelector('#hijriCalendarModal .modal-body');
    
    if (modalBody) {
        modalBody.innerHTML = `
            <div class="p-4">
                <h4 class="text-center text-success mb-4">
                    <i class="bi bi-calendar-heart"></i> Kalender Hijriah Sederhana
                </h4>
                
                <div class="alert alert-info">
                    <p><strong>Sya'ban 1446 H</strong></p>
                    <p>Kalender Hijriah sederhana untuk referensi.</p>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6>Bulan Hijriah:</h6>
                        <ul class="list-group">
                            ${['Muharram', 'Safar', 'Rabiul Awal', 'Rabiul Akhir', 
                               'Jumadil Awal', 'Jumadil Akhir', 'Rajab', 'Sya\'ban',
                               'Ramadan', 'Syawal', 'Dzulqa\'dah', 'Dzulhijjah']
                              .map(month => `<li class="list-group-item">${month}</li>`).join('')}
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Hari Besar Islam:</h6>
                        <ul class="list-group">
                            <li class="list-group-item">1 Muharram - Tahun Baru Hijriyah</li>
                            <li class="list-group-item">10 Muharram - Hari Asyura</li>
                            <li class="list-group-item">12 Rabiul Awal - Maulid Nabi</li>
                            <li class="list-group-item">27 Rajab - Isra Mi'raj</li>
                            <li class="list-group-item">1 Ramadan - Awal Puasa</li>
                            <li class="list-group-item">1 Syawal - Idul Fitri</li>
                            <li class="list-group-item">10 Dzulhijjah - Idul Adha</li>
                        </ul>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button class="btn btn-success" onclick="window.open('${window.location.origin}/public/dash/tahun_hijriah.html', '_blank')">
                        <i class="bi bi-box-arrow-up-right"></i> Buka Kalender di Tab Baru
                    </button>
                </div>
            </div>
        `;
    }
}
</script>
@endpush