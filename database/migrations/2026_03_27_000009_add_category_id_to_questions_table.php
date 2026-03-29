<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryIdToQuestionsTable extends Migration
{
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('keyword')->constrained('categories')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });
    }
}
