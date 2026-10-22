@extends('layouts.login')
@section('title','Login')
@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card p-4">
            <h1>Login</h1>            
            <form action="{{ route('login.post') }}" method="post">
                @include('layouts.notif')
                @csrf
                <div class="mb-2">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" value="{{ old('email') }}" name="email" aria-describedby="emailHelp">
                </div>
                <div class="mb-2">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" id="password">
                </div>
                <div class="d-inline">
                    <button type="submit" class="btn btn-primary">Login Aplikasi</button>
                    <a href="{{ route('user.forgotPassword') }}">Lupa Password</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection