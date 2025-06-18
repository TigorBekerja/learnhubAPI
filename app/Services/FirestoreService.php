<?php

namespace App\Services;

use GuzzleHttp\Client;

class FirestoreService
{
    protected string $collection;
    protected string $projectId;
    protected string $accessToken;
    protected Client $http;

    public function __construct(string $collection, FirebaseTokenService $tokenService)
    {
        $this->collection = $collection;
        $this->projectId = env('FIRESTORE_PROJECT_ID');
        $this->accessToken = $tokenService->getAccessToken();

        $this->http = new Client([
            'base_uri' => "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/",
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    protected function getAccessToken(): string
    {
        $jsonKeyPath = base_path('storage/app/laravel-firestore-sa.json');
        $jsonKey = json_decode(file_get_contents(filename: $jsonKeyPath), associative: true);

        // Pakai Google OAuth 2.0 JWT flow untuk dapat token (bisa pakai library Google Auth atau buat manual)
        // Untuk contoh singkat, pakai paket Google Client:
        $client = new \Google_Client();
        $client->setAuthConfig($jsonKeyPath);
        $client->addScope('https://www.googleapis.com/auth/datastore');
        $client->fetchAccessTokenWithAssertion();
        $token = $client->getAccessToken();

        return $token['access_token'] ?? '';
    }

    public function createDocument(array $data)
    {
        $formattedData = $this->formatFirestoreData($data);

        $response = $this->http->post("{$this->collection}", [
            'json' => ['fields' => $formattedData]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function formatFirestoreData(array $data): array
    {
        $formatted = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $formatted[$key] = ['stringValue' => $value];
            } elseif (is_int($value)) {
                $formatted[$key] = ['integerValue' => (string) $value];
            } elseif (is_bool($value)) {
                $formatted[$key] = ['booleanValue' => $value];
            } elseif ($value === null) {
                $formatted[$key] = ['nullValue' => null];
            }
            // bisa tambah tipe lain sesuai kebutuhan
        }
        return $formatted;
    }
    public function getAllDocuments(): array
    {
        $response = $this->http->get($this->collection);
        $data = json_decode($response->getBody()->getContents(), true);

        return $this->parseDocuments($data);
    }

    public function updateDocument(string $documentId, array $data): array
{
    $formattedData = $this->formatFirestoreData($data);

    $url = "{$this->collection}/{$documentId}";

    $response = $this->http->patch($url, [
        'headers' => [
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'fields' => $formattedData
        ],
    ]);
    return json_decode($response->getBody()->getContents(), true);
}
    private function formatFields(array $data): array
    {
        $fields = [];

        foreach ($data as $key => $value) {
            $fields[$key] = ['stringValue' => (string) $value];
        }

        return $fields;
    }

    private function parseDocuments(array $response): array
    {
        $documents = $response['documents'] ?? [];
        $result = [];

        foreach ($documents as $doc) {
            $fields = $doc['fields'] ?? [];
            $item = [];

            foreach ($fields as $key => $value) {
                // Ambil stringValue jika ada, sesuaikan jika ada tipe lain
                if (isset($value['stringValue'])) {
                    $item[$key] = $value['stringValue'];
                }
                // Bisa ditambah tipe lain seperti integerValue, booleanValue, dll jika perlu
            }

            $result[] = $item;
        }

        return $result;
    }


    // Contoh method get documents
    public function listDocuments()
    {
        $response = $this->http->get("{$this->collection}");
        return json_decode($response->getBody()->getContents(), true);
    }

    public function getDocuments(): array
    {
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$this->collection}";

        $response = $this->http->get($url, [
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $documents = [];

        if (isset($data['documents'])) {
            foreach ($data['documents'] as $doc) {
                $docData = ['id' => basename($doc['name'])];
                foreach ($doc['fields'] as $key => $value) {
                    $docData[$key] = $value[array_key_first($value)];
                }
                $documents[] = $docData;
            }
        }

        return $documents;
    }
    public function getDocumentById(string $collection, string $documentId): ?array
    {
        $url = "{$collection}/{$documentId}";

        try {
            $response = $this->http->get($url);
            $data = json_decode($response->getBody()->getContents(), true);

            return $data['fields'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
            public function deleteDocument(string $documentId): bool
    {
        $url = "{$this->collection}/{$documentId}";

        $response = $this->http->delete($url, [
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
            ],
        ]);

        return $response->getStatusCode() === 200;
    }



    public function setCollection(string $collection)
    {
        $this->collection = $collection;
    }

}
