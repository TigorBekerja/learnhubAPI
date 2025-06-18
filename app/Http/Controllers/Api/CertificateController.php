<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    protected FirestoreService $certificateService;

    public function __construct()
    {
        $this->certificateService = new FirestoreService('certificates', app(\App\Services\FirebaseTokenService::class));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string',
            'mata_kuliah' => 'required|string',
            'gambar' => 'required|url'
        ]);

        // 1. Simpan certificate tanpa certificate_id
        $result = $this->certificateService->createDocument([
            'nama' => $validated['nama'],
            'mata_kuliah' => $validated['mata_kuliah'],
            'gambar' => $validated['gambar']
        ]);

        // 2. Ambil ID dokumen dari response Firestore
        $docName = $result['name'];
        $parts = explode('/', $docName);
        $certificateID = end($parts);

        // 3. Ambil data dokumen lama
        $oldData = $this->certificateService->getDocumentById('certificates', $certificateID);

        // 4. Extract nilai string dari oldData, atau kosongkan jika tidak ada
        $oldDataPlain = [];
        foreach ($oldData ?? [] as $key => $value) {
            // Karena di getDocumentById kamu ambil 'fields' langsung, setiap field ada tipe stringValue
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        // 5. Gabungkan data lama dengan faculty_id baru
        $updatedData = array_merge($oldDataPlain, ['certificate_id' => $certificateID]);

        // 6. Update dokumen dengan data lengkap 
        $this->certificateService->updateDocument($certificateID, $updatedData);

        // 7. Gabungkan data untuk dikembalikan ke response
        $data = array_merge($oldDataPlain, ['certificate_id' => $certificateID]);

        return response()->json([
            'message' => 'certificate berhasil ditambahkan',
            'data' => $data
        ], 201);
    }

    public function index()
    {
        $result = $this->certificateService->getDocuments();

        return response()->json($result);
    }

    public function update(Request $request, string $certificate_id) {
        try {
            $data = $request->validate([
                'nama' => 'required|string',
                'mata_kuliah' => 'required | string',
                'gambar' => 'required|url',
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return $th->validator->errors();
        }

        $oldData = $this->certificateService->getDocumentById('certificates', $certificate_id);

        if (!$oldData) {
            return response()->json(['message' => 'certificate id tidak ditemukan'], 404);
        }

        // Ambil nilai asli dari dokumen Firestore
        $oldDataPlain = [];
        foreach ($oldData as $key => $value) {
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        $oldDataPlain['nama'] = $data['nama'];
        $oldDataPlain['mata_kuliah'] = $data['mata_kuliah'];
        $oldDataPlain['gambar'] = $data['gambar'];

        $this->certificateService->updateDocument($certificate_id, $oldDataPlain);

        return response()->json([
            'message' => 'certificate berhasil diupdate',
            'data' => $oldDataPlain,
        ]);
    }
}
