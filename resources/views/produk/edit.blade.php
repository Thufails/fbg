@extends('layouts.appform')
@section('content')
  <h3 class="mb-4"><i class="bi bi-pencil-square"></i> Edit Data Produk</h3>
  <form action="{{ route('produk.update', $produk->kd_produk) }}" method="POST">
    @csrf
    @method('PUT') <!-- Menyatakan bahwa ini adalah request PUT -->
    
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-upc-scan"></i> Kode Produk</label>
      <input type="text" name="kd_produk" class="form-control" value="{{ old('kd_produk', $produk->kd_produk) }}" readonly>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-box"></i> Nama Produk</label>
      <input type="text" name="nama_produk" class="form-control" value="{{ old('nama_produk', $produk->nama_produk) }}" required>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-cash-coin"></i> Harga</label>
      <input type="number" name="harga" class="form-control" value="{{ old('harga', $produk->harga) }}" required>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-card-text"></i> Keterangan</label>
      <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan', $produk->keterangan) }}</textarea>
    </div>

    <div class="d-flex justify-content-between">
      <a href="{{ route('produk.view') }}" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Kembali</a>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save2"></i> Simpan Perubahan</button>
    </div>

  </form>
</div>
@endsection