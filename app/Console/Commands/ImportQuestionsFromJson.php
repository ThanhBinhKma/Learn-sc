<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportQuestionsFromJson extends Command
{
    protected $signature = 'quiz:import-json
                            {path=storage/app/sc200_questions.json}
                            {--fresh : Delete existing choice/multi_choice questions before import}
                            {--force : Import even if prompt already exists (will duplicate)}
                            {--explain : Print skip reasons summary}';

    protected $description = 'Import choice/multi_choice questions from parsed JSON file';

    public function handle()
    {
        $pathArg = $this->argument('path');
        $path = str_starts_with($pathArg, '/')
            ? $pathArg
            : base_path($pathArg);

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $rows = json_decode(file_get_contents($path), true);
        if (!is_array($rows)) {
            $this->error('Invalid JSON format.');
            return self::FAILURE;
        }

        $inserted = 0;
        $skipped = 0;
        $skipReasons = [
            'invalid_row' => 0,
            'duplicate' => 0,
        ];

        $fresh = (bool) $this->option('fresh');
        $force = (bool) $this->option('force');
        $explain = (bool) $this->option('explain');

        DB::transaction(function () use ($rows, &$inserted, &$skipped, &$skipReasons, $fresh, $force) {
            if ($fresh) {
                $ids = Question::query()->whereIn('type', ['choice', 'multi_choice'])->pluck('id')->all();
                if (!empty($ids)) {
                    QuestionOption::query()->whereIn('question_id', $ids)->delete();
                    Question::query()->whereIn('id', $ids)->delete();
                }
            }

            foreach ($rows as $row) {
                $type = $row['type'] ?? null;
                $prompt = trim((string) ($row['prompt'] ?? ''));
                $options = $row['options'] ?? [];
                $correctIndexes = $row['correct_indexes'] ?? [];

                if (!in_array($type, ['choice', 'multi_choice'], true) || $prompt === '' || count($options) < 2 || count($correctIndexes) < 1) {
                    $skipped++;
                    $skipReasons['invalid_row']++;
                    continue;
                }

                if (!$force) {
                    $exists = Question::query()
                        ->where('type', $type)
                        ->where('prompt', $prompt)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        $skipReasons['duplicate']++;
                        continue;
                    }
                }

                // sanity: correct indexes must be within option bounds
                $maxIdx = count($options) - 1;
                foreach ($correctIndexes as $ci) {
                    if (!is_int($ci) && !ctype_digit((string) $ci)) {
                        $skipped++;
                        $skipReasons['invalid_row']++;
                        continue 2;
                    }
                    if ((int) $ci < 0 || (int) $ci > $maxIdx) {
                        $skipped++;
                        $skipReasons['invalid_row']++;
                        continue 2;
                    }
                }

                if ($type === 'choice' && count($correctIndexes) !== 1) {
                    $skipped++;
                    $skipReasons['invalid_row']++;
                    continue;
                }

                $question = Question::create([
                    'type' => $type,
                    'prompt' => $prompt,
                ]);

                foreach ($options as $idx => $text) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'text' => (string) $text,
                        'display_order' => (int) $idx,
                        'is_correct' => in_array((int) $idx, array_map('intval', $correctIndexes), true),
                        'correct_position' => null,
                        'select_group' => null,
                    ]);
                }

                $inserted++;
            }
        });

        $this->info("Imported: {$inserted}");
        $this->info("Skipped: {$skipped}");
        if ($explain) {
            $this->line('Skip reasons:');
            foreach ($skipReasons as $k => $v) {
                $this->line("- {$k}: {$v}");
            }
        }

        return self::SUCCESS;
    }
}

