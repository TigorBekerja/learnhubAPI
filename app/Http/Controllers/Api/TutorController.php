<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\FirebaseTokenService;

class TutorController extends Controller
{
    protected FirestoreService $firestoreService;
    protected FirestoreService $tutorServive;
    public function __construct()
    {
        $this->firestoreService = new FirestoreService('users', app(FirebaseTokenService::class));
        $this->tutorServive = new FirestoreService('tutor', app(FirebaseTokenService::class));
    }

    public function index()
    {
        $tutor = $this->tutorServive->getAllDocuments();

        return response()->json($tutor);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|string',
            'rating_mean' => 'nullable',
            'num_customer'=>'nullable',
        ]);
        $user_list = $this->firestoreService->getAllDocuments(); // asumsikan dari koleksi 'users'

            $isProdiIdValid = collect($user_list)->contains(function ($item) use ($validated) {
                return isset($item['user_id']) && $item['user_id'] === $validated['user_id'];
            });
            if (!$isProdiIdValid) {
                return response()->json(['message' => 'user_id tidak ditemukan di database'], 422);
            }

        // 1. Simpan dokumen tanpa user_id
        $result = $this->tutorServive->createDocument([
            'user_id' => $validated['user_id'],
            'rating_mean'=>$validated['rating_mean'] ?? 0,
            'num_customer'=>$validated['num_customer'] ?? 0,
        ]);

        // 2. Ambil ID dokumen dari response Firestore
        $docName = $result['name'];
        $parts = explode('/', $docName);
        $tutorid = end($parts);

        // 3. Ambil data dokumen lama
        $oldData = $this->tutorServive->getDocumentById('tutor', $tutorid);
        
        // 4. Extract nilai string dari oldData, atau kosongkan jika tidak ada
        $oldDataPlain = [];
        foreach ($oldData ?? [] as $key => $value) {
            // Karena di getDocumentById kamu ambil 'fields' langsung, setiap field ada tipe stringValue
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        // 5. Gabungkan data lama dengan faculty_id baru
        $updatedData = array_merge($oldDataPlain, ['tutor_id' => $tutorid]);

        // 6. Update dokumen dengan data lengkap (nama + faculty_id)
        $this->tutorServive->updateDocument($tutorid, $updatedData);

        // 7. Gabungkan data untuk dikembalikan ke response
        $data = array_merge($oldDataPlain, ['tutor_id' => $tutorid]);

        return response()->json([
            'data' => $data,
        ], 201);
    }
}
