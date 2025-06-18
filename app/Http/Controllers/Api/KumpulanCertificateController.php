<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\FirebaseTokenService;

class KumpulanCertificateController extends Controller
{
    protected FirestoreService $kumpulanCertificateService;
    protected FirestoreService $certificateService;
    protected FirestoreService $tutorServive;
    public function __construct()
    {
        $this->certificateService = new FirestoreService('certificates', app(FirebaseTokenService::class));
        $this->tutorServive = new FirestoreService('tutor', app(FirebaseTokenService::class));
        $this->kumpulanCertificateService = new FirestoreService('kumpulanCertificates', app(FirebaseTokenService::class));
    }

    public function index()
    {
        $tutor = $this->kumpulanCertificateService->getDocuments();

        return response()->json($tutor);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tutor_id' => 'required|string',
            'certificate_id' => 'required|string',
        ]);
        $tutor_list = $this->tutorServive->getAllDocuments();

        $isTutorIdValid = collect($tutor_list)->contains(function ($item) use ($validated) {
            return isset($item['tutor_id']) && $item['tutor_id'] === $validated['tutor_id'];
        });
        if (!$isTutorIdValid) {
            return response()->json(['message' => 'tutor_id tidak ditemukan di database'], 422);
        }

        $certificate_list = $this->certificateService->getAllDocuments();

        $isCertificateIdValid = collect($certificate_list)->contains(function ($item) use ($validated) {
            return isset($item['certificate_id']) && $item['certificate_id'] === $validated['certificate_id'];
        });
        if (!$isCertificateIdValid) {
            return response()->json(['message' => 'certificate id tidak ditemukan di database'], 422);
        }

        // simpen
        $result = $this->kumpulanCertificateService->createDocument([
            'tutor_id'=>$validated['tutor_id'],
            'certificate_id'=>$validated['certificate_id'],
        ]);

        return response()->json([
            'data' => $result,
        ], 201);
    }

    public function update(Request $request, string $id) {
        try {
            $validated = $request->validate([
                'tutor_id' => 'required|string',
                'certificate_id' => 'required | string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return $th->validator->errors();
        }

        $oldData = $this->kumpulanCertificateService->getDocumentById('kumpulanCertificates', $id);

        if (!$oldData) {
            return response()->json(['message' => 'kumpulan certificate id tidak ditemukan'], 404);
        }

        //validasi tutor
        if (isset($validated['tutor_id'])) {
            $tutorList = $this->tutorServive->getAllDocuments();

            $isTutorValid = collect($tutorList)->contains(function ($item) use ($validated) {
                return isset($item['tutor_id']) && $item['tutor_id'] === $validated['tutor_id'];
            });

            if (!$isTutorValid) {
                return response()->json(['message' => 'tidak ada tutor id'], 422);
            }
        }

        //validasi certificate  
        if (isset($validated['certificate_id'])) {
            $certificateList = $this->certificateService->getAllDocuments();

            $isCertificateValid = collect($certificateList)->contains(function ($item) use ($validated) {
                return isset($item['certificate_id']) && $item['certificate_id'] === $validated['certificate_id'];
            });

            if (!$isCertificateValid) {
                return response()->json(['message' => 'tidak ada certificate id'], 422);
            }
        }

        // Ambil nilai asli dari dokumen Firestore
        $oldDataPlain = [];
        foreach ($oldData as $key => $value) {
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        $oldDataPlain['tutor_id'] = $validated['tutor_id'];
        $oldDataPlain['certificate_id'] = $validated['certificate_id'];

        $this->tutorServive->updateDocument($id, $oldDataPlain);

        return response()->json([
            'message' => 'kumpulan certificate berhasil diupdate',
            'data' => $oldDataPlain,
        ]);
    }

    public function destroy(string $id) {
        $oldData = $this->kumpulanCertificateService->getDocumentById('kumpulanCertificates', $id);

        if (!$oldData) {
            return response()->json(['message' => 'kumpulan certificate id tidak ditemukan'], 404);
        }

        $this->kumpulanCertificateService->deleteDocument($id);
        
        return response()->json([
            'message' => 'kumpulan certificate berhasil dihapus'
        ], 200);
    }
}
