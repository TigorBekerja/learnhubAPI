<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\FirebaseTokenService;

class ScheduleController extends Controller
{
    protected FirestoreService $userService, $tutorCourseService, $scheduleService;

    public function __construct()
    {
        $this->userService = new FirestoreService('users', app(FirebaseTokenService::class));
        $this->tutorCourseService = new FirestoreService('tutorCourse', app(FirebaseTokenService::class));
        $this->scheduleService = new FirestoreService('schedules', app(FirebaseTokenService::class));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'user_id' => 'required|string',
                'tutor_id'=>'required|string',
                'course_id' => 'required|string',
                'date' => 'required|date_format:d-m-Y H:i:s'
            ]);
        }catch (\Illuminate\Validation\ValidationException $th) {
            return $th->validator->errors();
        }
        

        // validasi user_id
        $userList = $this->userService->getAllDocuments(); 

        $isUserIdValid = collect($userList)->contains(function ($item) use ($data) {
            return isset($item['user_id']) && $item['user_id'] === $data['user_id'];
        });

        if (!$isUserIdValid) {
            return response()->json(['message' => 'user id tidak ditemukan di database'], 422);
        }

        // validasi tutor id
        $tutorList = $this->tutorCourseService->getAllDocuments(); 

        $isTutorIDValid = collect($tutorList)->contains(function ($item) use ($data) {
            return isset($item['tutor_id']) && $item['tutor_id'] === $data['tutor_id'];
        });

        if (!$isTutorIDValid) {
            return response()->json(['message' => 'tutor id tidak ditemukan di database'], 422);
        }

        // validasi course id
        $courseList = $this->tutorCourseService->getAllDocuments();

        $isCourseIDValid = collect($courseList)->contains(function ($item) use ($data) {
            return isset($item['course_id']) && $item['course_id'] === $data['course_id'];
        });

        if (!$isCourseIDValid) {
            return response()->json(['message' => 'course id tidak ditemukan di database'], 422);
        }

        // 1. Simpan dokumen tanpa faculty_id
        $result = $this->scheduleService->createDocument([
            'user_id' => $data['user_id'],
            'tutor_id' => $data['tutor_id'],
            'course_id' => $data['course_id'],
            'date'=> $data['date']
        ]);

        // 2. Ambil ID dokumen dari response Firestore
        $docName = $result['name'];
        $parts = explode('/', $docName);
        $scheduleId = end($parts);

        // 3. Ambil data dokumen lama
        $oldData = $this->scheduleService->getDocumentById('schedules', $scheduleId);

        // 4. Extract nilai string dari oldData, atau kosongkan jika tidak ada
        $oldDataPlain = [];
        foreach ($oldData ?? [] as $key => $value) {
            // Karena di getDocumentById kamu ambil 'fields' langsung, setiap field ada tipe stringValue
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        // 5. Gabungkan data lama dengan faculty_id baru
        $updatedData = array_merge($oldDataPlain, ['schedule_id' => $scheduleId]);

        // 6. Update dokumen dengan data lengkap (nama + faculty_id)
        $this->scheduleService->updateDocument($scheduleId, $updatedData);

        // 7. Gabungkan data untuk dikembalikan ke response
        $data = array_merge($oldDataPlain, ['schedule_id' => $scheduleId]);

        return response()->json([
            'message' => 'schedule berhasil ditambahkan',
            'data' => $data
        ], 201);

    }

    public function index()
    {
        $result = $this->scheduleService->getDocuments();

        return response()->json($result);
    }

    public function update(Request $request, string $schedule_id) {
        // cuma ganti date
        try {
            $data = $request->validate([
                'date' => 'required|date_format:d-m-Y H:i:s'
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return $th->validator->errors();
        }

        // validasi id schedule
        $oldData = $this->scheduleService->getDocumentById('schedules', $schedule_id);

        if (!$oldData) {
            return response()->json(['message' => 'schedule id tidak ditemukan'], 404);
        }

        // Ambil nilai asli dari dokumen Firestore
        $oldDataPlain = [];
        foreach ($oldData as $key => $value) {
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        $oldDataPlain['schedule_id'] = $schedule_id;
        $oldDataPlain['date'] = $data['date'];
        
        //update
        $this->scheduleService->updateDocument($schedule_id, $oldDataPlain);

        return response()->json([
            'message' => 'schedule berhasil diupdate',
            'data' => $oldDataPlain,
        ]);
    }

    public function destroy(string $schedule_id) {
        $oldData = $this->scheduleService->getDocumentById('schedules', $schedule_id);

        if (!$oldData) {
            return response()->json(['message' => 'schedule id tidak ditemukan'], 404);
        }

        $this->scheduleService->deleteDocument($schedule_id);
        return response()->json([
            'message' => 'schedule berhasil dihapus'
        ], 200);
    }
}
