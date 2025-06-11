<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\FirebaseTokenService;

class ProdiController extends Controller
{
    protected FirestoreService $prodiService, $facultyService;

    public function __construct()
    {
        $this->prodiService = new FirestoreService('prodi', app(FirebaseTokenService::class));
        $this->facultyService = new FirestoreService('faculties', app(FirebaseTokenService::class));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'faculty_id' => 'required|string',
            'nama' => 'required|string',
        ]);

        // validasi faculty_id
        $facultyList = $this->facultyService->getAllDocuments(); 

        $isFacultyIdValid = collect($facultyList)->contains(function ($item) use ($data) {
            return isset($item['faculty_id']) && $item['faculty_id'] === $data['faculty_id'];
        });

        if (!$isFacultyIdValid) {
            return response()->json(['message' => 'faculty_id tidak ditemukan di database'], 422);
        }

        // 1. Simpan dokumen tanpa faculty_id
        $result = $this->prodiService->createDocument([
            'nama' => $data['nama'],
            'faculty_id' => $data['faculty_id']
        ]);

        // 2. Ambil ID dokumen dari response Firestore
        $docName = $result['name'];
        $parts = explode('/', $docName);
        $prodiId = end($parts);

        // 3. Ambil data dokumen lama
        $oldData = $this->prodiService->getDocumentById('prodi', $prodiId);

        // 4. Extract nilai string dari oldData, atau kosongkan jika tidak ada
        $oldDataPlain = [];
        foreach ($oldData ?? [] as $key => $value) {
            // Karena di getDocumentById kamu ambil 'fields' langsung, setiap field ada tipe stringValue
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        // 5. Gabungkan data lama dengan faculty_id baru
        $updatedData = array_merge($oldDataPlain, ['prodi_id' => $prodiId]);

        // 6. Update dokumen dengan data lengkap (nama + faculty_id)
        $this->prodiService->updateDocument($prodiId, $updatedData);

        // 7. Gabungkan data untuk dikembalikan ke response
        $data = array_merge($oldDataPlain, ['prodi_id' => $prodiId]);

        return response()->json([
            'message' => 'Prodi berhasil ditambahkan',
            'data' => $data
        ], 201);

    }

    public function index()
    {
        $result = $this->prodiService->getDocuments();

        return response()->json($result);
    }
}
