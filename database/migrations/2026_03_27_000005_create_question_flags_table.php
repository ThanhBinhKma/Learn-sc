<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionFlagsTable extends Migration
{
    public function up()
    {
        Schema::create('question_flags', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['session_id', 'question_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('question_flags');
    }
}

