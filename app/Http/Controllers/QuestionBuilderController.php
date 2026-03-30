<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class QuestionBuilderController extends Controller
{
    public function create()
    {
        $categories = Category::query()->orderBy('name')->get();

        return view('question-builder.create', compact('categories'));
    }

    public function edit(Question $question)
    {
        $question->load(['options' => function ($q) {
            $q->orderBy('display_order');
        }]);

        $options = $question->options->values();
        $correctOne = null;
        $correctMany = [];
        $correctOneGroup1 = null;
        $correctOneGroup2 = null;

        foreach ($options as $idx => $o) {
            if ($question->type === 'choice' && $o->is_correct) {
                $correctOne = $idx;
            }
            if ($question->type === 'multi_choice' && $o->is_correct) {
                $correctMany[] = $idx;
            }
            if ($question->type === 'select' && $o->is_correct) {
                if ((int) ($o->select_group ?? 1) === 1) {
                    $correctOneGroup1 = $idx;
                } else {
                    $correctOneGroup2 = $idx;
                }
            }
        }

        $editPayload = [
            'type' => $question->type,
            'prompt' => $question->prompt,
            'keyword' => $question->keyword ?? '',
            'correctOne' => $correctOne,
            'correctMany' => $correctMany,
            'correctOneGroup1' => $correctOneGroup1,
            'correctOneGroup2' => $correctOneGroup2,
            'options' => $options->map(function ($o) {
                return [
                    'text' => $o->text,
                    'select_group' => (int) ($o->select_group ?? 1),
                    'correct_position' => $o->correct_position,
                ];
            })->all(),
        ];

        $categories = Category::query()->orderBy('name')->get();

        return view('question-builder.edit', compact('question', 'editPayload', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validateQuestionRequest($request);

        return DB::transaction(function () use ($data) {
            $question = Question::create([
                'type' => $data['type'],
                'prompt' => $data['prompt'],
                'keyword' => $data['keyword'] ?? null,
                'category_id' => $data['category_id'] ?? null,
            ]);

            $this->syncOptionsFromPayload($question, $data);

            return redirect()->route('quiz.index')->with('status', 'Tạo câu hỏi thành công.');
        });
    }

    public function update(Request $request, Question $question)
    {
        $data = $this->validateQuestionRequest($request);

        return DB::transaction(function () use ($data, $question) {
            $question->update([
                'type' => $data['type'],
                'prompt' => $data['prompt'],
                'keyword' => $data['keyword'] ?? null,
                'category_id' => $data['category_id'] ?? null,
            ]);

            $question->options()->delete();
            $this->syncOptionsFromPayload($question, $data);

            return redirect()->route('quiz.show', $question)->with('status', 'Đã cập nhật câu hỏi.');
        });
    }

    public function destroy(Question $question)
    {
        $question->delete(); // cascadeOnDelete should remove related options/attempts/flags

        return redirect()
            ->route('quiz.index')
            ->with('status', 'Đã xóa câu hỏi.');
    }

    private function validateQuestionRequest(Request $request): array
    {
        if ($request->input('category_id') === '' || $request->input('category_id') === null) {
            $request->merge(['category_id' => null]);
        }

        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(['choice', 'multi_choice', 'drag_drop', 'select'])],
            'prompt' => [
                'required',
                'string',
                'max:65000',
                function ($attribute, $value, $fail) {
                    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
                    if (mb_strlen($plain) < 3) {
                        $fail('Nội dung câu hỏi quá ngắn (ít nhất 3 ký tự có nghĩa).');
                    }
                },
            ],
            'keyword' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'options' => ['required', 'array', 'min:2'],
            'options.*.text' => ['required', 'string', 'min:1'],

            'correct_one' => ['nullable', 'integer'],

            'correct_many' => ['nullable', 'array'],
            'correct_many.*' => ['integer'],

            'options.*.correct_position' => ['nullable', 'integer', 'min:1'],
            'options.*.select_group' => ['nullable', 'integer', Rule::in([1, 2])],
            'correct_one_group_1' => ['nullable', 'integer'],
            'correct_one_group_2' => ['nullable', 'integer'],
        ]);

        $type = $data['type'];
        if ($type === 'choice') {
            if (!isset($data['correct_one'])) {
                throw ValidationException::withMessages([
                    'correct_one' => 'Vui lòng chọn đúng 1 đáp án.',
                ]);
            }
        } elseif ($type === 'select') {
            $group1 = [];
            $group2 = [];
            foreach ($data['options'] as $idx => $opt) {
                $group = (int) ($opt['select_group'] ?? 1);
                if ($group === 1) {
                    $group1[] = $idx;
                } else {
                    $group2[] = $idx;
                }
            }

            if (count($group1) < 2 || count($group2) < 2) {
                throw ValidationException::withMessages([
                    'options' => 'Loai select can 2 nhom, moi nhom it nhat 2 option.',
                ]);
            }

            if (!isset($data['correct_one_group_1']) || !in_array((int) $data['correct_one_group_1'], $group1, true)) {
                throw ValidationException::withMessages([
                    'correct_one_group_1' => 'Vui long chon 1 dap an dung cho select thu nhat.',
                ]);
            }
            if (!isset($data['correct_one_group_2']) || !in_array((int) $data['correct_one_group_2'], $group2, true)) {
                throw ValidationException::withMessages([
                    'correct_one_group_2' => 'Vui long chon 1 dap an dung cho select thu hai.',
                ]);
            }
        } elseif ($type === 'multi_choice') {
            if (empty($data['correct_many']) || !is_array($data['correct_many'])) {
                throw ValidationException::withMessages([
                    'correct_many' => 'Vui lòng chọn ít nhất 1 đáp án đúng.',
                ]);
            }
        } elseif ($type === 'drag_drop') {
            $positions = [];
            foreach ($data['options'] as $i => $opt) {
                if (!isset($opt['correct_position'])) {
                    throw ValidationException::withMessages([
                        "options.$i.correct_position" => 'Vui lòng nhập vị trí đúng cho item này.',
                    ]);
                }
                $positions[] = (int) $opt['correct_position'];
            }
            $unique = array_values(array_unique($positions));
            if (count($unique) !== count($positions)) {
                throw ValidationException::withMessages([
                    'options' => 'Vị trí đúng không được trùng nhau (1..n).',
                ]);
            }
        }

        $kw = isset($data['keyword']) ? trim((string) $data['keyword']) : '';
        $data['keyword'] = $kw !== '' ? $kw : null;

        return $data;
    }

    private function syncOptionsFromPayload(Question $question, array $data): void
    {
        $type = $question->type;
        $correctOne = $data['correct_one'] ?? null;
        $correctMany = array_map('intval', $data['correct_many'] ?? []);
        $correctOneGroup1 = isset($data['correct_one_group_1']) ? (int) $data['correct_one_group_1'] : null;
        $correctOneGroup2 = isset($data['correct_one_group_2']) ? (int) $data['correct_one_group_2'] : null;

        foreach ($data['options'] as $idx => $opt) {
            $isCorrect = false;
            $correctPosition = null;
            $selectGroup = null;

            if ($type === 'choice') {
                $isCorrect = ($correctOne !== null) && ((int) $correctOne === (int) $idx);
            } elseif ($type === 'select') {
                $selectGroup = (int) ($opt['select_group'] ?? 1);
                if ($selectGroup === 1) {
                    $isCorrect = $correctOneGroup1 !== null && $correctOneGroup1 === (int) $idx;
                } else {
                    $isCorrect = $correctOneGroup2 !== null && $correctOneGroup2 === (int) $idx;
                }
            } elseif ($type === 'multi_choice') {
                $isCorrect = in_array((int) $idx, $correctMany, true);
            } elseif ($type === 'drag_drop') {
                $correctPosition = isset($opt['correct_position']) ? (int) $opt['correct_position'] : null;
            }

            QuestionOption::create([
                'question_id' => $question->id,
                'text' => $opt['text'],
                'display_order' => $idx,
                'is_correct' => $isCorrect,
                'correct_position' => $correctPosition,
                'select_group' => $selectGroup,
            ]);
        }
    }
}
