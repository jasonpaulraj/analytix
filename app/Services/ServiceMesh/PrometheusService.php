<?php

namespace App\Services\ServiceMesh;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class PrometheusService extends ServiceMeshBaseService
{
    public function query(string $query): array
    {
        try {
            $response = $this->client->get($this->buildUrl('prometheus/query'), [
                'query' => ['query' => $query]
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to execute Prometheus query: ' . $e->getMessage());
            return [];
        }
    }

    // ... other Prometheus methods ...
}