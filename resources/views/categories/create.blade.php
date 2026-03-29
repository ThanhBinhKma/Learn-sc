@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="h1">Tạo category</div>
        <div class="muted">Dùng để gom nhóm câu hỏi (tùy chọn khi tạo/sửa câu).</div>
        <hr class="hr" />

        <form method="POST" action="{{ route('categories.store') }}" class="grid" style="gap:12px; max-width:480px">
            @csrf
            <div class="grid" style="gap:8px">
                <div class="k">Tên category</div>
                <input class="input" type="text" name="name" value="{{ old('name') }}" placeholder="Ví dụ: SC-200, Network..." maxlength="255" required>
                @error('name')<div class="muted">{{ $message }}</div>@enderror
            </div>
            <div class="row" style="justify-content:flex-end">
                <button class="btn btn-primary" type="submit">Lưu category</button>
            </div>
        </form>
    </div>
@endsection
