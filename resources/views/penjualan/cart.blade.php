@extends('layouts.app')
@section('title', 'Keranjang Penjualan')
@section('content')
<div class="container-fluid">
    <h4 class="mb-3"><i class="bi bi-cart"></i> Keranjang Penjualan</h4>
    <div class="card mb-4">
        <div class="card-body">
            @if(empty($cart))
                <div class="alert alert-warning">Keranjang Anda kosong.</div>
            @else
                <!-- Form untuk menyimpan penjualan -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Simpan Penjualan</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('penjualan.storeCart') }}" method="POST">
                            @csrf
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="tanggal" class="form-label">Tanggal Penjualan</label>
                                    <input type="date" name="tanggal" id="tanggal" class="form-control" 
                                           value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Semua Penjualan
                                </button>
                                
                                <a href="{{ route('penjualan.emptyCart') }}" class="btn btn-danger">
                                    <i class="bi bi-trash"></i> Kosongkan Keranjang
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabel keranjang -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th width="50">No</th>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cart as $index => $item)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($item['tanggal'])->format('d-m-Y') }}</td>
                                <td>{{ $item['nama_produk'] }}</td>
                                <td class="text-end">{{ number_format($item['harga'], 0, ',', '.') }}</td>
                                <td class="text-center">{{ $item['jumlah'] }}</td>
                                <td class="text-end">{{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <form action="{{ route('penjualan.removeFromCart', $index) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            <tr class="table-active">
                                <td colspan="5" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold">{{ number_format(array_sum(array_column($cart, 'subtotal')), 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    
    <div class="d-flex justify-content-between">
        <a href="{{ route('penjualan.insert') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Tambah Item Baru
        </a>
        <a href="{{ route('penjualan.view') }}" class="btn btn-secondary">
            <i class="bi bi-list"></i> Lihat Daftar Penjualan
        </a>
    </div>
</div>
@endsection