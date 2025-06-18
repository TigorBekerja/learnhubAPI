<?php

namespace App\Http\Controllers\Api;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\FirebaseTokenService;

class AnswerForumController extends Controller
{
    protected FirestoreService $forumService, $userService, $answerService;

    public function __construct()
    {
        $this->forumService = new FirestoreService('forums', app(FirebaseTokenService::class));
        $this->userService = new FirestoreService('users', app(FirebaseTokenService::class));
        $this->answerService = new FirestoreService('answer_forums', app(FirebaseTokenService::class));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'forum_id' => 'required|string',
            'user_id' => 'required|string',
            'answer' => 'required|string',
        ]);
        // validasi user id
        $userList = $this->userService->getAllDocuments(); 

        $isUserValid = collect($userList)->contains(function ($item) use ($data) {
            return isset($item['user_id']) && $item['user_id'] === $data['user_id'];
        });

        if (!$isUserValid) {
            return response()->json(['message' => 'user id tidak ditemukan di database'], 422);
        }

        //validasi forum id
        $forumList = $this->forumService->getAllDocuments(); 

        $isForumValid = collect($forumList)->contains(function ($item) use ($data) {
            return isset($item['forum_id']) && $item['forum_id'] === $data['forum_id'];
        });

        if (!$isForumValid) {
            return response()->json(['message' => 'forum id tidak ditemukan di database'], 422);
        }

        // 1. Simpan dokumen tanpa forum id
        $result = $this->answerService->createDocument([
            'forum_id' => $data['forum_id'],
            'user_id' => $data['user_id'],
            'answer' => $data['answer'],
            'like'=> "0", //null for some kind of reason
            'dislike'=> "0", //null for some kind of reason
            'date'=> Carbon::now()->toDateTimeString(), // buat ambil date
        ]);

        // 2. Ambil ID dokumen dari response Firestore
        $docName = $result['name'];
        $parts = explode('/', $docName);
        $answerid = end($parts);

        // 3. Ambil data dokumen lama
        $oldData = $this->answerService->getDocumentById('answer_forums', $answerid);

        // 4. Extract nilai string dari oldData, atau kosongkan jika tidak ada
        $oldDataPlain = [];
        foreach ($oldData ?? [] as $key => $value) {
            // Karena di getDocumentById kamu ambil 'fields' langsung, setiap field ada tipe stringValue
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        // 5. Gabungkan data lama dengan faculty_id baru
        $updatedData = array_merge($oldDataPlain, ['answer_id' => $answerid]);

        // 6. Update dokumen dengan data lengkap (nama + faculty_id)
        $this->answerService->updateDocument($answerid, $updatedData);

        // 7. Gabungkan data untuk dikembalikan ke response
        $data = array_merge($oldDataPlain, ['answer_id' => $answerid]);

        return response()->json([
            'message' => 'answer berhasil ditambahkan',
            'data' => $data
        ], 201);

    }

    public function index()
    {
        $result = $this->answerService->getDocuments();

        return response()->json($result);
    }

    public function update(Request $request, string $answer_id) {
        try {
            $data = $request->validate([
                'answer' => 'nullable|string',
                'like' => 'nullable|numeric',
                'dislike' => 'nullable|numeric'
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return $th->validator->errors();
        }

        // validasi id answer
        $oldData = $this->forumService->getDocumentById('answer_forums', $answer_id);

        if (!$oldData) {
            return response()->json(['message' => 'answer id tidak ditemukan'], 404);
        }

        // Ambil nilai asli dari dokumen Firestore
        $oldDataPlain = [];
        foreach ($oldData as $key => $value) {
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }
        foreach (['answer', 'like', 'dislike'] as $field) {
            if (isset($data[$field])) {
                $oldDataPlain[$field] = $data[$field];
            }
        }

        // update date, kalau answer berubah
        if (isset($data['answer'])) {
            $oldDataPlain['date'] = Carbon::now()->toDateTimeString(); // buat ambil date
        }
        $oldDataPlain['answer_id'] = $answer_id;
        $this->answerService->updateDocument($answer_id, $oldDataPlain);

        return response()->json([
            'message' => 'answer berhasil diupdate',
            'data' => $oldDataPlain,
        ]);
    }
}
