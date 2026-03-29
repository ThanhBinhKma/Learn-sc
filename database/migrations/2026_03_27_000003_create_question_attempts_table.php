<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionAttemptsTable extends Migration
{
    public function up()
    {
        Schema::create('question_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->json('selected'); // option_ids[] for choice/multi/select OR ordered option_ids[] for drag_drop
            $table->boolean('is_correct')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'question_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('question_attempts');
    }
}

