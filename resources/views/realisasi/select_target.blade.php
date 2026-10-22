@extends('layouts.app')
@section('title', 'Pilih Target Realisasi')
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-calendar-check"></i> Pilih Target Realisasi</h4>  
  <!-- Search Form -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form action="{{ route('realisasi.select') }}" method="GET" class="mb-0">
        @csrf
        <div class="row g-3 align-items-end">
          <div class="col-md-2">
            <label for="start_date" class="form-label">Tanggal Awal</label>
            <input type="date" name="start_date" class="form-control" 
                   value="{{ request('start_date') }}" id="start_date">
          </div>
          <div class="col-md-2">
            <label for="end_date" class="form-label">Tanggal Akhir</label>
            <input type="date" name="end_date" class="form-control" 
                   value="{{ request('end_date') }}" id="end_date">
          </div>
          <div class="col-md-2">
            <label for="kd_akun" class="form-label">Akun</label>
            <select name="kd_akun" class="form-select" id="kd_akun">
              <option value="">Semua Akun</option>
              @foreach($akunList ?? [] as $akun)
                <option value="{{ $akun->kd_akun }}" {{ request('kd_akun') == $akun->kd_akun ? 'selected' : '' }}>
                  {{ $akun->kd_akun }} - {{ $akun->nama_akun }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label for="kd_entitas" class="form-label">Entitas</label>
            <select name="kd_entitas" class="form-select" id="kd_entitas">
              <option value="">Semua Entitas</option>
              @foreach($entitasList ?? [] as $entitas)
                <option value="{{ $entitas->kd_entitas }}" {{ request('kd_entitas') == $entitas->kd_entitas ? 'selected' : '' }}>
                  {{ $entitas->kd_entitas }} - {{ $entitas->nama_entitas }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-funnel me-1"></i> Filter
            </button>
            @if(request('start_date') || request('end_date') || request('kd_akun') || request('kd_entitas'))
            <a href="{{ route('realisasi.select') }}" class="btn btn-secondary w-100 mt-2">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </a>
            @endif
          </div>
        </div>
      </form>
    </div>
  </div>

  @if($targets->count() > 0)
    <form action="{{ route('realisasi.addToCart') }}" method="POST" id="addToCartForm">
      @csrf      
      <input type="hidden" value="{{ $kd_akun }}">
      <div class="card mb-4">
          <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
              <h5 class="mb-0">Daftar Target</h5>              
              <!-- Action Buttons Group -->
              <div class="btn-group" role="group">
                  <button type="submit" class="btn btn-primary btn-sm" id="addToCartBtn" disabled>
                      <i class="bi bi-cart-plus me-1"></i> Tambahkan ke Keranjang
                  </button>
                  <a href="{{ route('realisasi.cart') }}" class="btn btn-info btn-sm">
                      <i class="bi bi-cart me-1"></i> Lihat Keranjang 
                      @if(session('realisasi_cart') && count(session('realisasi_cart')) > 0)
                          <span class="badge bg-warning text-dark">{{ count(session('realisasi_cart')) }}</span>
                      @endif
                  </a>
              </div>
          </div>
          <div class="card-body">
              <div class="alert alert-info">
                  <i class="bi bi-info-circle me-1"></i>
                  Pilih target yang ingin ditambahkan ke keranjang dengan mencentang checkbox, kemudian klik tombol "Tambahkan ke Keranjang".
              </div>
              
              <div class="table-responsive">
                  <table class="table table-bordered table-hover align-middle">
                      <thead class="table-dark text-center">
                          <tr>
                              <th width="50">
                                  <input type="checkbox" id="selectAllTargets" class="form-check-input">
                              </th>
                              <th width="60">No</th>
                              <th>Tanggal</th>
                              <th>Entitas</th>
                              <th>Akun</th>
                              <th>Nilai Target</th>
                          </tr>
                      </thead>
                      <tbody>
                          @foreach($targets as $key => $target)
                          <tr>
                              <td class="text-center">
                                  <input type="checkbox" name="selected_targets[]" value="{{ $target->kd_target }}" 
                                        data-nilai="{{ $target->nilai }}" 
                                        data-kd-akun="{{ $target->kd_akun }}"
                                        class="form-check-input target-checkbox">
                              </td>
                              <td class="text-center">{{ ($targets->currentPage() - 1) * $targets->perPage() + $loop->iteration }}</td>
                              <td>{{ \Carbon\Carbon::parse($target->tanggal)->format('d-m-Y') }}</td>
                              <td>{{ $target->nama_entitas }}</td>
                              <td>{{ $target->nama_akun }}</td>
                              <td class="text-end">{{ number_format($target->nilai, 0, ',', '.') }}</td>
                          </tr>
                          @endforeach
                      </tbody>
                  </table>
              </div>
              
              <!-- Selected Items Summary -->
              <div id="selectedSummary" class="mt-3" style="display: none;">
                  <div class="alert alert-secondary">
                      <strong>Item yang dipilih: </strong>
                      <span id="selectedCount">0</span> target
                      <span class="ms-3"><strong>Total Nilai: </strong>Rp <span id="totalValue">0</span></span>
                  </div>
              </div>
          </div>
      </div>
  </form>
  @else
  <div class="alert alert-warning">
    @if(request('start_date') || request('end_date') || request('kd_akun') || request('kd_entitas'))
      Tidak ditemukan target dengan filter yang dipilih
    @else
      Tidak ada data target
    @endif
  </div>
  @endif

  <!-- Pagination -->
  @if($targets->hasPages())
  <div class="d-flex justify-content-center mt-4">
    {{ $targets->withQueryString()->links() }}
  </div>
  @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Select all targets checkbox
  const selectAll = document.getElementById('selectAllTargets');
  const targetCheckboxes = document.querySelectorAll('.target-checkbox');
  const addToCartBtn = document.getElementById('addToCartBtn');
  const selectedSummary = document.getElementById('selectedSummary');
  const selectedCount = document.getElementById('selectedCount');
  const totalValue = document.getElementById('totalValue');
  
  // Function to update selected items summary
  function updateSummary() {
    const checkedBoxes = document.querySelectorAll('.target-checkbox:checked');
    const count = checkedBoxes.length;
    
    if (count > 0) {
      let total = 0;
      checkedBoxes.forEach(checkbox => {
        total += parseFloat(checkbox.dataset.nilai) || 0;
      });
      
      selectedCount.textContent = count;
      totalValue.textContent = new Intl.NumberFormat('id-ID').format(total);
      selectedSummary.style.display = 'block';
      addToCartBtn.disabled = false;
    } else {
      selectedSummary.style.display = 'none';
      addToCartBtn.disabled = true;
    }
  }
  
  // Select all functionality
  selectAll.addEventListener('change', function() {
    targetCheckboxes.forEach(checkbox => {
      checkbox.checked = this.checked;
    });
    updateSummary();
  });
  
  // Individual checkbox change
  targetCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      // Update select all checkbox state
      const checkedCount = document.querySelectorAll('.target-checkbox:checked').length;
      const totalCount = targetCheckboxes.length;
      
      if (checkedCount === 0) {
        selectAll.indeterminate = false;
        selectAll.checked = false;
      } else if (checkedCount === totalCount) {
        selectAll.indeterminate = false;
        selectAll.checked = true;
      } else {
        selectAll.indeterminate = true;
      }
      
      updateSummary();
    });
  });
  
  // Initial summary update
  updateSummary();
  
  // Form submission validation
  document.getElementById('addToCartForm').addEventListener('submit', function(e) {
    const checkedBoxes = document.querySelectorAll('.target-checkbox:checked');
    if (checkedBoxes.length === 0) {
      e.preventDefault();
      alert('Pilih minimal satu target untuk ditambahkan ke keranjang.');
      return false;
    }
  });
});
</script>

<style>
.btn-group .btn {
  margin-left: 5px;
}

.btn-group .btn:first-child {
  margin-left: 0;
}

@media (max-width: 768px) {
  .card-header .btn-group {
    margin-top: 10px;
    width: 100%;
  }
  
  .card-header .btn-group .btn {
    flex: 1;
    margin: 2px;
  }
  
  .card-header {
    flex-direction: column;
    align-items: stretch !important;
  }
}

.badge {
  font-size: 0.7em;
}
</style>
@endsection