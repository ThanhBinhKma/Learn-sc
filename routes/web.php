<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\QuestionBuilderController;
use App\Http\Controllers\QuizController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('quiz.index');
});

Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');

Route::get('/questions/create', [QuestionBuilderController::class, 'create'])->name('questions.create');
Route::post('/questions', [QuestionBuilderController::class, 'store'])->name('questions.store');
Route::get('/questions/{question}/edit', [QuestionBuilderController::class, 'edit'])->name('questions.edit');
Route::put('/questions/{question}', [QuestionBuilderController::class, 'update'])->name('questions.update');

Route::get('/quiz', [QuizController::class, 'index'])->name('quiz.index');
Route::get('/quiz/{question}', [QuizController::class, 'show'])->name('quiz.show');
Route::post('/quiz/{question}/check', [QuizController::class, 'check'])->name('quiz.check');
Route::post('/quiz/{question}/reset', [QuizController::class, 'reset'])->name('quiz.reset');
Route::post('/quiz/{question}/flag', [QuizController::class, 'toggleFlag'])->name('quiz.flag');
