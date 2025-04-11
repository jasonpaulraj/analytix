<?php

namespace App\Services\ServiceMesh;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class ServiceMeshBaseService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer ' . env('SERVICE_MESH_API_KEY'),
                'Accept' => 'application/json',
            ],
        ]);
    }

    protected function isUrlReachable(string $url): bool
    {
        try {
            $response = $this->client->get($url, [
                'http_errors' => false,
                'verify' => false
            ]);
            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 400;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function buildUrl(string $endpoint = ''): string
    {
        $baseUrl = env('SERVICE_MESH_API_URL');

        // Try HTTPS first
        $httpsUrl = 'https://' . $baseUrl . '/' . ltrim($endpoint, '/');
        if ($this->isUrlReachable($httpsUrl)) {
            return $httpsUrl;
        }

        // Fallback to HTTP
        $httpUrl = 'http://' . $baseUrl . '/' . ltrim($endpoint, '/');
        if ($this->isUrlReachable($httpUrl)) {
            return $httpUrl;
        }

        Log::error('API endpoint is not reachable: ' . $baseUrl);
        return false;
    }
    /**
     * Check API health status
     *
     * @return array
     */
    public function checkApiHealth(): array
    {
        try {
            $response = $this->client->get($this->buildUrl('health'));
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('API health check failed: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to check API health: ' . $e->getMessage()
            ];
        }
    }
}
