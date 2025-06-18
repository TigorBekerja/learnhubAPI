<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    protected FirestoreService $facultyService;

    public function __construct()
    {
        $this->facultyService = new FirestoreService('faculties', app(\App\Services\FirebaseTokenService::class));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string',
        ]);

        // 1. Simpan dokumen tanpa faculty_id
        $result = $this->facultyService->createDocument([
            'nama' => $validated['nama']
        ]);

        // 2. Ambil ID dokumen dari response Firestore
        $docName = $result['name'];
        $parts = explode('/', $docName);
        $facultyID = end($parts);

        // 3. Ambil data dokumen lama
        $oldData = $this->facultyService->getDocumentById('faculties', $facultyID);

        // 4. Extract nilai string dari oldData, atau kosongkan jika tidak ada
        $oldDataPlain = [];
        foreach ($oldData ?? [] as $key => $value) {
            // Karena di getDocumentById kamu ambil 'fields' langsung, setiap field ada tipe stringValue
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        // 5. Gabungkan data lama dengan faculty_id baru
        $updatedData = array_merge($oldDataPlain, ['faculty_id' => $facultyID]);

        // 6. Update dokumen dengan data lengkap (nama + faculty_id)
        $this->facultyService->updateDocument($facultyID, $updatedData);

        // 7. Gabungkan data untuk dikembalikan ke response
        $data = array_merge($oldDataPlain, ['faculty_id' => $facultyID]);

        return response()->json([
            'message' => 'Fakultas berhasil ditambahkan',
            'data' => $data
        ], 201);
    }


    public function index()
    {
        $result = $this->facultyService->getDocuments();

        return response()->json($result);
    }

    public function update(Request $request) {
        
    }
}
