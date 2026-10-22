@extends('layouts.app')
@section('title', 'Edit Penjualan')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Penjualan</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('penjualan.update', $penjualan->kd_penjualan) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="tanggal" class="form-label">Tanggal</label>
                                <input type="date" name="tanggal" id="tanggal" class="form-control @error('tanggal') is-invalid @enderror" 
                                       value="{{ old('tanggal', $penjualan->tanggal->format('Y-m-d')) }}" required>
                                @error('tanggal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="kd_entitas" class="form-label">Entitas</label>
                                <select name="kd_entitas" id="kd_entitas" class="form-select @error('kd_entitas') is-invalid @enderror">
                                    <option value="">Pilih Entitas</option>
                                    @foreach($entitasList as $entitas)
                                        <option value="{{ $entitas->kd_entitas }}" 
                                            @if(old('kd_entitas', $penjualan->kd_entitas) == $entitas->kd_entitas) selected @endif>
                                            {{ $entitas->kd_entitas }} - {{ $entitas->nama_entitas }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('kd_entitas')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="kd_produk" class="form-label">Produk</label>
                                <select name="kd_produk" id="kd_produk" class="form-select @error('kd_produk') is-invalid @enderror" required>
                                    <option value="">Pilih Produk</option>
                                    @foreach($produkList as $produk)
                                        <option value="{{ $produk->kd_produk }}" 
                                            @if(old('kd_produk', $penjualan->kd_produk) == $produk->kd_produk) selected @endif>
                                            {{ $produk->kd_produk }} - {{ $produk->nama_produk }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('kd_produk')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="harga" class="form-label">Harga</label>
                                <input type="number" name="harga" id="harga" class="form-control @error('harga') is-invalid @enderror" 
                                       value="{{ old('harga', $penjualan->harga) }}" min="0" required>
                                @error('harga')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="jumlah" class="form-label">Jumlah</label>
                                <input type="number" name="jumlah" id="jumlah" class="form-control @error('jumlah') is-invalid @enderror" 
                                       value="{{ old('jumlah', $penjualan->jumlah) }}" min="1" required>
                                @error('jumlah')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('penjualan.view') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection