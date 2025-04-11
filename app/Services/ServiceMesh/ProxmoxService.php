<?php

namespace App\Services\ServiceMesh;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class ProxmoxService extends ServiceMeshBaseService
{
    /**
     * Get all Proxmox nodes
     *
     * @return array
     */
    public function getNodes(): array
    {
        try {
            $response = $this->client->get($this->buildUrl('proxmox/nodes'));
            $data = json_decode($response->getBody(), true);

            // Process and format the data
            return array_map(function ($node) {
                return [
                    'node' => $node['node'],
                    'status' => $node['status'],
                    'cpu' => $node['cpu'] ? round($node['cpu'] * 100, 2) : 0,
                    'memory' => $node['memory'] ? round($node['memory'] / 1024 / 1024 / 1024, 2) : 0,
                    'uptime' => $node['uptime'] ? gmdate('H\h i\m s\s', $node['uptime']) : 'N/A',
                    'ip' => $node['ip'] ?? 'N/A'
                ];
            }, $data['nodes'] ?? []);
        } catch (RequestException $e) {
            Log::error('Failed to get Proxmox nodes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get Proxmox cluster information
     *
     * @return array
     */
    public function getCluster(): array
    {
        try {
            $response = $this->client->get($this->buildUrl('proxmox/cluster'));
            $data = json_decode($response->getBody(), true);

            // Process and format the data
            return [
                'nodes' => $data['nodes'] ?? 0,
                'vms' => $data['vms'] ?? 0,
                'storage' => $data['storage'] ?? 0,
                'total_cpu' => $data['total_cpu'] ?? 0,
                'total_memory' => $data['total_memory'] ? round($data['total_memory'] / 1024 / 1024 / 1024, 2) : 0,
                'total_disk' => $data['total_disk'] ? round($data['total_disk'] / 1024 / 1024 / 1024, 2) : 0
            ];
        } catch (RequestException $e) {
            Log::error('Failed to get Proxmox cluster: ' . $e->getMessage());
            return [];
        }
    }

    public function getNodeDetails(string $node): array
    {
        try {
            $response = $this->client->get($this->buildUrl("api/v1/proxmox/nodes/{$node}"));
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error("Failed to get Proxmox node details: {$e->getMessage()}");
            return [];
        }
    }

    public function getVMs(): array
    {
        try {
            $response = $this->client->get($this->buildUrl('proxmox/vms'));
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to get Proxmox VMs: ' . $e->getMessage());
            return [];
        }
    }
}
