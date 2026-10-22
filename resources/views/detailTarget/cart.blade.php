@extends('layouts.app')
@section('title', 'Keranjang')
@section('content')
<div class="container-fluid">
    <h4 class="mb-3">Keranjang</h4>
    <div class="card mb-4">
        <div class="card-body">
            @if(empty($cartItems))
                <div class="alert alert-warning">Keranjang Anda kosong.</div>
            @else
                <!-- Form untuk menyimpan ke tabel detail_target -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Simpan Target Detail</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('detailTarget.store') }}" method="POST">
                            @csrf
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="kd_detail_akun" class="form-label">Detail Akun</label>
                                    <select name="kd_detail_akun" id="kd_detail_akun" class="form-select" required>
                                        <option value="">Pilih Detail Akun</option>
                                        @foreach($detailAkunList as $detailAkun)
                                            <option value="{{ $detailAkun->kd_detail_akun }}" 
                                                @if(session('kd_akun') == $detailAkun->kd_akun) selected @endif>
                                                {{ $detailAkun->kd_detail_akun }} - {{ $detailAkun->nama_detail_akun }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="nilai" class="form-label">Nilai</label>
                                    <input type="number" name="nilai" id="nilai" class="form-control" required 
                                           placeholder="Masukkan nilai">
                                </div>
                            </div>
                            
                            <input type="hidden" name="cart_items" value="{{ json_encode($cartItems) }}">
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Detail Target
                                </button>
                                
                                <a href="{{ route('detailTarget.empty') }}" class="btn btn-danger">
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
                                <th>Kode Akun</th>
                                <th>Nilai</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cartItems as $index => $item)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($item['tanggal'])->format('d-m-Y') }}</td>
                                <td>{{ $item['kd_akun'] }}</td>
                                <td class="text-end">{{ isset($item['nilai']) ? number_format($item['nilai'], 0, ',', '.') : '-' }}</td>
                                <td class="text-center">
                                    <form action="{{ route('detailTarget.item.remove', $index) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection