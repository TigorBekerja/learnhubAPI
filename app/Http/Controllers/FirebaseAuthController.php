<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Routing\Controller;
class FirebaseAuthController extends Controller
{
    protected $auth;
    protected $firestore;

    public function __construct()
    {
        $firebase = Firebase::project(); // jika multi-project
        $this->auth = $firebase->auth();
        $this->firestore = $firebase->firestore();
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
            'nama' => 'required|string',
            'profile_picture' => 'nullable|url',
            'bio' => 'nullable|string'
        ]);

        try {
            // 1. Buat akun di Firebase Auth
            $createdUser = $this->auth->createUserWithEmailAndPassword($validated['email'], $validated['password']);

            $uid = $createdUser->uid;

            // 2. Simpan data tambahan di Firestore
            $this->firestore->database()
                ->collection('users')
                ->document($uid)
                ->set([
                    'user_id' => $uid,
                    'email' => $validated['email'],
                    'nama' => $validated['nama'],
                    'profile_picture' => $validated['profile_picture'] ?? '',
                    'bio' => $validated['bio'] ?? '',
                    'created_at' => now()->toDateTimeString(),
                ]);

            return response()->json(['message' => 'User registered successfully', 'user_id' => $uid], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
