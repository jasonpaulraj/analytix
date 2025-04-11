<?php

namespace App\Services\ServiceMesh;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class UptimeKumaService extends ServiceMeshBaseService
{
    public function getMonitorStatistics(): array
    {
        try {
            $response = $this->client->get($this->buildUrl('uptime-kuma/monitors/statistics'));
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to get Uptime Kuma monitors statistics: ' . $e->getMessage());
            return [];
        }
    }
    public function getMonitors(): array
    {
        try {
            $response = $this->client->get($this->buildUrl('uptime-kuma/monitors'));
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to get Uptime Kuma monitors: ' . $e->getMessage());
            return [];
        }
    }

    public function createMonitor(array $data): array
    {
        try {
            $response = $this->client->post($this->buildUrl('uptime-kuma/monitors'), [
                'json' => $data
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to create Uptime Kuma monitor: ' . $e->getMessage());
            return [];
        }
    }

    public function getMonitor(string $monitorId): array
    {
        try {
            $response = $this->client->get($this->buildUrl("api/v1/uptime-kuma/monitors/{$monitorId}"));
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error("Failed to get Uptime Kuma monitor {$monitorId}: " . $e->getMessage());
            return [];
        }
    }

    public function updateMonitor(string $monitorId, array $data): array
    {
        try {
            $response = $this->client->patch($this->buildUrl("api/v1/uptime-kuma/monitors/{$monitorId}"), [
                'json' => $data
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error("Failed to update Uptime Kuma monitor {$monitorId}: " . $e->getMessage());
            return [];
        }
    }

    public function deleteMonitor(string $monitorId): bool
    {
        try {
            $this->client->delete($this->buildUrl("api/v1/uptime-kuma/monitors/{$monitorId}"));
            return true;
        } catch (RequestException $e) {
            Log::error("Failed to delete Uptime Kuma monitor {$monitorId}: " . $e->getMessage());
            return false;
        }
    }

    public function getStatusPages(): array
    {
        try {
            $response = $this->client->get($this->buildUrl('uptime-kuma/status-pages'));
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to get Uptime Kuma status pages: ' . $e->getMessage());
            return [];
        }
    }

    public function getStatusPage(string $pageId): array
    {
        try {
            $response = $this->client->get($this->buildUrl("api/v1/uptime-kuma/status-pages/{$pageId}"));
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error("Failed to get Uptime Kuma status page {$pageId}: " . $e->getMessage());
            return [];
        }
    }
}