<?php

namespace App\Http\Controllers;

use App\Services\ServiceMesh\ProxmoxService;
use App\Services\ServiceMesh\ServiceMeshBaseService;
use App\Services\ServiceMesh\UptimeKumaService;

class MasterController extends Controller
{
    protected $proxmoxService;

    public function __construct(ProxmoxService $proxmoxService)
    {
        $this->proxmoxService = $proxmoxService;
    }

    public function showDashboard()
    {
        $serviceMesh = new ServiceMeshBaseService();

        $data = [
            'skeleton' => 3,
            'services' => config('service-mesh.services'),
        ];

        if (config('app.offline_mode')) {
            $ServiceMeshHealthStatus = loadJson('service_mesh_healthcheck.json');
            $uptimeKumaMonitorStats = loadJson('service_mesh_uptime_kuma_monitor_statistics.json');
            $proxmoxNodes = loadJson('service_mesh_proxmox_nodes.json');
            $proxmoxCluster = loadJson('service_mesh_proxmox_cluster.json');

            $data = array_merge($data, [
                'ServiceMeshHealthStatus' => $ServiceMeshHealthStatus,
                'proxmoxNodes' => proxmoxNodesMapping($proxmoxNodes),
                'proxmoxCluster' => proxmoxClusterMapping($proxmoxCluster),
                'uptimeKumaMonitorStats' => $uptimeKumaMonitorStats,
                'uptimeKumaMonitors' => $uptimeKumaMonitorStats['monitors'] ?? []
            ]);
        }

        if (!config('app.offline_mode')) {
            $proxmoxService = new ProxmoxService();
            $uptimeKumaService = new UptimeKumaService();
            $uptimeKumaMonitorStats = $uptimeKumaService->getMonitorStatistics() ?? [];

            $data = array_merge($data, [
                'ServiceMeshHealthStatus' => $serviceMesh->checkApiHealth(),
                'proxmoxNodes' => $proxmoxService->getNodes(),
                'proxmoxCluster' => $proxmoxService->getCluster(),
                'uptimeKumaMonitorStats' => $uptimeKumaMonitorStats ?? [],
                'uptimeKumaMonitors' => $uptimeKumaMonitorStats['monitors'] ?? []
            ]);
        }

        return view('dashboard', $data);
    }
}
