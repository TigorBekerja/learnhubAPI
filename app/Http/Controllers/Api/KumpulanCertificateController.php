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
        $tutor = $this->kumpulanCertificateService->getAllDocuments();

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
}
