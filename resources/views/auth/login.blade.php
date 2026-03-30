@extends('layouts.app')

@section('content')
    <div class="card" style="max-width: 520px; margin: 0 auto;">
        <div class="h1">Đăng nhập</div>
        <div class="muted" style="margin-bottom: 12px;">Đăng nhập để sử dụng các chức năng liên quan đến câu hỏi.</div>

        <form method="POST" action="{{ route('login.store') }}" class="grid">
            @csrf

            <div class="grid" style="gap:8px">
                <div class="k">Email</div>
                <input class="input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                @error('email')<div class="muted">{{ $message }}</div>@enderror
            </div>

            <div class="grid" style="gap:8px">
                <div class="k">Mật khẩu</div>
                <input class="input" type="password" name="password" autocomplete="current-password" required>
                @error('password')<div class="muted">{{ $message }}</div>@enderror
            </div>

            <label class="row" style="align-items:center; gap:8px">
                <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <span class="muted">Ghi nhớ đăng nhập</span>
            </label>

            <div class="row" style="justify-content:flex-end">
                <button class="btn" type="submit">Đăng nhập</button>
            </div>
        </form>
    </div>
@endsection

