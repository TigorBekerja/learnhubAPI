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

    public function update(Request $request, string $prodiId) {
        try {
            $data = $request->validate([
                'faculty_id' => 'nullable|string',
                'nama' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return $th->validator->errors();
        }

        // validasi id prodi
        $oldData = $this->prodiService->getDocumentById('prodi', $prodiId);

        if (!$oldData) {
            return response()->json(['message' => 'prodi tidak ditemukan'], 404);
        }

        // validasi id fakultas baru, kalau ada
        if (isset($data['faculty_id'])) {
            $facultyList = $this->facultyService->getAllDocuments();

            $isFacultyValid = collect($facultyList)->contains(function ($item) use ($data) {
                return isset($item['faculty_id']) && $item['faculty_id'] === $data['faculty_id'];
            });

            if (!$isFacultyValid) {
                return response()->json(['message' => 'fakultas id tidak ada di database'], 422);
            }
        }

        // validasi nama prodi, ga boleh ada nama yang sama
        if (isset($data['nama'])) {
            $prodiList = $this->prodiService->getAllDocuments();

            $isprodiValid = collect($prodiList)->contains(function ($item) use ($data) {
                return isset($item['nama']) && $item['nama'] === $data['nama'];
            });

            if ($isprodiValid) {
                return response()->json(['message' => 'nama sudah ada di database'], 422);
            }
        }

        // Ambil nilai asli dari dokumen Firestore
        $oldDataPlain = [];
        foreach ($oldData as $key => $value) {
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        foreach (['faculty_id', 'nama'] as $field) {
            if (isset($data[$field])) {
                $oldDataPlain[$field] = $data[$field];
            }
        }

        // Siapkan data yang akan diupdate (hanya jika field disediakan)
        //$oldDataPlain['faculty_id'] = $data['faculty_id'];
        //$oldDataPlain['nama'] = $data['nama'];

        // Pastikan user_id tetap disimpan
        $oldDataPlain['prodi_id'] = $prodiId;

        // Update dokumen di Firestore
        $this->prodiService->updateDocument($prodiId, $oldDataPlain);

        return response()->json([
            'message' => 'prodi berhasil diupdate',
            'data' => $oldDataPlain,
        ]);
    }
}
