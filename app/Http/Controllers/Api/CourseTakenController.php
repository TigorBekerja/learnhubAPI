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
        $tutor = $this->courseTakenService->getDocuments();

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

        // validasi course dan tutor id
        $tutorCourseList = $this->tutorCourseServive->getAllDocuments();

        $valid = collect($tutorCourseList)->contains(function ($item) use ($validated) {
            return (isset($item['course_id']) && $item['course_id'] === $validated['course_id']) && (isset($item['tutor_id']) && $item['tutor_id'] === $validated['tutor_id']);
        });
        if (!$valid) {
            return response()->json(['message' => 'course id dan\atau tutor id tidak ditemukan di database'], 422);
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

    public function update(Request $request, string $id) {
        try {
            $data = $request->validate([
                'user_id' => 'nullable|string',
                'course_id' => 'nullable|string',
                'tutor_id' => 'nullable|string',
            ]);
        }catch (\Illuminate\Validation\ValidationException $th) {
            return $th->validator->errors();
        }
        
        // validasi id schedule
        $oldDataRaw = $this->courseTakenService->getDocumentById('course_takens', $id);
        if (!$oldDataRaw) {
            return response()->json(['message' => 'id tidak ditemukan'], 404);
        }

        $oldData = [];
        foreach ($oldDataRaw as $key => $value) {
            $oldData[$key] = $value['stringValue'] ?? null;
        }

        // assign setelah valid
        if (isset($data['user_id'])) {
            $userList = $this->userService->getAllDocuments();

            $isUserValid = collect($userList)->contains(function ($item) use ($data) {
                return isset($item['user_id']) && $item['user_id'] === $data['user_id'];
            });

            if (!$isUserValid) {
                return response()->json(['message' => 'user id tidak ada di database'], 422);
            }
        }

        // validasi course sama tutor update
        $course_update = isset($data['course_id']);
        $tutor_update = isset($data['tutor_id']);

        if ($course_update && !$tutor_update) {
            $tutorcourse = $this->tutorCourseServive->getAllDocuments();

            $isCourseValid = collect($tutorcourse)->contains(function ($item) use ($data, $oldData) {
                return (isset($item['course_id']) && $item['course_id'] === $data['course_id']) && (isset($item['tutor_id']) && $item['tutor_id'] === $oldData['tutor_id']);
            });

            if (!$isCourseValid) {
                return response()->json(['message' => 'course id tidak ada di database'], 422);
            }
        } else if (!$course_update && $tutor_update) {
            $tutorcourse = $this->tutorCourseServive->getAllDocuments();

            $isTutorValid = collect($tutorcourse)->contains(function ($item) use ($data, $oldData) {
                return (isset($item['course_id']) && $item['course_id'] === $oldData['course_id']) && (isset($item['tutor_id']) && $item['tutor_id'] === $data['tutor_id']);
            });

            if (!$isTutorValid) {
                return response()->json(['message' => 'course id tidak ada di database'], 422);
            }
        } else if ($course_update && $tutor_update) {
            $tutorcourse = $this->tutorCourseServive->getAllDocuments();

            $isTutorCourseValid = collect($tutorcourse)->contains(function ($item) use ($data) {
                return (isset($item['course_id']) && $item['course_id'] === $data['course_id']) && (isset($item['tutor_id']) && $item['tutor_id'] === $data['tutor_id']);
            });

            if (!$isTutorCourseValid) {
                return response()->json(['message' => 'course id dan tutor id tidak ada di database'], 422);
            }
        }

        foreach (['user_id', 'course_id', 'tutor_id'] as $field) {
            if (isset($data[$field])) {
                $oldData[$field] = $data[$field];
            }
        }

        $this->courseTakenService->updateDocument($id, $oldData);

        return response()->json([
            'message' => 'course taken berhasil diupdate',
            'data' => $oldData,
        ]);
    }
}
