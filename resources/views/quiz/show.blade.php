@extends('layouts.app')

@section('content')
    @php
        $checked = (bool) ($attempt && $attempt->checked_at);
        $selected = $attempt ? ($attempt->selected ?? []) : [];
        $selectedSet = array_flip(array_map('intval', $selected));
        $isCorrect = $attempt ? (bool) $attempt->is_correct : false;
    @endphp

    <div class="card {{ $checked && $isCorrect ? 'success' : '' }} {{ $checked && !$isCorrect ? 'danger' : '' }}">
        <div class="row" style="align-items:flex-start; justify-content:space-between">
            <div style="flex:1">
                <div class="h1">Câu hỏi #{{ $question->id }}</div>
                <div class="muted">
                    <span class="badge">{{ $question->type }}</span>
                    @if ($question->category)
                        <span class="badge" style="border-color: rgba(79,140,255,.55);">{{ $question->category->name }}</span>
                    @endif
                    @if ($isFlagged)
                        <span class="badge" style="border-color: rgba(255,221,87,.95); color: #ffe38a;">Flag</span>
                    @endif
                    @if ($checked)
                        <span class="badge">{{ $isCorrect ? 'ĐÚNG' : 'SAI' }}</span>
                    @endif
                </div>
            </div>
            <div class="row" style="gap:8px; flex-shrink:0">
                <a class="btn btn-ghost" href="{{ route('questions.edit', $question) }}">Sửa</a>
                <a class="btn btn-ghost" href="{{ route('quiz.index') }}">← Danh sách</a>
            </div>
        </div>

        <hr class="hr" />

        <div class="question-prompt-html" style="font-size:16px">{!! \App\Models\Question::renderPromptForDisplay($question->prompt) !!}</div>
        <div style="height:12px"></div>

        @if ($checked && filled($question->keyword))
            <div class="option" style="margin-bottom:12px; border-color: rgba(79,140,255,.45); background: var(--surface3);">
                <div style="flex:1">
                    <div class="k" style="margin-bottom:4px">Keyword</div>
                    <div style="font-size:15px; line-height:1.45; white-space:pre-wrap">{{ $question->keyword }}</div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('quiz.check', $question) }}" id="quizForm" class="grid" style="gap:10px">
            @csrf

            @if (in_array($question->type, ['choice', 'select'], true))
                @if ($question->type === 'select')
                    @php
                        $select1 = $question->options->where('select_group', 1)->values();
                        $select2 = $question->options->where('select_group', 2)->values();
                    @endphp
                    <div class="grid" style="gap:8px">
                        <div class="k">Select 1</div>
                        <select class="select" name="selected_select_1" {{ $checked ? 'disabled' : '' }}>
                            <option value="">-- Chọn đáp án --</option>
                            @foreach ($select1 as $opt)
                                <option value="{{ $opt->id }}" {{ isset($selectedSet[(int) $opt->id]) ? 'selected' : '' }}>
                                    {{ $opt->text }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid" style="gap:8px">
                        <div class="k">Select 2</div>
                        <select class="select" name="selected_select_2" {{ $checked ? 'disabled' : '' }}>
                            <option value="">-- Chọn đáp án --</option>
                            @foreach ($select2 as $opt)
                                <option value="{{ $opt->id }}" {{ isset($selectedSet[(int) $opt->id]) ? 'selected' : '' }}>
                                    {{ $opt->text }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    @foreach ($question->options as $opt)
                        @php
                            $isSelected = isset($selectedSet[(int) $opt->id]);
                        @endphp
                        <label class="option">
                            <input type="radio" name="selected_one" value="{{ $opt->id }}" {{ $isSelected ? 'checked' : '' }} {{ $checked ? 'disabled' : '' }}>
                            <div style="flex:1">{{ $opt->text }}</div>
                        </label>
                    @endforeach
                @endif

            @elseif ($question->type === 'multi_choice')
                @foreach ($question->options as $opt)
                    @php
                        $isSelected = isset($selectedSet[(int) $opt->id]);
                    @endphp
                    <label class="option">
                        <input type="checkbox" name="selected_many[]" value="{{ $opt->id }}" {{ $isSelected ? 'checked' : '' }} {{ $checked ? 'disabled' : '' }}>
                        <div style="flex:1">{{ $opt->text }}</div>
                    </label>
                @endforeach

            @elseif ($question->type === 'drag_drop')
                <div class="muted">Kéo thả để sắp xếp theo đúng thứ tự, sau đó bấm Check.</div>
                <div id="dragList" class="draglist">
                    @php
                        $byId = $question->options->keyBy('id');
                        $order = $checked && count($selected) ? array_values($selected) : $question->options->pluck('id')->all();
                    @endphp

                    @foreach ($order as $oid)
                        @php $opt = $byId[(int) $oid] ?? null; @endphp
                        @if ($opt)
                            <div class="option dragitem" draggable="{{ $checked ? 'false' : 'true' }}" data-option-id="{{ $opt->id }}">
                                <div class="k" style="width:36px; text-align:center">⋮⋮</div>
                                <div style="flex:1">{{ $opt->text }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
                <div id="orderedHidden"></div>
            @endif

            <div class="row" style="justify-content:flex-end; align-items:center">
                <button class="btn btn-ghost" type="submit" formaction="{{ route('quiz.flag', $question) }}">
                    {{ $isFlagged ? 'Bỏ flag' : 'Flag câu này' }}
                </button>
                @if ($checked)
                    <div class="muted" style="margin-right:auto">Đã lưu đáp án lúc {{ optional($attempt->checked_at)->format('H:i:s d/m/Y') }}</div>
                    <button class="btn btn-ghost" type="submit" formaction="{{ route('quiz.reset', $question) }}">Reset</button>
                    <a class="btn btn-ghost" href="{{ route('quiz.show', $question) }}">Tải lại</a>
                @else
                    <button class="btn btn-primary" type="submit">Check</button>
                @endif
            </div>
        </form>
    </div>

    @if ($question->type === 'drag_drop')
        <script>
            (function () {
                const checked = {{ $checked ? 'true' : 'false' }};
                if (checked) return;

                const list = document.getElementById('dragList');
                const orderedHidden = document.getElementById('orderedHidden');
                const form = document.getElementById('quizForm');

                let dragging = null;

                function items() {
                    return Array.from(list.querySelectorAll('.dragitem'));
                }

                function rebuildHidden() {
                    orderedHidden.innerHTML = '';
                    items().forEach((el, idx) => {
                        const id = el.getAttribute('data-option-id');
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `ordered[${idx}]`;
                        input.value = id;
                        orderedHidden.appendChild(input);
                    });
                }

                function handleDragStart(e) {
                    dragging = e.currentTarget;
                    dragging.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                }

                function handleDragEnd() {
                    if (dragging) dragging.classList.remove('dragging');
                    dragging = null;
                    rebuildHidden();
                }

                function handleDragOver(e) {
                    e.preventDefault();
                    const after = getDragAfterElement(list, e.clientY);
                    if (!dragging) return;
                    if (after == null) {
                        list.appendChild(dragging);
                    } else {
                        list.insertBefore(dragging, after);
                    }
                }

                function getDragAfterElement(container, y) {
                    const els = items().filter(el => el !== dragging);
                    return els.reduce((closest, child) => {
                        const box = child.getBoundingClientRect();
                        const offset = y - box.top - box.height / 2;
                        if (offset < 0 && offset > closest.offset) {
                            return { offset, element: child };
                        }
                        return closest;
                    }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
                }

                items().forEach(el => {
                    el.addEventListener('dragstart', handleDragStart);
                    el.addEventListener('dragend', handleDragEnd);
                });
                list.addEventListener('dragover', handleDragOver);

                // Ensure we always submit current order
                rebuildHidden();
                form.addEventListener('submit', rebuildHidden);
            })();
        </script>
    @endif
@endsection

