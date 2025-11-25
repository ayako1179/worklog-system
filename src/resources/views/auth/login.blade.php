@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css')}}">
@endsection

@section('content')
<div class="auth auth--login">
  <h1 class="auth__title">ログイン</h1>

  <form action="{{ route('login') }}" method="POST" class="auth__form">
    @csrf

    <div class="auth__group">
      <label for="email" class="auth__label">メールアドレス</label>
      <input type="text" id="email" name="email" value="{{ old('email') }}">

      @error('email')
      <p class="error">{{ $message }}</p>
      @enderror
    </div>

    <div class="auth__group">
      <label for="password" class="auth__label">パスワード</label>
      <input type="password" id="password" name="password">

      @error('password')
      <p class="error">{{ $message }}</p>
      @enderror
    </div>

    <button type="submit" class="btn--auth">ログインする</button>
  </form>

  <div class="auth__link">
    <a href="{{ route('register') }}">会員登録はこちら</a>
  </div>
</div>
@endsection