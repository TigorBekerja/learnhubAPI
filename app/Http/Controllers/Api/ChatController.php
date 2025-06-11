<?php

namespace App\Http\Controllers\Api;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\FirebaseTokenService;

class ChatController extends Controller
{
    protected FirestoreService $userService, $tutorService, $chatService;

    public function __construct()
    {
        $this->tutorService = new FirestoreService('tutor', app(FirebaseTokenService::class));
        $this->userService = new FirestoreService('users', app(FirebaseTokenService::class));
        $this->chatService = new FirestoreService('chats', app(FirebaseTokenService::class));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|string',
            'tutor_id' => 'required|string',
            'sender_id' => 'required|string',
            'text' => 'required|string'
        ]);

        // validasi user id
        $userList = $this->userService->getAllDocuments(); 

        $isUserValid = collect($userList)->contains(function ($item) use ($data) {
            return isset($item['user_id']) && $item['user_id'] === $data['user_id'];
        });

        if (!$isUserValid) {
            return response()->json(['message' => 'user id tidak ditemukan di database'], 422);
        }

        // validasi tutor_id
        $tutorList = $this->tutorService->getAllDocuments(); 

        $isTutorValid = collect($tutorList)->contains(function ($item) use ($data) {
            return isset($item['tutor_id']) && $item['tutor_id'] === $data['tutor_id'];
        });

        if (!$isTutorValid) {
            return response()->json(['message' => 'tutor id tidak ditemukan di database'], 422);
        }

        //validasi sender_id
        if ($data['user_id'] != $data['sender_id'] && $data['tutor_id'] != $data['sender_id']) {
            return response()->json(['message' => 'sender id harus tutor atau user di dalam chat'], 422);
        }

        if ($data['user_id'] == $data['sender_id'] && $data['tutor_id'] == $data['sender_id']) {
            return response()->json(['message' => 'bruh, u can\'t do that'], 422);
        }

        // 1. Simpan dokumen tanpa forum id
        $result = $this->chatService->createDocument([
            'user_id' => $data['user_id'],
            'tutor_id' => $data['tutor_id'],
            'sender_id' => $data['sender_id'],
            'text' => $data['text'],
            'date'=> Carbon::now()->toDateTimeString(), // buat ambil date
        ]);

        // 2. Ambil ID dokumen dari response Firestore
        $docName = $result['name'];
        $parts = explode('/', $docName);
        $chatId = end($parts);

        // 3. Ambil data dokumen lama
        $oldData = $this->chatService->getDocumentById('chats', $chatId);

        // 4. Extract nilai string dari oldData, atau kosongkan jika tidak ada
        $oldDataPlain = [];
        foreach ($oldData ?? [] as $key => $value) {
            // Karena di getDocumentById kamu ambil 'fields' langsung, setiap field ada tipe stringValue
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        // 5. Gabungkan data lama dengan faculty_id baru
        $updatedData = array_merge($oldDataPlain, ['chat_id' => $chatId]);

        // 6. Update dokumen dengan data lengkap (nama + faculty_id)
        $this->chatService->updateDocument($chatId, $updatedData);

        // 7. Gabungkan data untuk dikembalikan ke response
        $data = array_merge($oldDataPlain, ['chat_id' => $chatId]);

        return response()->json([
            'message' => 'chat berhasil ditambahkan',
            'data' => $data
        ], 201);

    }

    public function index()
    {
        $result = $this->chatService->getDocuments();

        return response()->json($result);
    }
}