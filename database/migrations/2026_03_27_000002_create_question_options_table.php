<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionOptionsTable extends Migration
{
    public function up()
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('text');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_correct')->default(false); // choice | multi_choice | select
            $table->unsignedInteger('correct_position')->nullable(); // drag_drop (1..n)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('question_options');
    }
}

