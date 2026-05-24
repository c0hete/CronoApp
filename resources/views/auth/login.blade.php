@extends('layouts.app')

@section('title', 'Ingresar')

@section('content')
<div class="card" style="max-width: 380px; margin: 3rem auto;">
    <h2 style="margin-top:0;">{{ $branding->nombre() }}</h2>
    <p style="color:#6b7280; margin-top:-.4rem;">Panel de gestión</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password" required>

        <label style="font-weight:400; margin-top:.8rem;">
            <input type="checkbox" name="remember" style="width:auto;"> Recordarme
        </label>

        <button class="btn" type="submit" style="margin-top:1rem; width:100%;">Ingresar</button>
    </form>
</div>
@endsection
