<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\FirebaseTokenService;

class TutorCourseController extends Controller
{
    protected FirestoreService $tutorcourseService;
    protected FirestoreService $courseService;
    protected FirestoreService $tutorServive;
    public function __construct()
    {
        $this->courseService = new FirestoreService('course', app(FirebaseTokenService::class));
        $this->tutorServive = new FirestoreService('tutor', app(FirebaseTokenService::class));
        $this->tutorcourseService = new FirestoreService('tutorCourse', app(FirebaseTokenService::class));
    }

    public function index()
    {
        $tutor = $this->tutorcourseService->getAllDocuments();

        return response()->json($tutor);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tutor_id' => 'required|string',
            'course_id' => 'required|string',
        ]);
        $tutor_list = $this->tutorServive->getAllDocuments();

            $isTutorIdValid = collect($tutor_list)->contains(function ($item) use ($validated) {
                return isset($item['tutor_id']) && $item['tutor_id'] === $validated['tutor_id'];
            });
            if (!$isTutorIdValid) {
                return response()->json(['message' => 'tutor_id tidak ditemukan di database'], 422);
            }
        $course_list = $this->courseService->getAllDocuments();

            $isCourseIdValid = collect($course_list)->contains(function ($item) use ($validated) {
                return isset($item['course_id']) && $item['course_id'] === $validated['course_id'];
            });
            if (!$isCourseIdValid) {
                return response()->json(['message' => 'course_id tidak ditemukan di database'], 422);
            }

        // 1. Simpan dokumen tanpa user_id
        $result = $this->tutorcourseService->createDocument([
            'tutor_id'=>$validated['tutor_id'],
            'course_id'=>$validated['course_id'],
        ]);

        return response()->json([
            'data' => $result,
        ], 201);
    }
}
