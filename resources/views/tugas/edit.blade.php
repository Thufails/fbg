@extends('layouts.appform')
@section('content')
<h3 class="mb-4"><i class="bi bi-card-checklist"></i> Edit Tugas</h3>
<form action="{{ route('tugas.update', $tugas->kd_tugas) }}" method="POST">
    @csrf
    @method('PUT')

    <!-- Input Kode Tugas -->
    <div class="mb-3">
        <label class="form-label"><i class="bi bi-tag"></i> Kode Tugas</label>
        <input type="text" name="kd_tugas" class="form-control" 
               value="{{ $tugas->kd_tugas }}" required>
    </div>

    <!-- Input Tanggal -->
    <div class="mb-3">
        <label class="form-label"><i class="bi bi-calendar-check"></i> Tanggal</label>
        <input type="date" name="tanggal" class="form-control" 
               value="{{ $tugas->tanggal }}" required>
    </div>

    <!-- Input Deskripsi -->
    <div class="mb-3">
        <label class="form-label"><i class="bi bi-card-text"></i> Deskripsi Tugas</label>
        <textarea name="uraian" class="form-control" rows="3" required>{{ $tugas->uraian }}</textarea>
    </div>

    <!-- Radio Button Status -->
    <div class="mb-3">
        <label class="form-label"><i class="bi bi-list-stars"></i> Status</label>
        <div class="form-radio-group d-flex gap-3">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="status" 
                       id="statusPlan" value="0" {{ $tugas->status == 0 ? 'checked' : '' }}>
                <label class="form-check-label" for="statusPlan">
                    <i class="bi bi-hourglass-top text-warning"></i> Plan
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="status" 
                       id="statusProses" value="1" {{ $tugas->status == 1 ? 'checked' : '' }}>
                <label class="form-check-label" for="statusProses">
                    <i class="bi bi-gear-wide-connected text-primary"></i> Proses
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="status" 
                       id="statusDone" value="2" {{ $tugas->status == 2 ? 'checked' : '' }}>
                <label class="form-check-label" for="statusDone">
                    <i class="bi bi-check-circle-fill text-success"></i> Done
                </label>
            </div>
        </div>
    </div>

    <!-- Tombol Action -->
    <div class="d-flex justify-content-between">
        <a href="{{ route('tugas.view') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save-fill"></i> Simpan Perubahan
        </button>
    </div>
</form>
@endsection