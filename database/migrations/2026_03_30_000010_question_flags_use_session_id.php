<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuestionFlagsUseSessionId extends Migration
{
    public function up()
    {
        DB::table('question_flags')->delete();

        Schema::table('question_flags', function (Blueprint $table) {
            $table->dropUnique(['visitor_id', 'question_id']);
        });

        Schema::table('question_flags', function (Blueprint $table) {
            $table->dropColumn('visitor_id');
        });

        Schema::table('question_flags', function (Blueprint $table) {
            $table->string('session_id')->index();
            $table->unique(['session_id', 'question_id']);
        });
    }

    public function down()
    {
        DB::table('question_flags')->delete();

        Schema::table('question_flags', function (Blueprint $table) {
            $table->dropUnique(['session_id', 'question_id']);
        });

        Schema::table('question_flags', function (Blueprint $table) {
            $table->dropColumn('session_id');
        });

        Schema::table('question_flags', function (Blueprint $table) {
            $table->string('visitor_id', 36);
            $table->unique(['visitor_id', 'question_id']);
        });
    }
}
