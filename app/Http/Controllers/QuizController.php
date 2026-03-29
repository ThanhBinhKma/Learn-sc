<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Models\QuestionFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $sessionId = $request->session()->getId();
        $visitorId = (string) $request->attributes->get('quiz_visitor_id');
        $status = $request->query('status', 'all');
        $categoryId = $request->query('category_id');
        $categoryId = ($categoryId === null || $categoryId === '' || $categoryId === 'all')
            ? null
            : (int) $categoryId;
        if ($categoryId !== null && $categoryId < 1) {
            $categoryId = null;
        }
        if ($categoryId !== null && !Category::query()->whereKey($categoryId)->exists()) {
            $categoryId = null;
        }

        $allowedPerPage = [10, 20, 30, 50];
        $perPage = (int) $request->query('per_page', 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $questionsQuery = Question::query()
            ->withCount('options')
            ->with('category')
            ->with(['attempts' => function ($q) use ($sessionId) {
                $q->where('session_id', $sessionId);
            }])
            ->with(['flags' => function ($q) use ($visitorId) {
                $q->where('visitor_id', $visitorId);
            }])
            ->orderByDesc('id')
        ;

        if ($status === 'correct') {
            $questionsQuery->whereHas('attempts', function ($q) use ($sessionId) {
                $q->where('session_id', $sessionId)->where('is_correct', true);
            });
        } elseif ($status === 'wrong') {
            $questionsQuery->whereHas('attempts', function ($q) use ($sessionId) {
                $q->where('session_id', $sessionId)->where('is_correct', false);
            });
        } elseif ($status === 'unanswered') {
            $questionsQuery->whereDoesntHave('attempts', function ($q) use ($sessionId) {
                $q->where('session_id', $sessionId);
            });
        } elseif ($status === 'flagged') {
            $questionsQuery->whereHas('flags', function ($q) use ($visitorId) {
                $q->where('visitor_id', $visitorId);
            });
        } elseif ($status === 'unflagged') {
            $questionsQuery->whereDoesntHave('flags', function ($q) use ($visitorId) {
                $q->where('visitor_id', $visitorId);
            });
        } else {
            $status = 'all';
        }

        if ($categoryId !== null) {
            $questionsQuery->where('category_id', $categoryId);
        }

        $questions = $questionsQuery
            ->paginate($perPage)
            ->appends(array_filter([
                'status' => $status,
                'per_page' => $perPage,
                'category_id' => $categoryId,
            ]));

        $categories = Category::query()->orderBy('name')->get();

        return view('quiz.index', compact('questions', 'status', 'perPage', 'categories', 'categoryId'));
    }

    public function show(Question $question, Request $request)
    {
        $question->load(['category', 'options' => function ($q) use ($question) {
            if ($question->type === 'drag_drop') {
                $q->orderBy('display_order');
            } else {
                $q->orderBy('display_order');
            }
        }]);

        $attempt = QuestionAttempt::query()
            ->where('session_id', $request->session()->getId())
            ->where('question_id', $question->id)
            ->first();

        $isFlagged = QuestionFlag::query()
            ->where('visitor_id', (string) $request->attributes->get('quiz_visitor_id'))
            ->where('question_id', $question->id)
            ->exists();

        return view('quiz.show', compact('question', 'attempt', 'isFlagged'));
    }

    public function check(Question $question, Request $request)
    {
        $question->load('options');

        $sessionId = $request->session()->getId();

        $selected = $this->normalizeSelected($question->type, $request);
        $isCorrect = $this->isCorrect($question, $selected);

        DB::transaction(function () use ($sessionId, $question, $selected, $isCorrect) {
            QuestionAttempt::updateOrCreate(
                ['session_id' => $sessionId, 'question_id' => $question->id],
                [
                    'selected' => $selected,
                    'is_correct' => $isCorrect,
                    'checked_at' => now(),
                ]
            );
        });

        return redirect()
            ->route('quiz.show', $question)
            ->with('checked', true);
    }

    public function reset(Question $question, Request $request)
    {
        QuestionAttempt::query()
            ->where('session_id', $request->session()->getId())
            ->where('question_id', $question->id)
            ->delete();

        return redirect()
            ->route('quiz.show', $question)
            ->with('status', 'Da reset lich su tra loi cho cau hoi nay.');
    }

    public function toggleFlag(Question $question, Request $request)
    {
        $visitorId = (string) $request->attributes->get('quiz_visitor_id');
        $existing = QuestionFlag::query()
            ->where('visitor_id', $visitorId)
            ->where('question_id', $question->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $message = 'Da bo danh dau flag.';
        } else {
            QuestionFlag::create([
                'visitor_id' => $visitorId,
                'question_id' => $question->id,
            ]);
            $message = 'Da danh dau flag cho cau hoi.';
        }

        return redirect()
            ->route('quiz.show', $question)
            ->with('status', $message);
    }

    private function normalizeSelected(string $type, Request $request): array
    {
        if ($type === 'choice') {
            $id = $request->input('selected_one');
            return $id ? [(int) $id] : [];
        }

        if ($type === 'select') {
            $id1 = $request->input('selected_select_1');
            $id2 = $request->input('selected_select_2');
            $result = [];
            if ($id1) {
                $result[] = (int) $id1;
            }
            if ($id2) {
                $result[] = (int) $id2;
            }
            return $result;
        }

        if ($type === 'multi_choice') {
            $ids = $request->input('selected_many', []);
            $ids = array_values(array_unique(array_map('intval', is_array($ids) ? $ids : [])));
            sort($ids);
            return $ids;
        }

        // drag_drop: ordered list of option ids (as ints)
        $ordered = $request->input('ordered', []);
        $ordered = array_values(array_map('intval', is_array($ordered) ? $ordered : []));
        return $ordered;
    }

    private function isCorrect(Question $question, array $selected): bool
    {
        $type = $question->type;

        if ($type === 'choice') {
            $correct = $question->options->firstWhere('is_correct', true);
            return $correct && count($selected) === 1 && (int) $selected[0] === (int) $correct->id;
        }

        if ($type === 'select') {
            $group1Correct = $question->options
                ->where('select_group', 1)
                ->firstWhere('is_correct', true);
            $group2Correct = $question->options
                ->where('select_group', 2)
                ->firstWhere('is_correct', true);

            if (!$group1Correct || !$group2Correct || count($selected) !== 2) {
                return false;
            }

            $sel = array_values(array_map('intval', $selected));
            sort($sel);
            $correct = [(int) $group1Correct->id, (int) $group2Correct->id];
            sort($correct);
            return $sel === $correct;
        }

        if ($type === 'multi_choice') {
            $correctIds = $question->options->where('is_correct', true)->pluck('id')->map(fn ($v) => (int) $v)->all();
            sort($correctIds);
            $sel = $selected;
            sort($sel);
            return $correctIds === $sel;
        }

        if ($type === 'drag_drop') {
            $correctOrder = $question->options
                ->filter(fn ($o) => $o->correct_position !== null)
                ->sortBy('correct_position')
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->values()
                ->all();

            return $correctOrder && $correctOrder === array_values($selected);
        }

        return false;
    }
}

