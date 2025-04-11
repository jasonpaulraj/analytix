<?php

namespace App\Repositories;

use App\Models\ProxmoxCluster;
use App\Models\ProxmoxNode;
use Illuminate\Database\Eloquent\Collection;

class ProxmoxRepository
{
    /**
     * Get all Proxmox clusters with their nodes
     *
     * @return Collection
     */
    public function getAllClusters(): Collection
    {
        return ProxmoxCluster::with('nodes')->get();
    }

    /**
     * Get a specific cluster by ID with its nodes
     *
     * @param int $id
     * @return ProxmoxCluster|null
     */
    public function getClusterById(int $id): ?ProxmoxCluster
    {
        return ProxmoxCluster::with('nodes')->find($id);
    }

    /**
     * Create a new Proxmox cluster
     *
     * @param array $data
     * @return ProxmoxCluster
     */
    public function createCluster(array $data): ProxmoxCluster
    {
        return ProxmoxCluster::create($data);
    }

    /**
     * Update a Proxmox cluster
     *
     * @param int $id
     * @param array $data
     * @return ProxmoxCluster|null
     */
    public function updateCluster(int $id, array $data): ?ProxmoxCluster
    {
        $cluster = ProxmoxCluster::find($id);
        
        if ($cluster) {
            $cluster->update($data);
        }
        
        return $cluster;
    }

    /**
     * Delete a Proxmox cluster
     *
     * @param int $id
     * @return bool
     */
    public function deleteCluster(int $id): bool
    {
        $cluster = ProxmoxCluster::find($id);
        
        if ($cluster) {
            // Delete associated nodes first
            $cluster->nodes()->delete();
            
            // Delete the cluster
            return $cluster->delete();
        }
        
        return false;
    }

    /**
     * Get all nodes for a specific cluster
     *
     * @param int $clusterId
     * @return Collection
     */
    public function getNodesByCluster(int $clusterId): Collection
    {
        return ProxmoxNode::where('proxmox_cluster_id', $clusterId)->get();
    }

    /**
     * Get all nodes for a specific group in a cluster
     *
     * @param int $clusterId
     * @param string $group
     * @return Collection
     */
    public function getNodesByGroup(int $clusterId, string $group): Collection
    {
        return ProxmoxNode::where('proxmox_cluster_id', $clusterId)
            ->where('group', $group)
            ->get();
    }

    /**
     * Get a specific node by ID
     *
     * @param int $id
     * @return ProxmoxNode|null
     */
    public function getNodeById(int $id): ?ProxmoxNode
    {
        return ProxmoxNode::find($id);
    }

    /**
     * Get a specific node by node_id within a cluster
     *
     * @param int $clusterId
     * @param string $nodeId
     * @return ProxmoxNode|null
     */
    public function getNodeByNodeId(int $clusterId, string $nodeId): ?ProxmoxNode
    {
        return ProxmoxNode::where('proxmox_cluster_id', $clusterId)
            ->where('node_id', $nodeId)
            ->first();
    }

    /**
     * Update a Proxmox node
     *
     * @param int $id
     * @param array $data
     * @return ProxmoxNode|null
     */
    public function updateNode(int $id, array $data): ?ProxmoxNode
    {
        $node = ProxmoxNode::find($id);
        
        if ($node) {
            $node->update($data);
        }
        
        return $node;
    }

    /**
     * Get all unique node groups from a specific cluster
     *
     * @param int $clusterId
     * @return array
     */
    public function getNodeGroups(int $clusterId): array
    {
        return ProxmoxNode::where('proxmox_cluster_id', $clusterId)
            ->whereNotNull('group')
            ->distinct()
            ->pluck('group')
            ->toArray();
    }
}
