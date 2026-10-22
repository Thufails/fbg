@extends('layouts.app')
@section('title','Update Data')
@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card p-4">
            <h1>Update Data</h1>            
            <form action="{{ route('user.updateData.post') }}" method="post">
                @include('layouts.notif')
                @csrf
                <div class="mb-2">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" value="{{ old('name')?old('name'):Auth::user()->email }}" name="email" aria-describedby="emailHelp" disabled>
                </div>
                <div class="mb-2">
                    <label for="name" class="form-label">Nama</label>
                    <input type="text" class="form-control" id="name" value="{{ Auth::user()->name }}" name="name">
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
                    <button type="submit" class="btn btn-primary">Update Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection