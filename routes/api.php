<?php

use App\Http\Controllers\Api\AnswerForumController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseTakenController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\KumpulanCertificateController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\TutorController;
use App\Http\Controllers\Api\TutorCourseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\Api\UserController;



Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{user_id}', [UserController::class, 'update']);

use App\Http\Controllers\Api\FacultyController;
Route::get('/faculties', [FacultyController::class, 'index']);
Route::post('/faculties', [FacultyController::class, 'store']);
Route::put('/faculties/{faculty_id}', [FacultyController::class, 'update']);


use App\Http\Controllers\Api\ProdiController;

Route::post('/prodis', [ProdiController::class, 'store']);
Route::get('/prodis', [ProdiController::class, 'index']);

Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);

Route::post('/course', [CourseController::class, 'store']);
Route::get('/course', [CourseController::class, 'index']);

Route::get('/tutor', [TutorController::class, 'index']);
Route::post('/tutor', [TutorController::class, 'store']);

Route::get('/tutorCourse', [TutorCourseController::class, 'index']);
Route::post('/tutorCourse', [TutorCourseController::class, 'store']);

Route::get('/certificate', [CertificateController::class, 'index']);
Route::post('/certificate', [CertificateController::class, 'store']);

Route::get('/kumpulanCertificate', [KumpulanCertificateController::class, 'index']);
Route::post('/kumpulanCertificate', [KumpulanCertificateController::class, 'store']);

Route::get('/review', [ReviewController::class, 'index']);
Route::post('/review', [ReviewController::class, 'store']);

Route::get('/forum', [ForumController::class, 'index']);
Route::post('/forum', [ForumController::class, 'store']);

Route::get('/answerForum', [AnswerForumController::class, 'index']);
Route::post('/answerForum', [AnswerForumController::class, 'store']);

Route::get('/courseTaken', [CourseTakenController::class, 'index']);
Route::post('/courseTaken', [CourseTakenController::class, 'store']);

Route::get('/schedule', [ScheduleController::class, 'index']);
Route::post('/schedule', [ScheduleController::class, 'store']);

Route::get('/chat', [ChatController::class, 'index']);
Route::post('/chat', [ChatController::class, 'store']);