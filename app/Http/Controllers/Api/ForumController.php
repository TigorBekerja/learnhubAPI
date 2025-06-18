<?php

namespace App\Http\Controllers\Api;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\FirebaseTokenService;

class ForumController extends Controller
{
    protected FirestoreService $forumService, $userService;

    public function __construct()
    {
        $this->forumService = new FirestoreService('forums', app(FirebaseTokenService::class));
        $this->userService = new FirestoreService('users', app(FirebaseTokenService::class));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|string',
            'header_question' => 'required|string',
            'question' => 'required|string',
        ]);

        // validasi user id
        $userList = $this->userService->getAllDocuments(); 

        $isUserValid = collect($userList)->contains(function ($item) use ($data) {
            return isset($item['user_id']) && $item['user_id'] === $data['user_id'];
        });

        if (!$isUserValid) {
            return response()->json(['message' => 'user id tidak ditemukan di database'], 422);
        }

        // 1. Simpan dokumen tanpa forum id
        $result = $this->forumService->createDocument([
            'user_id' => $data['user_id'],
            'header_question' => $data['header_question'],
            'question' => $data['question'],
            'date'=> Carbon::now()->toDateTimeString(), // buat ambil date
        ]);

        // 2. Ambil ID dokumen dari response Firestore
        $docName = $result['name'];
        $parts = explode('/', $docName);
        $forumid = end($parts);

        // 3. Ambil data dokumen lama
        $oldData = $this->forumService->getDocumentById('forums', $forumid);

        // 4. Extract nilai string dari oldData, atau kosongkan jika tidak ada
        $oldDataPlain = [];
        foreach ($oldData ?? [] as $key => $value) {
            // Karena di getDocumentById kamu ambil 'fields' langsung, setiap field ada tipe stringValue
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        // 5. Gabungkan data lama dengan faculty_id baru
        $updatedData = array_merge($oldDataPlain, ['forum_id' => $forumid]);

        // 6. Update dokumen dengan data lengkap (nama + faculty_id)
        $this->forumService->updateDocument($forumid, $updatedData);

        // 7. Gabungkan data untuk dikembalikan ke response
        $data = array_merge($oldDataPlain, ['forum_id' => $forumid]);

        return response()->json([
            'message' => 'forum berhasil ditambahkan',
            'data' => $data
        ], 201);

    }

    public function index()
    {
        $result = $this->forumService->getDocuments();

        return response()->json($result);
    }

    public function update(Request $request, string $forum_id) {
        try {
            $data = $request->validate([
                'header_question' => 'nullable|string',
                'question' => 'nullable|string'
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return $th->validator->errors();
        }

        // validasi id forum
        $oldData = $this->forumService->getDocumentById('forums', $forum_id);

        if (!$oldData) {
            return response()->json(['message' => 'forum id tidak ditemukan'], 404);
        }

        // Ambil nilai asli dari dokumen Firestore
        $oldDataPlain = [];
        foreach ($oldData as $key => $value) {
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }
        foreach (['header_question', 'question'] as $field) {
            if (isset($data[$field])) {
                $oldDataPlain[$field] = $data[$field];
            }
        }
        // update date, kalau header atau question berubah
        if (isset($data['header_question']) || isset($data['question'])) {
            $oldDataPlain['date'] = Carbon::now()->toDateTimeString(); // buat ambil date
        }
        $oldDataPlain['forum_id'] = $forum_id;
        //update
        $this->forumService->updateDocument($forum_id, $oldDataPlain);

        return response()->json([
            'message' => 'forum berhasil diupdate',
            'data' => $oldDataPlain,
        ]);
    }

}
