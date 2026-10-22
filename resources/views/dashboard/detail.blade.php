<!-- resources/views/dashboard/detail.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Detail Performance - {{ $bulan }} - {{ $akun->nama_akun }}</h1>
    
    @if($akunDetails->count() > 0)
    <div class="alert alert-info">
        Menampilkan detail untuk akun: <strong>{{ $akun->nama_akun }}</strong>
    </div>
    @endif
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Kode Akun</th>
                            <th>Nama Akun</th>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Target</th>
                            <th>Realisasi</th>
                            <th>GAP</th>
                            <th>% Gap</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detailData as $item)
                        @php
                            $gap = $item->realisasi - $item->target;
                            $persen_gap = $item->target > 0 ? ($gap / $item->target) * 100 : 0;
                        @endphp
                        <tr>
                            <td>{{ $item->kd_akun }}</td>
                            <td>{{ $item->nama_akun }}</td>
                            <td>{{ $item->tanggal }}</td>
                            <td>{{ $item->hari }}</td>
                            <td>{{ number_format($item->target, 0, ',', '.') }}</td>
                            <td>{{ number_format($item->realisasi, 0, ',', '.') }}</td>
                            <td class="{{ $gap >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($gap, 0, ',', '.') }}
                            </td>
                            <td class="{{ $persen_gap >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($persen_gap, 2) }}%
                            </td>
                            <td>{{ $item->keterangan_realisasi ?: $item->keterangan_target }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <a href="{{ route('dashboard.view') }}" class="btn btn-secondary mt-3">Kembali ke Dashboard</a>
</div>
@endsection