<?php

namespace App\Services\ServiceMesh;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class GrafanaService extends ServiceMeshBaseService
{
    public function getDashboards(): array
    {
        try {
            $response = $this->client->get($this->buildUrl('grafana/dashboards'));
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to get Grafana dashboards: ' . $e->getMessage());
            return [];
        }
    }

    // ... other Grafana methods ...
}
