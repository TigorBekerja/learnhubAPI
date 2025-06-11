<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;

class FirebaseTokenService
{
    protected ServiceAccountCredentials $credentials;

    public function __construct()
    {
        $jsonPath = storage_path('app/laravel-firestore-sa.json');

        $this->credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/datastore'],
            $jsonPath
        );
    }

    public function getAccessToken(): ?string
    {
        $token = $this->credentials->fetchAuthToken();
        return $token['access_token'] ?? null;
    }
}
