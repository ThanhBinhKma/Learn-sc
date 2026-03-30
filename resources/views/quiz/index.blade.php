@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="row" style="align-items:center; justify-content:space-between">
            <div>
                <div class="h1">Danh sách câu hỏi</div>
                <div class="muted">Chọn 1 câu để làm và bấm Check để lưu đáp án.</div>
            </div>
            <a class="btn btn-primary" href="{{ route('questions.create') }}">+ Tạo câu hỏi</a>
        </div>

        <hr class="hr" />

        @php
            $quizBase = array_filter([
                'per_page' => $perPage,
                'category_id' => $categoryId,
                'q' => $search !== '' ? $search : null,
            ]);
        @endphp
        <div class="row" style="align-items:center; gap:8px">
            <a class="pill" href="{{ route('quiz.index', array_merge($quizBase, ['status' => 'all'])) }}" style="{{ $status === 'all' ? 'border-color: rgba(79,140,255,.8);' : '' }}">Tất cả</a>
            <a class="pill" href="{{ route('quiz.index', array_merge($quizBase, ['status' => 'correct'])) }}" style="{{ $status === 'correct' ? 'border-color: rgba(43,213,118,.9);' : '' }}">Đúng</a>
            <a class="pill" href="{{ route('quiz.index', array_merge($quizBase, ['status' => 'wrong'])) }}" style="{{ $status === 'wrong' ? 'border-color: rgba(255,90,107,.9);' : '' }}">Sai</a>
            <a class="pill" href="{{ route('quiz.index', array_merge($quizBase, ['status' => 'unanswered'])) }}" style="{{ $status === 'unanswered' ? 'border-color: rgba(255,255,255,.5);' : '' }}">Chưa làm</a>
            <a class="pill" href="{{ route('quiz.index', array_merge($quizBase, ['status' => 'flagged'])) }}" style="{{ $status === 'flagged' ? 'border-color: rgba(255,221,87,.95);' : '' }}">Flagged</a>
            <a class="pill" href="{{ route('quiz.index', array_merge($quizBase, ['status' => 'unflagged'])) }}" style="{{ $status === 'unflagged' ? 'border-color: rgba(170,178,197,.9);' : '' }}">Unflag</a>
            <form method="GET" action="{{ route('quiz.index') }}" style="margin-left:auto;">
                <input type="hidden" name="status" value="{{ $status }}">
                @if ($search !== '')
                    <input type="hidden" name="q" value="{{ $search }}">
                @endif
                @if ($categoryId !== null)
                    <input type="hidden" name="category_id" value="{{ $categoryId }}">
                @endif
                <select class="select" name="per_page" onchange="this.form.submit()" style="width:auto; min-width:120px;">
                    <option value="10" {{ $perPage === 10 ? 'selected' : '' }}>10 / trang</option>
                    <option value="20" {{ $perPage === 20 ? 'selected' : '' }}>20 / trang</option>
                    <option value="30" {{ $perPage === 30 ? 'selected' : '' }}>30 / trang</option>
                    <option value="50" {{ $perPage === 50 ? 'selected' : '' }}>50 / trang</option>
                </select>
            </form>
        </div>

        <div style="height:8px"></div>
        <form method="GET" action="{{ route('quiz.index') }}" class="row" style="align-items:center; gap:10px; flex-wrap:wrap">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <span class="k">Tìm câu hỏi</span>
            <input class="input" type="search" name="q" value="{{ $search }}" placeholder="Nội dung câu, từ khóa, đáp án..." autocomplete="off" style="flex:1; min-width:200px; max-width:420px;">
            <button class="btn btn-primary" type="submit">Tìm</button>
            @if ($search !== '')
                <a class="pill" href="{{ route('quiz.index', array_filter(['status' => $status, 'per_page' => $perPage, 'category_id' => $categoryId])) }}">Xóa tìm</a>
            @endif
            <span class="k" style="margin-left:8px;">Category</span>
            <select class="select" name="category_id" onchange="this.form.submit()" style="width:auto; min-width:220px;">
                <option value="all" {{ $categoryId === null ? 'selected' : '' }}>Tất cả category</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $categoryId === $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </form>

        <div style="height:8px"></div>

        <div class="grid" style="gap:10px">
            @forelse ($questions as $q)
                @php
                    $attempt = $q->attempts->first();
                    $flag = $q->flags->first();
                    $questionNumber = ($questions->currentPage() - 1) * $questions->perPage() + $loop->iteration;
                @endphp
                <div class="option" style="align-items:stretch">
                    <a href="{{ route('quiz.show', $q) }}" style="flex:1; display:block; min-width:0">
                        <div style="font-weight:700">Question {{ $questionNumber }}</div>
                        <div class="prompt-multiline">{{ \Illuminate\Support\Str::limit(\App\Models\Question::plainPromptPreview($q->prompt), 120) }}</div>
                        <div class="muted">
                            <span class="badge">{{ $q->type }}</span>
                            @if ($q->category)
                                <span class="badge" style="border-color: rgba(79,140,255,.55);">{{ $q->category->name }}</span>
                            @endif
                            <span class="badge">{{ $q->options_count }} options</span>
                            <span class="badge">#{{ $q->id }}</span>
                            @if ($flag)
                                <span class="badge" style="border-color: rgba(255,221,87,.95); color: #ffe38a;">Flag</span>
                            @endif
                            @if ($attempt)
                                <span class="badge" style="{{ $attempt->is_correct ? 'border-color: rgba(43,213,118,.9); color: #aef5c7;' : 'border-color: rgba(255,90,107,.9); color: #ffc1c9;' }}">
                                    {{ $attempt->is_correct ? 'Đúng' : 'Sai' }}
                                </span>
                            @else
                                <span class="badge">Chưa làm</span>
                            @endif
                        </div>
                    </a>
                    <div class="row" style="flex-direction:column; justify-content:center; gap:6px; flex-shrink:0">
                        <a class="pill" href="{{ route('quiz.show', $q) }}" style="text-align:center">Mở</a>
                        <a class="pill" href="{{ route('questions.edit', $q) }}" style="text-align:center; border-color: rgba(79,140,255,.6);">Sửa</a>
                    </div>
                </div>
            @empty
                <div class="muted">
                    @if ($search !== '')
                        Không có câu hỏi khớp “{{ Str::limit($search, 80) }}”. Thử từ khóa khác hoặc <a href="{{ route('quiz.index', array_filter(['status' => $status, 'per_page' => $perPage, 'category_id' => $categoryId])) }}">xóa tìm kiếm</a>.
                    @else
                        Không có câu hỏi phù hợp với bộ lọc hiện tại.
                    @endif
                </div>
            @endforelse
        </div>
        <div style="height:10px"></div>
        <div class="muted">Trang {{ $questions->currentPage() }} / {{ $questions->lastPage() }} - Tổng {{ $questions->total() }} câu</div>
        <div style="height:10px"></div>
        @if ($questions->lastPage() > 1)
            <div class="row" style="align-items:center; gap:6px">
                @if ($questions->onFirstPage())
                    <span class="pill" style="opacity:.5">Trước</span>
                @else
                    <a class="pill" href="{{ $questions->previousPageUrl() }}">Trước</a>
                @endif

                @foreach ($questions->getUrlRange(1, $questions->lastPage()) as $page => $url)
                    @if ($page == $questions->currentPage())
                        <span class="pill" style="border-color: rgba(79,140,255,.8);">{{ $page }}</span>
                    @else
                        <a class="pill" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if ($questions->hasMorePages())
                    <a class="pill" href="{{ $questions->nextPageUrl() }}">Sau</a>
                @else
                    <span class="pill" style="opacity:.5">Sau</span>
                @endif
            </div>
        @endif
    </div>
@endsection

