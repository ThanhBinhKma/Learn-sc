@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="h1">Tạo câu hỏi</div>
        <div class="muted">Hỗ trợ: <span class="badge">choice</span> <span class="badge">multi_choice</span> <span class="badge">drag_drop</span> <span class="badge">select</span></div>
        <hr class="hr" />

        <form method="POST" action="{{ route('questions.store') }}" id="createForm" class="grid" style="gap:12px">
            @csrf

            <div class="grid" style="gap:8px">
                <div class="k">Dạng câu hỏi</div>
                <select class="select" name="type" id="type">
                    <option value="choice">choice (1 đáp án)</option>
                    <option value="multi_choice">multi_choice (nhiều đáp án)</option>
                    <option value="drag_drop">kéo thả (đúng thứ tự)</option>
                    <option value="select">select (dropdown)</option>
                </select>
                @error('type')<div class="muted">{{ $message }}</div>@enderror
            </div>

            <div class="grid" style="gap:8px">
                <div class="k">Câu hỏi (CKEditor — xuống dòng, định dạng)</div>
                <textarea class="input" name="prompt" id="prompt" rows="10" placeholder="Soạn nội dung câu hỏi...">{!! old('prompt') !!}</textarea>
                @error('prompt')<div class="muted">{{ $message }}</div>@enderror
            </div>

            <div class="grid" style="gap:8px">
                <div class="k">Keyword (chỉ hiện sau khi bấm Check)</div>
                <textarea class="input" name="keyword" rows="2" placeholder="Gợi ý / từ khóa sau khi làm xong...">{{ old('keyword') }}</textarea>
                @error('keyword')<div class="muted">{{ $message }}</div>@enderror
            </div>

            <div class="grid" style="gap:8px">
                <div class="k">Category (tùy chọn)</div>
                <select class="select" name="category_id">
                    <option value="">— Không chọn —</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string) old('category_id') === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<div class="muted">{{ $message }}</div>@enderror
            </div>

            <div class="grid" style="gap:10px">
                <div class="row" style="align-items:center; justify-content:space-between">
                    <div>
                        <div class="k">Danh sách đáp án</div>
                        <div class="muted" id="hint">Nhập ít nhất 2 đáp án.</div>
                    </div>
                    <button type="button" class="btn btn-ghost" id="addOption">+ Thêm đáp án</button>
                </div>

                <div id="options" class="grid" style="gap:10px"></div>
                @error('options')<div class="muted">{{ $message }}</div>@enderror
                @error('correct_one')<div class="muted">{{ $message }}</div>@enderror
                @error('correct_many')<div class="muted">{{ $message }}</div>@enderror
                @error('correct_one_group_1')<div class="muted">{{ $message }}</div>@enderror
                @error('correct_one_group_2')<div class="muted">{{ $message }}</div>@enderror
            </div>

            <div class="row" style="justify-content:flex-end">
                <button class="btn btn-primary" type="submit">Lưu câu hỏi</button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const typeEl = document.getElementById('type');
            const optionsEl = document.getElementById('options');
            const addBtn = document.getElementById('addOption');
            const hintEl = document.getElementById('hint');

            let optionCount = 0;

            function currentType() { return typeEl.value; }

            function renderHint() {
                const t = currentType();
                if (t === 'choice') {
                    hintEl.textContent = 'Chọn đúng 1 đáp án.';
                } else if (t === 'select') {
                    hintEl.textContent = 'Select co 2 nhom (1 va 2), moi nhom chon 1 dap an dung.';
                } else if (t === 'multi_choice') {
                    hintEl.textContent = 'Chọn một hoặc nhiều đáp án đúng.';
                } else {
                    hintEl.textContent = 'Nhập vị trí đúng (1..n) cho mỗi item.';
                }
            }

            function optionRow(idx) {
                const wrapper = document.createElement('div');
                wrapper.className = 'option';
                wrapper.dataset.idx = String(idx);

                const left = document.createElement('div');
                left.style.flex = '1';

                const input = document.createElement('input');
                input.className = 'input';
                input.name = `options[${idx}][text]`;
                input.placeholder = `Đáp án #${idx + 1}`;

                left.appendChild(input);

                const right = document.createElement('div');
                right.style.display = 'flex';
                right.style.gap = '8px';
                right.style.alignItems = 'center';

                const t = currentType();
                if (t === 'choice') {
                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = 'correct_one';
                    radio.value = String(idx);
                    right.appendChild(radio);
                } else if (t === 'select') {
                    const group = document.createElement('select');
                    group.className = 'select';
                    group.style.width = '92px';
                    group.name = `options[${idx}][select_group]`;
                    group.innerHTML = '<option value="1">Select 1</option><option value="2">Select 2</option>';

                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.value = String(idx);
                    radio.name = 'correct_one_group_1';

                    const syncRadioName = () => {
                        radio.name = group.value === '2' ? 'correct_one_group_2' : 'correct_one_group_1';
                    };
                    group.addEventListener('change', syncRadioName);
                    syncRadioName();

                    right.appendChild(group);
                    right.appendChild(radio);
                } else if (t === 'multi_choice') {
                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.name = 'correct_many[]';
                    cb.value = String(idx);
                    right.appendChild(cb);
                } else if (t === 'drag_drop') {
                    const pos = document.createElement('input');
                    pos.type = 'number';
                    pos.min = '1';
                    pos.className = 'input';
                    pos.style.width = '110px';
                    pos.name = `options[${idx}][correct_position]`;
                    pos.placeholder = 'Vị trí';
                    right.appendChild(pos);
                }

                const del = document.createElement('button');
                del.type = 'button';
                del.className = 'btn btn-ghost';
                del.textContent = 'Xóa';
                del.addEventListener('click', () => {
                    wrapper.remove();
                });
                right.appendChild(del);

                wrapper.appendChild(left);
                wrapper.appendChild(right);
                return wrapper;
            }

            function addOption() {
                optionsEl.appendChild(optionRow(optionCount));
                optionCount += 1;
            }

            function rerenderOptionsForTypeChange() {
                // Keep texts, rebuild controls
                const existing = Array.from(optionsEl.querySelectorAll('[data-idx]')).map(el => {
                    const idx = parseInt(el.dataset.idx, 10);
                    const textInput = el.querySelector(`input[name="options[${idx}][text]"]`);
                    const groupSelect = el.querySelector(`select[name="options[${idx}][select_group]"]`);
                    return {
                        idx,
                        text: textInput ? textInput.value : '',
                        group: groupSelect ? groupSelect.value : '1'
                    };
                });

                optionsEl.innerHTML = '';
                optionCount = 0;
                existing.forEach(({ text, group }) => {
                    const row = optionRow(optionCount);
                    const ti = row.querySelector(`input[name="options[${optionCount}][text]"]`);
                    if (ti) ti.value = text;
                    const gs = row.querySelector(`select[name="options[${optionCount}][select_group]"]`);
                    if (gs) {
                        gs.value = group === '2' ? '2' : '1';
                        gs.dispatchEvent(new Event('change'));
                    }
                    optionsEl.appendChild(row);
                    optionCount += 1;
                });
                renderHint();
            }

            typeEl.addEventListener('change', rerenderOptionsForTypeChange);
            addBtn.addEventListener('click', addOption);

            renderHint();
            addOption();
            addOption();
        })();
    </script>
@endsection

@push('scripts')
    @include('question-builder.partials.ckeditor-prompt-scripts')
@endpush

