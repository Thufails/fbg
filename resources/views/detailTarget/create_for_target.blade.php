@extends('layouts.app')
@section('title', 'Tambah Detail Target')
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Tambah Detail Target</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Akun</label>
                        <input type="text" class="form-control" value="{{ $target->nama_akun }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Entitas</label>
                        <input type="text" class="form-control" value="{{ $target->nama_entitas ?? '-' }}" readonly>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Pilih Tanggal</label>
                <select class="form-select" id="dateSelector">
                    @foreach($availableDates as $date)
                        <option value="{{ $date['date'] }}" {{ $date['date'] == $currentDate ? 'selected' : '' }}>
                            {{ $date['formatted'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Form for adding new details -->
            <form id="detailTargetForm">
                @csrf
                <input type="hidden" name="kd_target" value="{{ $target->kd_target }}">
                <input type="hidden" name="tanggal" id="selectedDate" value="{{ $currentDate }}">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="kd_detail_akun" class="form-label">Detail Akun</label>
                        <select name="kd_detail_akun" id="kd_detail_akun" class="form-select" required>
                            <option value="">Pilih Detail Akun</option>
                            @foreach($detailAkunList as $detailAkun)
                                <option value="{{ $detailAkun->kd_detail_akun }}">
                                    {{ $detailAkun->kd_detail_akun }} - {{ $detailAkun->nama_detail_akun }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="nilai" class="form-label">Nilai Target</label>
                        <input type="text" name="nilai" id="nilai" class="form-control" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-primary" id="addDetailBtn">
                        <i class="bi bi-plus-circle me-1"></i> Tambahkan
                    </button>
                </div>
            </form>

            <!-- Display details for selected date -->
            <div class="mt-4">
                <h5 class="mb-3">Detail Target untuk <span id="currentDateLabel">{{ Carbon\Carbon::parse($currentDate)->format('d-m-Y') }}</span></h5>
                <div id="detailsContainer">
                    @foreach($selectedDateDetails['details'] ?? [] as $detail)
                        <div class="detail-item mb-2 p-2 border rounded d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $detail['nama_detail_akun'] ?? $detailAkunList->firstWhere('kd_detail_akun', $detail['kd_detail_akun'])->nama_detail_akun }}</strong>
                                <div>Rp {{ number_format($detail['nilai'], 0, ',', '.') }}</div>
                            </div>
                            <button class="btn btn-sm btn-danger remove-detail" data-temp-id="{{ $detail['temp_id'] }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="card-footer bg-light d-flex justify-content-between">
            <a href="{{ route('detailTarget.select') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <button type="button" class="btn btn-success" id="saveAllBtn">
                <i class="bi bi-save me-1"></i> Simpan Semua
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format nilai input and handle numeric only
    const nilaiInput = document.getElementById('nilai');
    
    // Format as Rupiah on input
    nilaiInput.addEventListener('input', function(e) {
        // Remove all non-digit characters
        let value = this.value.replace(/\D/g, '');
        
        // Format as currency if not empty
        if (value.length > 0) {
            value = parseInt(value, 10).toLocaleString('id-ID');
        }
        
        this.value = value;
        this.dataset.rawValue = value.replace(/\D/g, ''); // Store raw numeric value
    });

    // Initialize raw value
    nilaiInput.dataset.rawValue = '';

    // Date selector change handler
    document.getElementById('dateSelector').addEventListener('change', function() {
        const selectedDate = this.value;
        document.getElementById('selectedDate').value = selectedDate;
        
        // Format date for display
        const dateObj = new Date(selectedDate);
        const formattedDate = dateObj.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).replace(/\//g, '-');
        
        document.getElementById('currentDateLabel').textContent = formattedDate;
        loadDateDetails(selectedDate);
    });

    // Add detail button handler
    document.getElementById('addDetailBtn').addEventListener('click', function() {
        const kdDetailAkun = document.getElementById('kd_detail_akun').value;
        const nilai = document.getElementById('nilai').dataset.rawValue || '0';
        
        if (!kdDetailAkun) {
            alert('Silakan pilih detail akun terlebih dahulu');
            return;
        }
        
        if (!nilai || isNaN(nilai) || parseInt(nilai) <= 0) {
            alert('Nilai target harus berupa angka yang valid dan lebih besar dari 0');
            return;
        }
        
        addDetailToCart(kdDetailAkun, nilai);
    });

    // Save all button handler
    document.getElementById('saveAllBtn').addEventListener('click', function() {
        if (confirm('Apakah Anda yakin ingin menyimpan semua detail target?')) {
            window.location.href = "{{ route('detailTarget.storeFromCart') }}";
        }
    });

    // Function to load details for selected date
    function loadDateDetails(date) {
        fetch(`/detail-target/get-date-details?date=${date}&kd_target={{ $target->kd_target }}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                const container = document.getElementById('detailsContainer');
                container.innerHTML = '';
                
                if (data.details && data.details.length > 0) {
                    data.details.forEach(detail => {
                        const item = document.createElement('div');
                        item.className = 'detail-item mb-2 p-2 border rounded d-flex justify-content-between align-items-center';
                        item.innerHTML = `
                            <div>
                                <strong>${detail.nama_detail_akun}</strong>
                                <div>Rp ${formatRupiah(detail.nilai)}</div>
                            </div>
                            <button class="btn btn-sm btn-danger remove-detail" data-temp-id="${detail.temp_id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                        container.appendChild(item);
                    });
                } else {
                    container.innerHTML = '<p class="text-muted">Belum ada detail untuk tanggal ini</p>';
                }
            })
            .catch(error => {
                console.error('Error loading date details:', error);
                alert('Gagal memuat detail untuk tanggal ini');
            });
    }

    // Function to add detail to cart
    function addDetailToCart(kdDetailAkun, nilai) {
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('kd_target', '{{ $target->kd_target }}');
        formData.append('tanggal', document.getElementById('dateSelector').value);
        formData.append('kd_detail_akun', kdDetailAkun);
        formData.append('nilai', nilai);
        
        fetch('{{ route("detailTarget.addDetailToCart") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Reload details for current date
                loadDateDetails(document.getElementById('dateSelector').value);
                // Reset form
                document.getElementById('kd_detail_akun').value = '';
                document.getElementById('nilai').value = '';
                document.getElementById('nilai').dataset.rawValue = '';
            } else {
                throw new Error(data.message || 'Gagal menambahkan detail');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Terjadi kesalahan saat menambahkan detail');
        });
    }

    // Handle remove detail
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-detail')) {
            const tempId = e.target.closest('.remove-detail').dataset.tempId;
            removeDetailFromCart(tempId);
        }
    });

    function removeDetailFromCart(tempId) {
        if (!confirm('Apakah Anda yakin ingin menghapus detail ini?')) return;
        
        fetch('{{ route("detailTarget.removeDetailFromCart") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                kd_target: '{{ $target->kd_target }}',
                tanggal: document.getElementById('dateSelector').value,
                temp_id: tempId
            })
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                loadDateDetails(document.getElementById('dateSelector').value);
            } else {
                throw new Error(data.message || 'Gagal menghapus detail');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Terjadi kesalahan saat menghapus detail');
        });
    }

    // Helper function to format as Rupiah
    function formatRupiah(angka) {
        if (!angka) return '0';
        return parseInt(angka).toLocaleString('id-ID');
    }

    // Initial load of details for the default selected date
    loadDateDetails(document.getElementById('dateSelector').value);
});
</script>
@endsection