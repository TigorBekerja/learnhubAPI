<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\FirebaseTokenService;

class ReviewController extends Controller
{
    protected FirestoreService $userService, $tutorService, $reviewService;

    public function __construct()
    {
        $this->userService = new FirestoreService('users', app(FirebaseTokenService::class));
        $this->tutorService = new FirestoreService('tutor', app(FirebaseTokenService::class));
        $this->reviewService = new FirestoreService('reviews', app(FirebaseTokenService::class));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|string',
            'tutor_id' => 'required|string',
            'rating_tutor' => 'required|numeric',
            'comment_review' => 'required|string',
        ]);

        // validasi user_id
        $userList = $this->userService->getAllDocuments(); 

        $isUserIdValid = collect($userList)->contains(function ($item) use ($data) {
            return isset($item['user_id']) && $item['user_id'] === $data['user_id'];
        });

        if (!$isUserIdValid) {
            return response()->json(['message' => 'user_id tidak ditemukan di database'], 422);
        }

        // validasi tutor_id
        $tutorList = $this->tutorService->getAllDocuments(); 

        $isTutorValid = collect($tutorList)->contains(function ($item) use ($data) {
            return isset($item['tutor_id']) && $item['tutor_id'] === $data['tutor_id'];
        });

        if (!$isTutorValid) {
            return response()->json(['message' => 'tutor_id tidak ditemukan di database'], 422);
        }



        // 1. Simpan dokumen tanpa review_id
        $result = $this->reviewService->createDocument([
            'user_id' => $data['user_id'],
            'tutor_id' => $data['tutor_id'],
            'rating_tutor' => $data['rating_tutor'],
            'comment_review' => $data['comment_review'],
        ]);

        // 2. Ambil ID dokumen dari response Firestore
        $docName = $result['name'];
        $parts = explode('/', $docName);
        $reviewID = end($parts);

        // 3. Ambil data dokumen lama
        $oldData = $this->reviewService->getDocumentById('reviews', $reviewID);

        // 4. Extract nilai string dari oldData, atau kosongkan jika tidak ada
        $oldDataPlain = [];
        foreach ($oldData ?? [] as $key => $value) {
            // Karena di getDocumentById kamu ambil 'fields' langsung, setiap field ada tipe stringValue
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        // 5. Gabungkan data lama dengan faculty_id baru
        $updatedData = array_merge($oldDataPlain, ['review_id' => $reviewID]);

        // 6. Update dokumen dengan data lengkap (nama + faculty_id)
        $this->reviewService->updateDocument($reviewID, $updatedData);

        // 7. Gabungkan data untuk dikembalikan ke response
        $data = array_merge($oldDataPlain, ['review_id' => $reviewID]);

        return response()->json([
            'message' => 'review berhasil ditambahkan',
            'data' => $data
        ], 201);

    }

    public function index()
    {
        $result = $this->reviewService->getDocuments();

        return response()->json($result);
    }

    public function update(Request $request, string $review_id) {
        try {
            $data = $request->validate([
                'rating_tutor' => 'nullable|numeric',
                'comment_review'=>'nullable|string'
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return $th->validator->errors();
        }

        // validasi id schedule
        $oldData = $this->reviewService->getDocumentById('reviews', $review_id);

        if (!$oldData) {
            return response()->json(['message' => 'review id tidak ditemukan'], 404);
        }

        // Ambil nilai asli dari dokumen Firestore
        $oldDataPlain = [];
        foreach ($oldData as $key => $value) {
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        foreach (['rating_tutor', 'comment_review'] as $field) {
            if (isset($data[$field])) {
                $oldDataPlain[$field] = $data[$field];
            }
        }
        $oldDataPlain['review_id'] = $review_id;
        //update
        $this->reviewService->updateDocument($review_id, $oldDataPlain);

        return response()->json([
            'message' => 'review berhasil diupdate',
            'data' => $oldDataPlain,
        ]);
    }
}
