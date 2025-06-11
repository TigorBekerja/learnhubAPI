<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\FirebaseTokenService;

class CourseTakenController extends Controller
{
    protected FirestoreService $courseTakenService;
    protected FirestoreService $userService;
    protected FirestoreService $tutorCourseServive;
    public function __construct()
    {
        $this->courseTakenService = new FirestoreService('course_takens', app(FirebaseTokenService::class));
        $this->userService = new FirestoreService('users', app(FirebaseTokenService::class));
        $this->tutorCourseServive = new FirestoreService('tutorCourse', app(FirebaseTokenService::class));
    }

    public function index()
    {
        $tutor = $this->courseTakenService->getAllDocuments();

        return response()->json($tutor);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|string',
            'course_id' => 'required|string',
            'tutor_id' => 'required|string',
        ]);

        // validasi user id
        $user_list = $this->userService->getAllDocuments();

        $isUserIdValid = collect($user_list)->contains(function ($item) use ($validated) {
            return isset($item['user_id']) && $item['user_id'] === $validated['user_id'];
        });
        if (!$isUserIdValid) {
            return response()->json(['message' => 'user id tidak ditemukan di database'], 422);
        }

        // validasi course id
        $course_list = $this->tutorCourseServive->getAllDocuments();

        $isCourseIdValid = collect($course_list)->contains(function ($item) use ($validated) {
            return isset($item['course_id']) && $item['course_id'] === $validated['course_id'];
        });
        if (!$isCourseIdValid) {
            return response()->json(['message' => 'course id tidak ditemukan di database'], 422);
        }

        //validasi tutor id
        $tutor_list = $this->tutorCourseServive->getAllDocuments();

        $isTutorIdValid = collect($tutor_list)->contains(function ($item) use ($validated) {
            return isset($item['tutor_id']) && $item['tutor_id'] === $validated['tutor_id'];
        });
        if (!$isTutorIdValid) {
            return response()->json(['message' => 'tutor_id tidak ditemukan di database'], 422);
        }

        // simpen
        $result = $this->courseTakenService->createDocument([
            'user_id' => $validated['user_id'],
            'course_id' => $validated['course_id'],
            'tutor_id'=>$validated['tutor_id'],
        ]);

        return response()->json([
            'data' => $result,
        ], 201);
    }
}
