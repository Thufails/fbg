@extends('layouts.appform')
@section('content')
<h3 class="mb-4"><i class="bi bi-pencil-square"></i> Edit Issue</h3>
<form action="{{ route('issue.update', $issue->kd_issue) }}" method="POST">
    @csrf
    @method('PUT')

    <!-- Input Kode Issue (Saya tambahkan sesuai contoh sebelumnya) -->
    <div class="mb-3">
        <label class="form-label"><i class="bi bi-hash"></i> Kode Issue</label>
        <input type="text" name="kd_issue" class="form-control" 
               value="{{ $issue->kd_issue }}" required>
    </div>

    <!-- Input Tanggal -->
    <div class="mb-3">
        <label class="form-label"><i class="bi bi-calendar-day"></i> Tanggal</label>
        <input type="date" name="tanggal" class="form-control" 
               value="{{ $issue->tanggal }}" required>
    </div>

    <!-- Input Deskripsi -->
    <div class="mb-3">
        <label class="form-label"><i class="bi bi-info-circle"></i> Deskripsi Masalah</label>
        <textarea name="uraian" class="form-control" rows="3" required>{{ $issue->uraian }}</textarea>
    </div>

    <!-- Radio Button Status -->
    <div class="mb-3">
        <label class="form-label"><i class="bi bi-plus-slash-minus"></i> Status</label>
        <div class="form-radio-group d-flex gap-3"> <!-- Tambah column gap -->
            <div class="form-check">
                <input class="form-check-input" type="radio" name="status" 
                       id="statusPlan" value="0" {{ $issue->status == 0 ? 'checked' : '' }}>
                <label class="form-check-label" for="statusPlan">
                    <i class="bi bi-plus-circle text-success"></i> Plan
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="status" 
                       id="statusProses" value="1" {{ $issue->status == 1 ? 'checked' : '' }}>
                <label class="form-check-label" for="statusProses">
                    <i class="bi bi-arrow-repeat text-primary"></i> Proses
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="status" 
                       id="statusDone" value="2" {{ $issue->status == 2 ? 'checked' : '' }}>
                <label class="form-check-label" for="statusDone">
                    <i class="bi bi-check-circle text-danger"></i> Done
                </label>
            </div>
        </div>
    </div>

    <!-- Tombol Action -->
    <div class="d-flex justify-content-between">
        <a href="{{ route('issue.view') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle"></i> Kembali
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save2"></i> Simpan Perubahan
        </button>
    </div>
</form>
@endsection