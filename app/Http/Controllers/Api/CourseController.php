<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\FirebaseTokenService;

class CourseController extends Controller
{
    protected FirestoreService $courseService;
    protected FirestoreService $prodiService;
    public function __construct()
    {
        $this->courseService = new FirestoreService('course', app(FirebaseTokenService::class));
        $this->prodiService = new FirestoreService('prodi', app(FirebaseTokenService::class));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'prodi_id' => 'required|string',
            'nama' => 'required|string',
        ]);

        // validasi prodi_id ada di koleksi prodi, jika perlu
        $prodiList = $this->prodiService->getAllDocuments(); // asumsikan dari koleksi 'prodi'

        $isProdiIdValid = collect($prodiList)->contains(function ($item) use ($data) {
            return isset($item['prodi_id']) && $item['prodi_id'] === $data['prodi_id'];
        });

        if (!$isProdiIdValid) {
            return response()->json(['message' => 'prodi_id tidak ditemukan di database'], 422);
        }

        // 1. Simpan dokumen tanpa faculty_id
        $result = $this->courseService->createDocument([
            'nama' => $data['nama'],
            'prodi_id' => $data['prodi_id']
        ]);

        // 2. Ambil ID dokumen dari response Firestore
        $docName = $result['name'];
        $parts = explode('/', $docName);
        $courseid = end($parts);

        // 3. Ambil data dokumen lama
        $oldData = $this->courseService->getDocumentById('course', $courseid);

        // 4. Extract nilai string dari oldData, atau kosongkan jika tidak ada
        $oldDataPlain = [];
        foreach ($oldData ?? [] as $key => $value) {
            // Karena di getDocumentById kamu ambil 'fields' langsung, setiap field ada tipe stringValue
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        // 5. Gabungkan data lama dengan faculty_id baru
        $updatedData = array_merge($oldDataPlain, ['course_id' => $courseid]);

        // 6. Update dokumen dengan data lengkap (nama + faculty_id)
        $this->courseService->updateDocument($courseid, $updatedData);

        // 7. Gabungkan data untuk dikembalikan ke response
        $data = array_merge($oldDataPlain, ['course_id' => $courseid]);

        return response()->json(['message' => 'Course berhasil ditambahkan', 'data' => $data], 201);
    }

    public function index()
    {
        $result = $this->courseService->getDocuments();

        return response()->json($result);
    }
}
