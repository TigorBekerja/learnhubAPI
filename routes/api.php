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
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);

use App\Http\Controllers\Api\FacultyController;
Route::get('/faculties', [FacultyController::class, 'index']);
Route::post('/faculties', [FacultyController::class, 'store']);
Route::put('/faculties/{faculty_id}', [FacultyController::class, 'update']);


use App\Http\Controllers\Api\ProdiController;

Route::post('/prodis', [ProdiController::class, 'store']);
Route::get('/prodis', [ProdiController::class, 'index']);
Route::put('/prodis/{prodi_id}', [ProdiController::class, 'update']);

Route::post('/course', [CourseController::class, 'store']);
Route::get('/course', [CourseController::class, 'index']);
Route::put('/course/{course_id}', [CourseController::class, 'update']);

Route::get('/tutor', [TutorController::class, 'index']);
Route::post('/tutor', [TutorController::class, 'store']);
Route::put('/tutor/{tutor_id}', [TutorController::class, 'update']);


Route::get('/tutorCourse', [TutorCourseController::class, 'index']);
Route::post('/tutorCourse', [TutorCourseController::class, 'store']);
Route::put('/tutorCourse/{id}', [TutorCourseController::class, 'update']);

Route::get('/certificate', [CertificateController::class, 'index']);
Route::post('/certificate', [CertificateController::class, 'store']);
Route::put('/certificate/{certificate_id}', [CertificateController::class, 'update']);

Route::get('/kumpulanCertificate', [KumpulanCertificateController::class, 'index']);
Route::post('/kumpulanCertificate', [KumpulanCertificateController::class, 'store']);
Route::put('/kumpulanCertificate/{id}', [KumpulanCertificateController::class, 'update']);
Route::delete('/kumpulanCertificate/{id}', [KumpulanCertificateController::class, 'destroy']);

Route::get('/review', [ReviewController::class, 'index']);
Route::post('/review', [ReviewController::class, 'store']);
Route::put('/review/{review_id}', [ReviewController::class, 'update']);

Route::get('/forum', [ForumController::class, 'index']);
Route::post('/forum', [ForumController::class, 'store']);
Route::put('/forum/{forum_id}', [ForumController::class, 'update']);

Route::get('/answerForum', [AnswerForumController::class, 'index']);
Route::post('/answerForum', [AnswerForumController::class, 'store']);
Route::put('/answerForum/{answer_id}', [AnswerForumController::class, 'update']);

Route::get('/courseTaken', [CourseTakenController::class, 'index']);
Route::post('/courseTaken', [CourseTakenController::class, 'store']);
Route::put('/courseTaken/{id}', [CourseTakenController::class, 'update']);


Route::get('/schedule', [ScheduleController::class, 'index']);
Route::post('/schedule', [ScheduleController::class, 'store']);
Route::put('/schedule/{schedule_id}', [ScheduleController::class, 'update']);

Route::get('/chat', [ChatController::class, 'index']);
Route::post('/chat', [ChatController::class, 'store']);
Route::put('/chat/{chat_id}', [ChatController::class, 'update']);