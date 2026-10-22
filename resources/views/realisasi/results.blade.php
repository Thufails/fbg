@extends('layouts.app')
@section('title', 'Hasil Penyimpanan Realisasi')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-list-check"></i> Hasil Penyimpanan Realisasi</h4>
        <a href="{{ route('realisasi.inputMassal') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Input Massal
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Berhasil Disimpan</h6>
                            <h3>{{ count($results['success'] ?? []) }}</h3>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Gagal Disimpan</h6>
                            <h3>{{ count($results['failed'] ?? []) }}</h3>
                        </div>
                        <i class="bi bi-x-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Dilewati</h6>
                            <h3>{{ count($results['skipped'] ?? []) }}</h3>
                        </div>
                        <i class="bi bi-skip-forward-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Results -->
    @if(!empty($results['success']))
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-check-circle"></i> Realisasi Berhasil Disimpan</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Kode Target</th>
                            <th>Nilai Realisasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['success'] as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($item['tanggal'])->format('d-m-Y') }}</td>
                            <td>{{ $item['kd_target'] }}</td>
                            <td class="text-end">Rp {{ number_format($item['nilai'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if(!empty($results['failed']))
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-x-circle"></i> Realisasi Gagal Disimpan</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Kode Target</th>
                            <th>Alasan Kegagalan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['failed'] as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($item['tanggal'])->format('d-m-Y') }}</td>
                            <td>{{ $item['kd_target'] ?? 'N/A' }}</td>
                            <td class="text-danger">{{ $item['reason'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if(!empty($results['skipped']))
    <div class="card mb-4">
        <div class="card-header bg-warning text-white">
            <h5 class="mb-0"><i class="bi bi-skip-forward-circle"></i> Realisasi Dilewati</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Kode Target</th>
                            <th>Alasan</th>
                            <th>Nilai Existing</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['skipped'] as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($item['tanggal'])->format('d-m-Y') }}</td>
                            <td>{{ $item['kd_target'] ?? 'N/A' }}</td>
                            <td class="text-warning">{{ $item['reason'] }}</td>
                            <td class="text-end">
                                @if(isset($item['existing_value']))
                                    Rp {{ number_format($item['existing_value'], 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="text-center mt-4">
        <a href="{{ route('realisasi.inputMassal') }}" class="btn btn-primary me-2">
            <i class="bi bi-plus-circle"></i> Input Massal Lagi
        </a>
        <a href="{{ route('realisasi.view') }}" class="btn btn-success">
            <i class="bi bi-list-ul"></i> Lihat Daftar Realisasi
        </a>
    </div>
</div>
@endsection