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

        if (isset($validated['nama'])) {
            $facultyList = $this->facultyService->getAllDocuments();

            $isFacultyValid = collect($facultyList)->contains(function ($item) use ($validated) {
                return isset($item['nama']) && $item['nama'] === $validated['nama'];
            });

            if ($isFacultyValid) {
                return response()->json(['message' => 'nama sudah ada di database'], 422);
            }
        }

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

    public function update(Request $request, string $faculty_id) {
        try {
            $validated = $request->validate([
                'nama' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return $th->validator->errors();
        }

        $oldData = $this->facultyService->getDocumentById('faculties', $faculty_id);

        if (!$oldData) {
            return response()->json(['message' => 'fakultas tidak ditemukan'], 404);
        }

        // Ambil nilai asli dari dokumen Firestore
        $oldDataPlain = [];
        foreach ($oldData as $key => $value) {
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        if (isset($validated['nama'])) {
            $facultyList = $this->facultyService->getAllDocuments();

            $isFacultyValid = collect($facultyList)->contains(function ($item) use ($validated) {
                return isset($item['nama']) && $item['nama'] === $validated['nama'];
            });

            if ($isFacultyValid) {
                return response()->json(['message' => 'nama sudah ada di database'], 422);
            }
        }

        $oldDataPlain['nama'] = $validated['nama'];
        $oldDataPlain['faculty_id'] = $faculty_id;
        $this->facultyService->updateDocument($faculty_id, $oldDataPlain);
        return response()->json([
            'message' => 'fakultas berhasil diupdate',
            'data' => $oldDataPlain,
        ]);
    }
}
