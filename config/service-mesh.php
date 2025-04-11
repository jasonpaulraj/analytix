<?php

/**
 * Given a list of services and their dependencies, determine the order in which the services should be started.
 * @param array $services
 * @return array
 */

return [
    'services' => [
        ['Proxmox', 'https://proxmox.example.com/', 'https://www.proxmox.com/favicon.ico'],
        ['Uptime Kuma', 'https://uptime.example.com/', 'https://uptime.kuma.pet/img/icon.svg'],
        ['Grafana', 'https://grafana.example.com/', 'https://grafana.com/static/assets/img/fav32.png'],
        ['FileFlows', 'https://fileflows.example.com/', 'https://fileflows.com/img/favicon.ico'],
        ['Portainer', 'https://portainer.example.com/', 'https://www.portainer.io/hubfs/crane-icon.svg']
    ],
];
