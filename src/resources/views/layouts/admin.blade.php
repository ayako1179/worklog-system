<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>勤怠管理アプリ</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="{{ asset('css/common.css')}}">
  @yield('css')
</head>

<body>
  <div class="app">
    <header class="header">
      <div class="header__logo">
        <a href="{{ route('admin.attendance.list') }}">
          <img src="{{ asset('images/logo.svg') }}" alt="coachtech">
        </a>
      </div>

      @if (!in_array(Route::currentRouteName(), ['login']))

      <nav class="header__nav">
        @auth
        <a href="{{ route('admin.attendance.list') }}">勤怠一覧</a>

        <a href="{{ route('admin.staff') }}">スタッフ一覧</a>

        <a href="{{ route('correction.index') }}">申請一覧</a>

        <form action="{{ route('logout') }}" method="POST" class="inline-form">
          @csrf
          <button type="submit" class="logout-link">ログアウト</button>
        </form>
        @endauth
      </nav>
      @endif
    </header>

    <div class="content">

      @yield('content')

    </div>
  </div>
  @yield('scripts')
</body>

</html>