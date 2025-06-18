<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\FirebaseTokenService;

class UserController extends Controller
{
    protected FirestoreService $firestoreService;
    protected FirestoreService $prodiService;

    public function __construct()
    {
        $this->firestoreService = new FirestoreService('users', app(FirebaseTokenService::class));
        $this->prodiService = new FirestoreService('prodi', app(FirebaseTokenService::class));
    }

    public function index()
    {
        $users = $this->firestoreService->getAllDocuments();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'nama' => 'required|string',
            'profile_picture' => 'nullable|url',
            'bio' => 'nullable|string',
            'prodi_id' => 'nullable|string',
            'no_telp' => 'nullable|numeric'// numeric buat validasi harus angka
        ]);

        // cek prodi id
        $prodiId = $validated['prodi_id'] ?? null;
        if ($prodiId != null) {
            $prodiList = $this->prodiService->getAllDocuments();

            $isProdiIdValid = collect($prodiList)->contains(function ($item) use ($validated) {
                return isset($item['prodi_id']) && $item['prodi_id'] === $validated['prodi_id'];
            });
            if (!$isProdiIdValid) {
                return response()->json(['message' => 'prodi_id tidak ditemukan di database'], 422);
            }
        }

        $validated['password'] = Hash::make($validated['password']);

        // 1. Simpan dokumen tanpa user_id
        $result = $this->firestoreService->createDocument([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'nama' => $validated['nama'],
            'profile_picture' => $validated['profile_picture'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'prodi_id' => $validated['prodi_id'] ?? null,
            'no_telp'=>$validated['no_telp'] ?? null,
        ]);

        // 2. Ambil ID dokumen dari response Firestore
        $docName = $result['name'];
        $parts = explode('/', $docName);
        $userId = end($parts);

        // 3. Ambil data dokumen lama
        $oldData = $this->firestoreService->getDocumentById('users', $userId);

        // 4. Extract nilai string dari oldData, atau kosongkan jika tidak ada
        $oldDataPlain = [];
        foreach ($oldData ?? [] as $key => $value) {
            // Karena di getDocumentById kamu ambil 'fields' langsung, setiap field ada tipe stringValue
            $oldDataPlain[$key] = $value['stringValue'] ?? null;
        }

        // 5. Gabungkan data lama dengan faculty_id baru
        $updatedData = array_merge($oldDataPlain, ['user_id' => $userId]);

        // 6. Update dokumen dengan data lengkap (nama + faculty_id)
        $this->firestoreService->updateDocument($userId, $updatedData);

        // 7. Gabungkan data untuk dikembalikan ke response
        $data = array_merge($oldDataPlain, ['user_id' => $userId]);

        return response()->json([
            'user_id' => $userId,
            'data' => $data,
        ], 201);
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Ambil semua user
        $users = $this->firestoreService->getAllDocuments();

        // Cari user yang email-nya cocok
        $matchedUser = null;
        foreach ($users as $user) {
            if (
                isset($user['email']) &&
                strtolower($user['email']) === strtolower($credentials['email'])
            ) {
                $matchedUser = $user;
                break;
            }
        }

        // Jika tidak ditemukan
        if (!$matchedUser) {
            return response()->json([
                'message' => 'User tidak ditemukan'
            ], 401);
        }

        // Cek password
        if (!Hash::check($credentials['password'], $matchedUser['password'])) {
            return response()->json([
                'message' => 'Password salah'
            ], 401);
        }

        // Login sukses
        return response()->json([
            'message' => 'Login berhasil',
            'user' => $matchedUser
        ]);
    }

    public function register(Request $request)
    {
        // Tambahkan nilai default null untuk optional fields agar tidak error di validasi store()
        $request->merge([
            'profile_picture' => $request->input('profile_picture', null),
            'bio' => $request->input('bio', null),
        ]);

        // Cek apakah email sudah digunakan
        $existingUsers = $this->firestoreService->getAllDocuments();
        foreach ($existingUsers as $user) {
            if (($user['email'] ?? '') === $request->input('email')) {
                return response()->json([
                    'message' => 'Email sudah digunakan.'
                ], 409);
            }
        }

        // Reuse store() untuk menyimpan data user
        return $this->store($request);
    }
}
