@extends('layouts.app')
@section('title', 'Keranjang Realisasi')
@section('content')
<div class="container-fluid">
    <h4 class="mb-3"><i class="bi bi-cart"></i> Keranjang Realisasi</h4>
    
    <div class="card mb-4">
        <div class="card-body">
            @if(empty($cartItems))
                <div class="alert alert-warning">Keranjang realisasi Anda kosong.</div>
            @else
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5>Detail Akun: {{ $akunInfo->kd_detail_akun ?? '' }} - {{ $akunInfo->nama_detail_akun ?? '' }}</h5>
                    </div>
                    <div>
                        <a href="{{ route('detailRealisasi.emptyCart') }}" class="btn btn-danger me-2">
                            <i class="bi bi-trash me-1"></i> Kosongkan Keranjang
                        </a>
                        <a href="{{ route('detailRealisasi.select') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Pilih Target
                        </a>
                    </div>
                </div>
                
                <!-- Form untuk menyimpan realisasi -->
                <form action="{{ route('detailRealisasi.store') }}" method="POST">
                    @csrf
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th width="60">No</th>
                                    <th>Tanggal</th>
                                    <th>Akun Detail</th>
                                    <th>Nilai Realisasi</th>
                                    <th>Keterangan</th>
                                    <th width="100">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cartItems as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item['tanggal'])->format('d-m-Y') }}</td>
                                    <td>{{ $item['nama_detail_akun'] }}</td>
                                    <td>
                                        <input type="number" 
                                               name="realisasi[{{ $index }}][nilai]" 
                                               class="form-control" 
                                               value="{{ $item['nilai'] ?? 0 }}" 
                                               required>
                                        <input type="hidden" 
                                               name="realisasi[{{ $index }}][tanggal]" 
                                               value="{{ $item['tanggal'] }}">
                                        <input type="hidden" 
                                               name="realisasi[{{ $index }}][kd_target_detail]" 
                                               value="{{ $item['kd_target_detail'] }}">
                                    </td>
                                    <td>
                                        <input type="text" 
                                               name="realisasi[{{ $index }}][keterangan]" 
                                               class="form-control" 
                                               value="{{ $item['keterangan'] ?? '' }}">
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('detailRealisasi.removeFromCart', $index) }}" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Hapus item ini dari keranjang?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan Realisasi
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection