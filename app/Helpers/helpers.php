<?php

if (!function_exists('loadJson')) {
    /**
     * Load JSON data from a file in the examples/json directory
     * 
     * @param string $filename The JSON file name to load
     * @return array The decoded JSON data or empty array if file doesn't exist
     */
    function loadJson($filename)
    {
        $jsonBasePath = base_path('examples/json');
        $path = $jsonBasePath . '/' . $filename;
        return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    }
}

if (!function_exists('proxmoxNodesMapping')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed 
     * @return void
     */
    function proxmoxNodesMapping($data)
    {
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
    }
}

if (!function_exists('proxmoxClusterMapping')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed
     * @return void
     */
    function proxmoxClusterMapping($data)
    {
        return [
            'nodes' => $data['nodes'] ?? 0,
            'vms' => $data['vms'] ?? 0,
            'storage' => $data['storage'] ?? 0,
            'total_cpu' => $data['total_cpu'] ?? 0,
            'total_memory' => $data['total_memory'] ? round($data['total_memory'] / 1024 / 1024 / 1024, 2) : 0,
            'total_disk' => $data['total_disk'] ? round($data['total_disk'] / 1024 / 1024 / 1024, 2) : 0
        ];
    }
}
