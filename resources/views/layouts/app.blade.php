<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Quiz' }}</title>
    <style>
        :root {
            color-scheme: dark;
            --bg:#0b1220; --bg2:#162449;
            --card:#111a2e;
            --text:#e7eaf0; --muted:#aab2c5;
            --border:#24304a;
            --surface: rgba(10,16,30,.8);
            --surface2: rgba(17,26,46,.7);
            --surface3: rgba(10,16,30,.65);
            --shadow: 0 10px 25px rgba(0,0,0,.25);
            --blue:#4f8cff; --green:#2bd576; --red:#ff5a6b;
        }
        :root[data-theme="light"] {
            color-scheme: light;
            --bg:#f6f8ff; --bg2:#e8efff;
            --card: rgba(255,255,255,.85);
            --text:#0b1220; --muted:#44506b;
            --border:#cfd8f3;
            --surface: rgba(255,255,255,.85);
            --surface2: rgba(255,255,255,.75);
            --surface3: rgba(255,255,255,.7);
            --shadow: 0 10px 25px rgba(10,18,32,.10);
        }
        * { box-sizing: border-box; }
        body { margin:0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; background: radial-gradient(1200px 600px at 10% 10%, var(--bg2), var(--bg)); color: var(--text); }
        a { color: inherit; text-decoration: none; }
        .container { max-width: 980px; margin: 0 auto; padding: 20px; }
        .nav { display:flex; gap:12px; align-items:center; justify-content:space-between; padding: 14px 0; }
        .brand { font-weight: 700; letter-spacing: .2px; }
        .navlinks { display:flex; gap:10px; flex-wrap: wrap; }
        .pill { display:inline-flex; align-items:center; gap:8px; padding: 8px 12px; border:1px solid var(--border); background: var(--surface2); border-radius: 999px; color: var(--text); }
        .pill:hover { border-color: rgba(79,140,255,.6); }
        .card { border:1px solid var(--border); background: var(--card); border-radius: 16px; padding: 16px; box-shadow: var(--shadow); backdrop-filter: blur(6px); }
        .grid { display:grid; gap:14px; }
        .row { display:flex; gap:12px; flex-wrap: wrap; }
        .h1 { font-size: 20px; margin: 0 0 10px; }
        .muted { color: var(--muted); }
        .input, .select, .btn { border-radius: 12px; border:1px solid var(--border); background: var(--surface); color: var(--text); padding: 10px 12px; }
        .input, .select { width: 100%; }
        textarea.input { min-height: 140px; resize: vertical; line-height: 1.55; }
        .prompt-multiline { white-space: pre-wrap; word-break: break-word; line-height: 1.55; }
        .question-prompt-html { line-height: 1.55; word-break: break-word; }
        .question-prompt-html p { margin: 0.45em 0; }
        .question-prompt-html p:first-child { margin-top: 0; }
        .question-prompt-html p:last-child { margin-bottom: 0; }
        .question-prompt-html ul, .question-prompt-html ol { margin: 0.45em 0 0.45em 1.25em; padding: 0; }
        .question-prompt-html li { margin: 0.2em 0; }
        .question-prompt-html h1, .question-prompt-html h2, .question-prompt-html h3 { margin: 0.6em 0 0.35em; font-size: 1.05em; }
        .ck.ck-editor__editable_inline { min-height: 220px; }
        .ck.ck-editor { --ck-border-radius: 12px; }
        :root[data-theme="dark"] .ck.ck-editor__main > .ck-editor__editable {
            background: rgba(10,16,30,.95); color: var(--text); border-color: var(--border);
        }
        :root[data-theme="light"] .ck.ck-editor__main > .ck-editor__editable {
            background: #fff; color: #0b1220; border-color: var(--border);
        }
        .btn { cursor:pointer; font-weight: 600; }
        .btn-primary { border-color: rgba(79,140,255,.7); background: rgba(79,140,255,.15); }
        .btn-primary:hover { background: rgba(79,140,255,.25); }
        .btn-ghost:hover { border-color: rgba(255,255,255,.25); }
        .badge { display:inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 12px; border:1px solid var(--border); color: var(--muted); }
        .success { border-color: rgba(43,213,118,.7) !important; box-shadow: 0 0 0 2px rgba(43,213,118,.2); }
        .danger { border-color: rgba(255,90,107,.7) !important; box-shadow: 0 0 0 2px rgba(255,90,107,.2); }
        .option { display:flex; gap:10px; align-items:center; padding: 10px 12px; border:1px solid var(--border); border-radius: 12px; background: var(--surface3); }
        .option input[type="radio"], .option input[type="checkbox"] { width: 18px; height: 18px; }
        .k { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; color: var(--muted); }
        .hr { height:1px; background: rgba(36,48,74,.8); border:0; margin: 14px 0; }
        .draglist { display:grid; gap:10px; }
        .dragitem { user-select:none; cursor: grab; }
        .dragitem.dragging { opacity: .55; cursor: grabbing; }
        .flash { padding: 10px 12px; border-radius: 12px; border:1px solid var(--border); background: var(--surface3); }
    </style>
    <script>
        (function () {
            const key = 'quiz_theme';
            const root = document.documentElement;
            const saved = localStorage.getItem(key);
            if (saved === 'light' || saved === 'dark') {
                root.dataset.theme = saved;
            } else {
                // default: dark
                root.dataset.theme = 'dark';
            }
        })();
    </script>
</head>
<body>
<div class="container">
    <div class="nav">
        <div class="brand">Quiz Builder</div>
        <div class="navlinks">
            <a class="pill" href="{{ route('quiz.index') }}">Danh sách câu hỏi</a>
            <a class="pill" href="{{ route('questions.create') }}">Tạo câu hỏi</a>
            <a class="pill" href="{{ route('categories.create') }}">Tạo category</a>
            <button class="pill" type="button" id="themeToggle" style="cursor:pointer">Sáng/Tối</button>
        </div>
    </div>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
        <div style="height:10px"></div>
    @endif

    @yield('content')
</div>
<script>
    (function () {
        const key = 'quiz_theme';
        const root = document.documentElement;
        const btn = document.getElementById('themeToggle');
        if (!btn) return;

        const applyLabel = () => {
            const t = root.dataset.theme === 'light' ? 'light' : 'dark';
            btn.textContent = t === 'light' ? 'Chế độ: Sáng' : 'Chế độ: Tối';
        };

        btn.addEventListener('click', () => {
            const next = root.dataset.theme === 'light' ? 'dark' : 'light';
            root.dataset.theme = next;
            localStorage.setItem(key, next);
            applyLabel();
        });

        applyLabel();
    })();
</script>
@stack('scripts')
</body>
</html>

