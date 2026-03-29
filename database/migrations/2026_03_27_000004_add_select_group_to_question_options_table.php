<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSelectGroupToQuestionOptionsTable extends Migration
{
    public function up()
    {
        Schema::table('question_options', function (Blueprint $table) {
            $table->unsignedTinyInteger('select_group')->nullable()->after('correct_position');
        });
    }

    public function down()
    {
        Schema::table('question_options', function (Blueprint $table) {
            $table->dropColumn('select_group');
        });
    }
}

