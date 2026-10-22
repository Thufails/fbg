@extends('layouts.appform')
@section('content')
  <h3 class="mb-4"><i class="bi bi-eye"></i> Preview Konversi Tanggal</h3>
  
  <div class="card mb-4">
    <div class="card-header bg-info text-white">
      <i class="bi bi-info-circle"></i> Ringkasan Konversi
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <table class="table table-sm">
            <tr>
              <th width="40%">Tanggal Masehi Awal:</th>
              <td>{{ date('d-m-Y', strtotime($inputData['tanggal_awal'])) }}</td>
            </tr>
            <tr>
              <th>Tanggal Masehi Akhir:</th>
              <td>{{ date('d-m-Y', strtotime($inputData['tanggal_akhir'])) }}</td>
            </tr>
            <tr>
              <th>Total Hari:</th>
              <td><strong>{{ $totalHari }} hari</strong></td>
            </tr>
          </table>
        </div>
        <div class="col-md-6">
          <table class="table table-sm">
            <tr>
              <th width="40%">Bulan Hijriah:</th>
              <td>{{ $inputData['bulan_h'] }}</td>
            </tr>
            <tr>
              <th>Tahun Hijriah:</th>
              <td>{{ $inputData['tahun_h'] }} H</td>
            </tr>
            <tr>
              <th>Periode:</th>
              <td>{{ $periode }}</td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header bg-warning text-dark">
      <i class="bi bi-table"></i> Preview Data Konversi ({{ $totalHari }} hari)
    </div>
    <div class="card-body">
      <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
        <table class="table table-bordered table-hover table-striped">
          <thead class="table-dark" style="position: sticky; top: 0;">
            <tr>
              <th class="text-center">No</th>
              <th class="text-center">Tanggal Masehi</th>
              <th class="text-center">Tanggal Hijriah</th>
              <th class="text-center">Hari H</th>
              <th class="text-center">Bulan H</th>
              <th class="text-center">Tahun H</th>
            </tr>
          </thead>
          <tbody>
            @foreach($previewData as $index => $data)
            <tr>
              <td class="text-center">{{ $index + 1 }}</td>
              <td class="text-center">{{ $data['tanggal_masehi_formatted'] }}</td>
              <td class="text-center">{{ $data['tanggal_hijriah'] }}</td>
              <td class="text-center">{{ $data['hari_h'] }}</td>
              <td class="text-center">{{ $data['bulan_h_nama'] }}</td>
              <td class="text-center">{{ $data['tahun_h'] }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-primary text-white">
      <i class="bi bi-check-circle"></i> Konfirmasi Penyimpanan
    </div>
    <div class="card-body">
      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> 
        <strong>Perhatian:</strong> Pastikan data di atas sudah benar sebelum menyimpan. 
        Data yang sudah disimpan tidak dapat diubah.
      </div>

      <form action="{{ route('konversiTanggal.store') }}" method="POST">
        @csrf
        <!-- Tidak perlu hidden fields karena data sudah disimpan di session -->

        <div class="d-flex justify-content-between">
          <a href="{{ route('konversiTanggal.create') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle"></i> Kembali & Edit
          </a>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-save2"></i> Simpan Konversi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection