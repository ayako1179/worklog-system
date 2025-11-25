@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css')}}">
@endsection

@section('content')
<div class="auth auth--register">
  <h1 class="auth__title">会員登録</h1>

  <form action="{{ route('register') }}" method="POST" class="auth__form">
    @csrf

    <div class="auth__group">
      <label for="name" class="auth__label">名前</label>
      <input type="text" id="name" name="name" value="{{ old('name') }}">
      @error('name')
      <p class="error">{{ $message }}</p>
      @enderror
    </div>

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

    <div class="auth__group">
      <label for="password_confirmation" class="auth__label">確認用パスワード</label>
      <input type="password" id="password_confirmation" name="password_confirmation">
      @error('password_confirmation')
      <p class="error">{{ $message }}</p>
      @enderror
    </div>

    <button type="submit" class="btn--auth">登録する</button>
  </form>

  <div class="auth__link">
    <a href="{{ route('login') }}">ログインはこちら</a>
  </div>
</div>
@endsection