@extends('layouts.appform')
@section('content')
  <h3 class="mb-4"><i class="bi bi-plus-circle"></i> Tambah Data Produk</h3>
  <form action="{{ route('produk.store') }}" method="POST">
    @csrf
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-upc-scan"></i> Kode Produk</label>
      <input type="text" name="kd_produk" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-box"></i> Nama Produk</label>
      <input type="text" name="nama_produk" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-cash-coin"></i> Harga</label>
      <input type="number" name="harga" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-card-text"></i> Keterangan</label>
      <textarea name="keterangan" class="form-control" rows="3"></textarea>
    </div>

    <div class="d-flex justify-content-between">
      <a href="{{ route('produk.view') }}" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Kembali</a>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save2"></i> Simpan</button>
    </div>

  </form>
</div>
@endsection
