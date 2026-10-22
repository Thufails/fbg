@extends('layouts.app')
@section('title','Tambah User')
@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card p-4">
            <h1>Tambah User</h1>            
            <form action="{{ route('user.store') }}" method="post">
                @include('layouts.notif')
                @csrf
                <div class="mb-2">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" value="{{ old('email') }}" name="email" aria-describedby="emailHelp">
                </div>
                <div class="mb-2">
                    <label for="name" class="form-label">Nama</label>
                    <input type="text" class="form-control" id="name" value="{{ old('name') }}" name="name">
                </div>
                <div class="mb-2">
                    <label for="level" class="form-label">Level</label>
                    <select class="form-select" id="level" name="level">
                        <option value="" disabled selected>Pilih Level</option>
                        <option value="admin" {{ old('level') == 'admin' ? 'selected' : '' }}>Administrator</option>
                        <option value="user" {{ old('level') == 'user' ? 'selected' : '' }}>User</option>
                        <option value="analis" {{ old('level') == 'analis' ? 'selected' : '' }}>Analis</option>
                        <option value="guest" {{ old('level') == 'guest' ? 'selected' : '' }}>Guest</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" id="password">
                </div>
                <div class="mb-2">
                    <label for="password-confirmation" class="form-label">Konfirmasi Password</label>
                    <input type="password" class="form-control" name="password_confirmation" id="password_confirmation">
                </div>
                <div class="d-inline">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection