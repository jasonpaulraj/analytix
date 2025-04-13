@extends('layouts.master')

@section('content')
<div class="container mx-auto px-4 py-8">
    <header class="mb-12 text-center">
        <div class="header-title">
            <h1 class="text-5xl font-bold mb-3 text-transparent bg-clip-text bg-gradient-to-r from-system-highlight to-blue-500">
                <i class="ri-server-line mr-3"></i>Analytix
            </h1>
        </div>
        <p class="text-gray-400 text-lg mt-4">
            <i class="ri-pulse-line mr-2"></i>System Monitoring Dashboard
        </p>
    </header>

    <!-- Docker Containers -->
    <section class="mb-12">
        <h2 class="section-header">
            <i class="ri-service-line system-icon"></i>Applications
        </h2>
        <div class="bg-system-secondary/50 rounded-xl xs:p-6 grid-background">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 llg:grid-cols-4 xxl:grid-cols-5 gap-6" id="docker-containers">
                @if(!empty($services))
                @foreach($services as $service)
                <div class="mb-5 metric-card bg-system-secondary/50 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-all duration-300">
                    <div class="flex-center text-center">
                        <div class="bg-system-secondary rounded-lg flex items-center justify-center">
                            <img src="{{ $service[2] }}" alt="{{$service[0]}}" class="w-12 h-12" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-300">{{$service[0]}}</h3>
                        <p class="mx-auto text-sm text-gray-400">
                            Virtualization Platform
                        </p>
                        <a href="{{ $service[1] }}" target="_blank" style="width: 6rem;margin: 1rem auto 0 auto;" class="mt-2 px-4 py-2 bg-system-highlight/20 hover:bg-system-highlight/40 text-blue-400 rounded-md transition-colors duration-300 flex items-center">
                            <span>Open</span>
                            <i class="ri-external-link-line ml-2"></i>
                        </a>
                    </div>
                </div>
                @endforeach
                @else
                <div class="metric-card animate-pulse">
                    <div class="flex items-center justify-between mb-2">
                        <div class="h-6 bg-gray-700 rounded w-3/4"></div>
                        <div class="status-indicator bg-gray-700"></div>
                    </div>
                    <div class="text-sm text-gray-400 space-y-2">
                        <div class="h-4 bg-gray-700 rounded w-1/2"></div>
                        <div class="h-4 bg-gray-700 rounded w-3/4"></div>
                        <div class="h-4 bg-gray-700 rounded w-2/3"></div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>

    <!-- Services Status -->
    <section class="mb-12">
        <h2 class="section-header"><i class="ri-settings-2-line system-icon"></i>Services Status</h2>
        <!-- Uptime Kuma -->
        <div class="bg-system-secondary/50 rounded-xl xs:p-6 grid-background">
            @if(!empty($uptimeKumaMonitorStats))
            @if(!empty($uptimeKumaMonitorStats['uptime_kuma_info']))
            @php($uptimeKumaInfo = $uptimeKumaMonitorStats['uptime_kuma_info'])
            <h3 class="text-2xl font-semibold mb-4 text-gray-300 border-b border-system-secondary pb-2 flex items-center">
                <i class="ri-pulse-line mr-2"></i>Uptime Kuma
            </h3>
            <div class="bg-system-secondary/50 rounded-xl xs:p-6 grid-background">
                <div class="p-4 bg-system-secondary/50 rounded-lg metric-card">
                    <div class="flex flex-wrap justify-between items-center mb-3">
                        <div class="flex items-center">
                            <i class="ri-information-line text-blue-400 mr-2 text-xl"></i>
                            <span class="text-gray-300 font-medium">Uptime Kuma v{{ $uptimeKumaInfo['version'] }}</span>
                            @if($uptimeKumaInfo['version'] !== $uptimeKumaInfo['latest_version'])
                            <span class="ml-2 text-xs px-2 py-0.5 bg-yellow-900/30 text-yellow-400 rounded-full">
                                Update available: v{{ $uptimeKumaInfo['latest_version'] }}
                            </span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400">
                            <span class="mr-3">{{ $uptimeKumaInfo['is_container'] ? 'Running in container' : 'Native installation' }}</span>
                            <span>{{ $uptimeKumaInfo['server_timezone'] }} ({{ $uptimeKumaInfo['server_timezone_offset'] }})</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
                        <div class="bg-system-secondary/50 rounded-lg p-2">
                            <div class="text-sm font-medium text-gray-300">Monitors</div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Total: {{ $uptimeKumaMonitorStats['monitors_count'] ?? 0 }}</span>
                                <span class="text-green-400">Up: {{ $uptimeKumaMonitorStats['up_monitors_count'] ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-red-400">Down: {{ $uptimeKumaMonitorStats['down_monitors_count'] ?? 0 }}</span>
                                <span class="text-yellow-400">Maintenance: {{ $uptimeKumaMonitorStats['maintenance_monitors_count'] ?? 0 }}</span>
                            </div>
                        </div>

                        <div class="bg-system-secondary/50 rounded-lg p-2">
                            <div class="text-sm font-medium text-gray-300">Performance</div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Avg Response:</span>
                                <span class="text-{{ isset($uptimeKumaMonitorStats['avg_response_time']) && $uptimeKumaMonitorStats['avg_response_time'] < 1000 ? 'green' : 'yellow' }}-400">
                                    {{ isset($uptimeKumaMonitorStats['avg_response_time']) ? number_format($uptimeKumaMonitorStats['avg_response_time']) : 0 }} ms
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Health Score:</span>
                                <span class="text-{{ isset($uptimeKumaMonitorStats['avg_health_score']) && $uptimeKumaMonitorStats['avg_health_score'] > 80 ? 'green' : 'yellow' }}-400">
                                    {{ isset($uptimeKumaMonitorStats['avg_health_score']) ? number_format($uptimeKumaMonitorStats['avg_health_score'], 1) : 0 }}/100
                                </span>
                            </div>
                        </div>

                        <div class="bg-system-secondary/50 rounded-lg p-2">
                            <div class="text-sm font-medium text-gray-300">Status</div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Uptime:</span>
                                <span class="text-{{ isset($uptimeKumaMonitorStats['up_percentage']) && $uptimeKumaMonitorStats['up_percentage'] > 99 ? 'green' : 'yellow' }}-400">
                                    {{ isset($uptimeKumaMonitorStats['up_percentage']) ? number_format($uptimeKumaMonitorStats['up_percentage'], 1) : 0 }}%
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Last Update:</span>
                                <span class="text-gray-400">
                                    {{ isset($uptimeKumaMonitorStats['timestamp']) ? \Carbon\Carbon::parse($uptimeKumaMonitorStats['timestamp'])->setTimezone('Asia/Singapore')->format('g:i A') : 'N/A' }}
                                </span>
                            </div>
                        </div>

                        <div class="bg-system-secondary/50 rounded-lg p-2">
                            <div class="text-sm font-medium text-gray-300">System</div>
                            <div class="flex items-center">
                                <i class="ri-database-2-line mr-1 text-blue-400"></i>
                                <span class="text-gray-400">Database: {{ $uptimeKumaInfo['database_size_gb'] }} GB</span>
                            </div>
                            <div class="flex items-center mt-1">
                                <i class="ri-time-line mr-1 text-blue-400"></i>
                                <span class="text-gray-400">Updated: {{ isset($uptimeKumaMonitorStats['timestamp']) ? \Carbon\Carbon::parse($uptimeKumaMonitorStats['timestamp'])->diffForHumans() : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @endif
            <div class="bg-system-secondary/50 rounded-xl xs:p-6 grid-background">
                @if(!empty($uptimeKumaMonitors))
                <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2 llg:grid-cols-2 xl:grid-cols-2 xxl:grid-cols-2 2xl:grid-cols-3 gap-6">
                    @foreach($uptimeKumaMonitors as $monitor)
                    <!-- Mobile view (below 600px) -->
                    <div class="metric-card xs:hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium truncate max-w-[70%]">{{ $monitor['name'] ?? 'Website' }}</span>
                            <div class="flex items-center">
                                @if(isset($monitor['uptime']))
                                <span class="text-xs mr-2 px-2 py-1 rounded-full {{ isset($monitor['uptime']) && isset($monitor['uptime']['24h_percent']) ? ($monitor['uptime']['24h_percent'] > 99 ? 'bg-green-900/30 text-green-400' : ($monitor['uptime']['24h_percent'] > 95 ? 'bg-yellow-900/30 text-yellow-400' : 'bg-red-900/30 text-red-400')) : 'bg-gray-900/30 text-gray-400' }}">
                                    {{ isset($monitor['uptime']) && isset($monitor['uptime']['24h_percent']) ? $monitor['uptime']['24h_percent'] : 'N/A' }}{{ isset($monitor['uptime']) && isset($monitor['uptime']['24h_percent']) ? '%' : '' }}
                                </span>
                                @endif
                                <span class="status-indicator {{ $monitor['status'] === 'up' || $monitor['status'] === 1 ? 'status-up' : ($monitor['maintenance'] ? 'status-warning' : 'status-down') }}"></span>
                            </div>
                        </div>

                        <!-- Enhanced mobile view with more information -->
                        <div class="text-sm text-gray-400 space-y-2">
                            <div class="flex justify-between">
                                <span>Type: {{ ucfirst($monitor['type']) }}</span>
                                <span>Method: {{ $monitor['method'] ?? 'GET' }}</span>
                            </div>

                            <div class="truncate">
                                URL: <a href="{{ $monitor['url'] }}" target="_blank" class="text-blue-400 hover:underline relative z-20">{{ $monitor['url'] ?? 'https://abc.com' }}</a>
                            </div>

                            @if(isset($monitor['description']) && $monitor['description'] !== '-')
                            <div class="truncate">Description: {{ $monitor['description'] }}</div>
                            @endif

                            <div class="flex justify-between">
                                <span>Interval: {{ $monitor['interval'] }}s</span>
                                <span>Timeout: {{ $monitor['timeout'] ?? 'N/A' }}s</span>
                            </div>

                            <div class="flex justify-between">
                                @if(isset($monitor['avg_ping']))
                                <span>Ping: <span class="text-{{ $monitor['avg_ping'] < 50 ? 'green' : ($monitor['avg_ping'] <= 300 ? 'yellow' : 'red') }}-400">{{ $monitor['avg_ping'] }}ms</span></span>
                                @endif

                                @if(isset($monitor['ping_quality']))
                                <span>Quality: <span class="text-{{ $monitor['ping_quality'] === 'good' ? 'green' : ($monitor['ping_quality'] === 'fair' ? 'yellow' : 'red') }}-400">
                                        {{ ucfirst($monitor['ping_quality']) }}
                                    </span></span>
                                @endif
                            </div>

                            @if(isset($monitor['health_score']))
                            <div class="flex justify-between">
                                <span>Health Score:</span>
                                <span class="text-{{ $monitor['health_score'] > 80 ? 'green' : ($monitor['health_score'] > 60 ? 'yellow' : 'red') }}-400">
                                    {{ isset($monitor['health_score']) && is_numeric($monitor['health_score']) ? number_format($monitor['health_score'], 1) : 'N/A' }}/100
                                </span>
                            </div>
                            @endif

                            @if(isset($monitor['uptime']))
                            <div class="mt-2">
                                <div class="w-full bg-gray-700 rounded-full h-1.5">
                                    <div class="bg-gradient-to-r from-green-500 to-blue-500 h-1.5 rounded-full progress-bar" data-width="{{ isset($monitor['uptime']['24h_percent']) && !empty($monitor['uptime']['24h_percent']) ? $monitor['uptime']['24h_percent'] : 0 }}"></div>
                                </div>
                                <div class="flex justify-between text-xs mt-1">
                                    <span>24h: {{ isset($monitor['uptime']['24h_percent']) && !empty($monitor['uptime']['24h_percent']) ? $monitor['uptime']['24h_percent'] : 'N/A' }}%</span>
                                    <span>30d: {{ isset($monitor['uptime']['720h_percent']) && !empty($monitor['uptime']['720h_percent']) ? $monitor['uptime']['720h_percent'] : 'N/A' }}%</span>
                                </div>
                            </div>
                            @endif

                            @if(isset($monitor['cert_info']))
                            <div class="mt-2 bg-system-secondary/70 rounded-md p-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-300">SSL Certificate</span>
                                    <span class="text-xs px-1.5 py-0.5 rounded-full {{ isset($monitor['cert_info']['valid']) ? ($monitor['cert_info']['valid'] ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400') : 'bg-gray-900/30 text-gray-400' }}">
                                        {{ isset($monitor['cert_info']['valid']) ? ($monitor['cert_info']['valid'] ? 'Valid' : 'Invalid') : 'N/A' }}
                                    </span>
                                </div>
                                <div class="text-xs">
                                    <div>CN: {{ isset($monitor['cert_info']['subject_cn']) && $monitor['cert_info']['subject_cn'] ?? 'abc.com' }}</div>
                                    @if(isset($monitor['cert_info']['days_remaining']) && $monitor['cert_info']['days_remaining'] !== '-')
                                    <div>Expires in: {{ $monitor['cert_info']['days_remaining'] }} days</div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            @if(isset($monitor['last_status_change']) && $monitor['last_status_change'] !== '-')
                            <div class="text-xs">Last Check: {{ \Carbon\Carbon::parse($monitor['last_status_change'])->diffForHumans() }}</div>
                            @endif

                            <!-- Re-added Monitor Logs for mobile -->
                            @if(isset($monitor['logs']) && count($monitor['logs']) > 0)
                            <div class="mt-3 relative">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-300">Recent Activity</span>
                                    <span class="text-xs text-gray-500">{{ count($monitor['logs']) }} entries</span>
                                </div>
                                <div class="custom-scrollbar-container bg-system-secondary/70 rounded-md p-1 relative z-10">
                                    <div class="custom-scrollbar" style="max-height: 120px; overflow-y: auto;">
                                        <table class="w-full text-xs">
                                            <tbody>
                                                @foreach(array_slice($monitor['logs'], 0, 5) as $log)
                                                <tr class="border-b border-gray-800/30 last:border-0">
                                                    <td class="py-1 px-1 whitespace-nowrap">
                                                        <span class="text-gray-400 text-[10px]">{{ \Carbon\Carbon::parse($log['time'])->setTimezone('Asia/Singapore')->format('d M, g:i A') }}</span>
                                                    </td>
                                                    <td class="py-1 px-1">
                                                        <span class="px-1 py-0.5 rounded-full text-[10px] {{ $log['status'] === 'UP' ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400' }}">
                                                            {{ $log['status'] }}
                                                        </span>
                                                    </td>
                                                    <td class="py-1 px-1 text-gray-400 truncate max-w-[120px]" title="{{ $log['message'] }}">
                                                        {{ $log['message'] }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Desktop view remains unchanged -->
                    <div class="metric-card hidden xs:block">
                        <!-- Existing desktop view code remains the same -->
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium">{{ $monitor['name'] ?? 'Website'  }}</span>
                            <div class="flex items-center">
                                @if(isset($monitor['uptime']))
                                <span class="text-xs mr-2 px-2 py-1 rounded-full {{ isset($monitor['uptime']) && isset($monitor['uptime']['24h_percent']) ? ($monitor['uptime']['24h_percent'] > 99 ? 'bg-green-900/30 text-green-400' : ($monitor['uptime']['24h_percent'] > 95 ? 'bg-yellow-900/30 text-yellow-400' : 'bg-red-900/30 text-red-400')) : 'bg-gray-900/30 text-gray-400' }}">
                                    {{ isset($monitor['uptime']) && isset($monitor['uptime']['24h_percent']) ? $monitor['uptime']['24h_percent'] : 'N/A' }}{{ isset($monitor['uptime']) && isset($monitor['uptime']['24h_percent']) ? '%' : '' }}
                                </span>
                                @endif
                                <span class="status-indicator {{ $monitor['status'] === 'up' || $monitor['status'] === 1 ? 'status-up' : ($monitor['maintenance'] ? 'status-warning' : 'status-down') }}"></span>
                            </div>
                        </div>
                        <div class="text-sm text-gray-400 space-y-1">
                            <div class="flex justify-between">
                                <span>Type: {{ ucfirst($monitor['type']) }}</span>
                                <!-- <span>ID: {{ $monitor['id'] ?? 'N/A' }}</span> -->
                            </div>
                            <div>URL: <a href="{{ $monitor['url'] }}" target="_blank" class="text-blue-400 hover:underline relative z-20">{{ $monitor['url'] ?? 'https://abc.com' }}</a></div>

                            @if(isset($monitor['description']) && $monitor['description'] !== '-')
                            <div>Description: {{ $monitor['description'] }}</div>
                            @endif

                            <div class="flex justify-between">
                                <span>Method: {{ $monitor['method'] ?? 'GET' }}</span>
                                <span>Timeout: {{ $monitor['timeout'] ?? 'N/A' }}s</span>
                            </div>

                            <div class="flex justify-between">
                                <span>Interval: {{ $monitor['interval'] }} seconds</span>
                                @if(isset($monitor['avg_ping']))
                                <span class="text-{{ $monitor['avg_ping'] < 50 ? 'green' : ($monitor['avg_ping'] <= 300 ? 'yellow' : 'red') }}-400">{{ $monitor['avg_ping'] }}ms</span>
                                @endif
                            </div>

                            @if(isset($monitor['ping_quality']))
                            <div class="flex justify-between">
                                <span>Ping Quality:</span>
                                <span class="text-{{ $monitor['ping_quality'] === 'good' ? 'green' : ($monitor['ping_quality'] === 'fair' ? 'yellow' : 'red') }}-400">
                                    {{ ucfirst($monitor['ping_quality']) }}
                                </span>
                            </div>
                            @endif

                            @if(isset($monitor['health_score']))
                            <div class="flex justify-between">
                                <span>Health Score:</span>
                                <span class="text-{{ $monitor['health_score'] > 80 ? 'green' : ($monitor['health_score'] > 60 ? 'yellow' : 'red') }}-400">
                                    {{ isset($monitor['health_score']) && is_numeric($monitor['health_score']) ? number_format($monitor['health_score'], 1) : 'N/A' }}/100
                                </span>
                            </div>
                            @endif

                            @if(isset($monitor['uptime']))
                            <div class="mt-2">
                                <div class="w-full bg-gray-700 rounded-full h-1.5">
                                    <div class="bg-gradient-to-r from-green-500 to-blue-500 h-1.5 rounded-full progress-bar" data-width="{{ isset($monitor['uptime']['24h_percent']) && !empty($monitor['uptime']['24h_percent']) ? $monitor['uptime']['24h_percent'] : 0 }}"></div>
                                </div>
                                <div class="flex justify-between text-xs mt-1">
                                    <span>24h: {{ isset($monitor['uptime']['24h_percent']) && !empty($monitor['uptime']['24h_percent']) ? $monitor['uptime']['24h_percent'] : 'N/A' }}% <span class="text-{{ isset($monitor['uptime']['24h_quality']) && !empty($monitor['uptime']['24h_quality']) ? ($monitor['uptime']['24h_quality'] === 'good' || $monitor['uptime']['24h_quality'] === 'excellent' ? 'green' : ($monitor['uptime']['24h_quality'] === 'poor' ? 'red' : 'yellow')) : 'gray' }}-400">({{ isset($monitor['uptime']['24h_quality']) && !empty($monitor['uptime']['24h_quality']) ? ucfirst($monitor['uptime']['24h_quality']) : 'N/A' }})</span></span>
                                    <span>30d: {{ isset($monitor['uptime']['720h_percent']) && !empty($monitor['uptime']['720h_percent']) ? $monitor['uptime']['720h_percent'] : 'N/A' }}% <span class="text-{{ isset($monitor['uptime']['720h_quality']) && !empty($monitor['uptime']['720h_quality']) ? ($monitor['uptime']['720h_quality'] === 'good' || $monitor['uptime']['720h_quality'] === 'excellent' ? 'green' : ($monitor['uptime']['720h_quality'] === 'poor' ? 'red' : 'yellow')) : 'gray' }}-400">({{ isset($monitor['uptime']['720h_quality']) && !empty($monitor['uptime']['720h_quality']) ? ucfirst($monitor['uptime']['720h_quality']) : 'N/A' }})</span></span>
                                </div>
                            </div>
                            @endif

                            @if(isset($monitor['cert_info']))
                            <div class="mt-2 bg-system-secondary/70 rounded-md">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-300">SSL Certificate</span>
                                    <span class="text-xs px-1.5 py-0.5 rounded-full {{ isset($monitor['cert_info']['valid']) ? ($monitor['cert_info']['valid'] ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400') : 'bg-gray-900/30 text-gray-400' }}">
                                        {{ isset($monitor['cert_info']['valid']) ? ($monitor['cert_info']['valid'] ? 'Valid' : 'Invalid') : 'N/A' }}
                                    </span>
                                </div>
                                <div class="text-xs">
                                    <div>CN: {{ isset($monitor['cert_info']['subject_cn']) && $monitor['cert_info']['subject_cn'] ?? 'abc.com' }}</div>
                                    <div>Issuer: {{ $monitor['cert_info']['issuer_cn'] ?? 'N/A' }}</div>
                                    @if(isset($monitor['cert_info']['days_remaining']) && $monitor['cert_info']['days_remaining'] !== '-')
                                    <div>Expires in: {{ $monitor['cert_info']['days_remaining'] }} days</div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            @if(isset($monitor['last_status_change']) && $monitor['last_status_change'] !== '-')
                            <div class="mt-1">Last Check: {{ \Carbon\Carbon::parse($monitor['last_status_change'])->diffForHumans() }}</div>
                            @endif

                            <!-- Monitor Logs -->
                            @if(isset($monitor['logs']) && count($monitor['logs']) > 0)
                            <div class="mt-3 relative">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-300">Recent Activity</span>
                                    <span class="text-xs text-gray-500">{{ count($monitor['logs']) }} entries</span>
                                </div>
                                <div class="custom-scrollbar-container bg-system-secondary/70 rounded-md p-1 relative z-10">
                                    <div class="custom-scrollbar" style="min-height: 100px;">
                                        <table class="w-full text-xs">
                                            <tbody>
                                                @foreach($monitor['logs'] as $log)
                                                <tr class="border-b border-gray-800/30 last:border-0">
                                                    <td class="py-1 px-2 whitespace-nowrap">
                                                        <span class="text-gray-400">{{ \Carbon\Carbon::parse($log['time'])->setTimezone('Asia/Singapore')->format('d M Y, g:i A') }}</span>
                                                    </td>
                                                    <td class="py-1 px-2">
                                                        <span class="px-1.5 py-0.5 rounded-full text-xs {{ $log['status'] === 'UP' ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400' }}">
                                                            {{ $log['status'] }}
                                                        </span>
                                                    </td>
                                                    <td class="py-1 px-2 text-gray-400 truncate max-w-[150px]" title="{{ $log['message'] }}">
                                                        {{ $log['message'] }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-gray-400">No monitors available</div>
                @endif
            </div>
        </div>
    </section>

    <!-- Proxmox Status -->
    <section class="mb-12">
        <h2 class="section-header">
            <i class="ri-server-line system-icon"></i>Proxmox Nodes
        </h2>
        <div class="bg-system-secondary/50 rounded-xl p-4 xs:p-6 grid-background">
            <!-- Cluster Overview Card -->
            <div class="mb-6 bg-system-secondary/70 rounded-lg overflow-hidden">
                <div class="border-b border-system-secondary">
                    <div class="p-4 flex items-center">
                        <i class="ri-cloud-line text-blue-400 mr-2 text-xl"></i>
                        <h3 class="text-xl font-semibold text-gray-300">Cluster Overview</h3>
                    </div>
                </div>

                @if(!empty($proxmoxCluster))
                <div class="p-4">
                    <div class="metric-card grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="text-sm font-medium text-gray-300">Nodes</div>
                            <div class="text-xl font-semibold text-blue-400">{{ $proxmoxCluster['nodes'] }}</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="text-sm font-medium text-gray-300">VMs</div>
                            <div class="text-xl font-semibold text-blue-400">{{ $proxmoxCluster['vms'] }}</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="text-sm font-medium text-gray-300">Storage Pools</div>
                            <div class="text-xl font-semibold text-blue-400">{{ $proxmoxCluster['storage'] }}</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="text-sm font-medium text-gray-300">Total CPU</div>
                            <div class="text-xl font-semibold text-blue-400">{{ $proxmoxCluster['total_cpu'] }}</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="text-sm font-medium text-gray-300">Total Memory</div>
                            <div class="text-xl font-semibold text-blue-400">{{ $proxmoxCluster['total_memory'] }} GB</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="text-sm font-medium text-gray-300">Total Disk</div>
                            <div class="text-xl font-semibold text-blue-400">{{ $proxmoxCluster['total_disk'] }} GB</div>
                        </div>
                    </div>
                </div>
                @else
                <div class="p-4">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="text-sm font-medium text-gray-300">Nodes</div>
                            <div class="text-xl font-semibold text-gray-500">N/A</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="text-sm font-medium text-gray-300">VMs</div>
                            <div class="text-xl font-semibold text-gray-500">N/A</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="text-sm font-medium text-gray-300">Storage Pools</div>
                            <div class="text-xl font-semibold text-gray-500">N/A</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="text-sm font-medium text-gray-300">Total CPU</div>
                            <div class="text-xl font-semibold text-gray-500">N/A</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="text-sm font-medium text-gray-300">Total Memory</div>
                            <div class="text-xl font-semibold text-gray-500">N/A</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="text-sm font-medium text-gray-300">Total Disk</div>
                            <div class="text-xl font-semibold text-gray-500">N/A</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- System Overview Card -->
            <div class="mb-6 bg-system-secondary/70 rounded-lg overflow-hidden">
                <div class="border-b border-system-secondary">
                    <div class="p-4 flex items-center">
                        <i class="ri-dashboard-line text-blue-400 mr-2 text-xl"></i>
                        <h3 class="text-xl font-semibold text-gray-300">System Overview</h3>
                    </div>
                </div>
                <div class="p-4">
                    <div class="metric-card grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="flex items-center mb-2">
                                <i class="ri-cpu-line text-blue-400 mr-2 text-xl"></i>
                                <div class="text-sm font-medium text-gray-300">CPU Usage</div>
                            </div>
                            <div class="text-xl font-semibold text-blue-400" id="cpu-usage">0%</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="flex items-center mb-2">
                                <i class="ri-ram-line text-blue-400 mr-2 text-xl"></i>
                                <div class="text-sm font-medium text-gray-300">RAM Usage</div>
                            </div>
                            <div class="text-xl font-semibold text-blue-400" id="ram-usage">0GB / 0GB</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="flex items-center mb-2">
                                <i class="ri-hard-drive-line text-blue-400 mr-2 text-xl"></i>
                                <div class="text-sm font-medium text-gray-300">Storage</div>
                            </div>
                            <div class="text-xl font-semibold text-blue-400" id="storage-usage">0TB / 0TB</div>
                        </div>
                        <div class="bg-system-secondary/50 rounded-lg p-3">
                            <div class="flex items-center mb-2">
                                <i class="ri-speed-up-line text-blue-400 mr-2 text-xl"></i>
                                <div class="text-sm font-medium text-gray-300">Network Speed</div>
                            </div>
                            <div class="text-xl font-semibold text-blue-400" id="network-speed">0 Mbps</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Node Details Card -->
            <div class="bg-system-secondary/70 rounded-lg overflow-hidden">
                <div class="border-b border-system-secondary">
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="ri-server-fill text-blue-400 mr-2 text-xl"></i>
                            <h3 class="text-xl font-semibold text-gray-300">Nodes</h3>
                        </div>
                        @if(!empty($proxmoxNodes))
                        <span class="text-xs text-gray-400 px-2 py-1 bg-system-secondary/50 rounded-full">
                            {{ count($proxmoxNodes) }} nodes
                        </span>
                        @endif
                    </div>
                </div>
                <!-- Proxmox Node Details -->
                <div class="p-4">
                    @if(!empty($proxmoxNodes))
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($proxmoxNodes as $node)
                        <div class=" metric-card bg-system-secondary/50 rounded-lg p-4 border border-system-secondary/30 hover:border-blue-500/30 transition-all duration-300">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <i class="ri-server-line text-blue-400 mr-2 text-xl"></i>
                                    <span class="text-gray-300 font-medium">{{ $node['node'] }}</span>
                                </div>
                                <span class="status-indicator {{ $node['status'] === 'online' ? 'status-up' : 'status-down' }}"></span>
                            </div>

                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400 text-sm">Status:</span>
                                    <span class="text-{{ $node['status'] === 'online' ? 'green' : 'red' }}-400 text-sm font-medium px-2 py-0.5 rounded-full bg-{{ $node['status'] === 'online' ? 'green' : 'red' }}-900/20">
                                        {{ ucfirst($node['status']) }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400 text-sm">CPU:</span>
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-700 rounded-full h-1.5 mr-2">
                                            <div class="bg-{{ $node['cpu'] < 50 ? 'green' : ($node['cpu'] < 80 ? 'yellow' : 'red') }}-500 h-1.5 rounded-full progress-bar" data-width="{{ $node['cpu'] }}"></div>
                                        </div>
                                        <span class="text-{{ $node['cpu'] < 50 ? 'green' : ($node['cpu'] < 80 ? 'yellow' : 'red') }}-400 text-sm">{{ $node['cpu'] }}%</span>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400 text-sm">Memory:</span>
                                    <span class="text-blue-400 text-sm">{{ $node['memory'] }} GB</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400 text-sm">IP Address:</span>
                                    <span class="text-gray-300 text-sm font-mono">{{ $node['ip'] }}</span>
                                </div>

                                <div class="pt-2 border-t border-system-secondary/30">
                                    <div class="flex items-center text-gray-400 text-sm">
                                        <i class="ri-time-line mr-1 text-blue-400"></i>
                                        <span>Uptime: {{ $node['uptime'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @for($i = 0; $i < 3; $i++) <div class="bg-system-secondary/50 rounded-lg p-4 border border-system-secondary/30 animate-pulse">
                            <div class="flex items-center justify-between mb-3">
                                <div class="h-6 bg-gray-700 rounded w-3/4"></div>
                                <div class="status-indicator bg-gray-700"></div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <div class="h-4 bg-gray-700 rounded w-1/4"></div>
                                    <div class="h-4 bg-gray-700 rounded w-1/4"></div>
                                </div>

                                <div class="flex justify-between items-center">
                                    <div class="h-4 bg-gray-700 rounded w-1/4"></div>
                                    <div class="h-4 bg-gray-700 rounded w-1/3"></div>
                                </div>

                                <div class="flex justify-between items-center">
                                    <div class="h-4 bg-gray-700 rounded w-1/4"></div>
                                    <div class="h-4 bg-gray-700 rounded w-1/3"></div>
                                </div>

                                <div class="pt-2 border-t border-system-secondary/30">
                                    <div class="h-4 bg-gray-700 rounded w-1/2"></div>
                                </div>
                            </div>
                    </div>
                    @endfor
                </div>
                @endif
            </div>
        </div>
</div>
</section>

<!-- ServiceMesh -->
<section class="mb-12">
    <h2 class="section-header">
        <i class="ri-heart-pulse-line system-icon"></i>ServiceMesh
    </h2>
    <div class="bg-system-secondary/50 rounded-xl xs:p-6 grid-background">
        @if(isset($ServiceMeshHealthStatus['status']) && $ServiceMeshHealthStatus['status'] !== 'error')
        <h3 class="text-2xl font-semibold mb-4 text-gray-300 border-b border-system-secondary pb-2 flex items-center">
            <i class="ri-health-book-line mr-2"></i>Health Status
        </h3>
        <div class="mb-6 p-4 bg-system-secondary/70 rounded-lg metric-card">
            <div class="flex flex-wrap justify-between items-center mb-3">
                <div class="flex items-center">
                    <span class="status-indicator {{ $ServiceMeshHealthStatus['status'] === 'healthy' ? 'status-up' : 'status-down' }} mr-2"></span>
                    <span class="text-gray-300 font-medium">Overall Status: {{ ucfirst($ServiceMeshHealthStatus['status']) }}</span>
                </div>
                <div class="text-xs text-gray-400">
                    Last checked: {{ $ServiceMeshHealthStatus['timestamp'] ? \Carbon\Carbon::parse($ServiceMeshHealthStatus['timestamp'])->setTimezone('Asia/Singapore')->format('M j, Y g:i A') : 'N/A' }}
                </div>
            </div>
        </div>

        @if(isset($ServiceMeshHealthStatus['services']))
        <h3 class="text-2xl font-semibold mb-4 text-gray-300 border-b border-system-secondary pb-2 flex items-center">
            <i class="ri-service-line mr-2"></i>Services Status
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($ServiceMeshHealthStatus['services'] as $serviceName => $service)
            <div class="p-4 bg-system-secondary/70 rounded-lg metric-card">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <i class="ri-settings-line text-blue-400 mr-2"></i>
                        <span class="text-gray-300 font-medium capitalize">{{ $serviceName }}</span>
                    </div>
                    <span class="status-indicator {{ $service['status'] === 'healthy' ? 'status-up' : ($service['status'] === 'unknown' ? 'status-warning' : 'status-down') }}"></span>
                </div>
                <div class="grid grid-cols-1 gap-3">
                    <div class="bg-system-secondary/50 rounded-lg">
                        <div class="text-sm font-medium text-gray-300">Status</div>
                        <div class="text-{{ $service['status'] === 'healthy' ? 'green' : ($service['status'] === 'unknown' ? 'yellow' : 'red') }}-400">{{ ucfirst($service['status']) }}</div>
                    </div>
                    @if($service['message'])
                    <div class="bg-system-secondary/50 rounded-lg">
                        <div class="text-sm font-medium text-gray-300">Message</div>
                        <div class="text-gray-400">{{ $service['message'] }}</div>
                    </div>
                    @endif
                    <div class="bg-system-secondary/50 rounded-lg">
                        <div class="text-sm font-medium text-gray-300">Enabled</div>
                        <div class="text-{{ $service['enabled'] === 'true' ? 'green' : 'yellow' }}-400">{{ $service['enabled'] === 'true' ? 'Yes' : 'No' }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="p-4 bg-system-secondary/70 rounded-lg metric-card">
            <div class="text-yellow-400">No service data available</div>
        </div>
        @endif
        @endif

        @if(isset($ServiceMeshHealthStatus['status']) && $ServiceMeshHealthStatus['status'] === 'error')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @for($i = 0; $i < $skeleton; $i++) <div class="p-4 bg-system-secondary/70 rounded-lg metric-card animate-pulse">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-6 bg-gray-700 rounded w-3/4"></div>
                    <div class="status-indicator bg-gray-700"></div>
                </div>
                <div class="space-y-3">
                    <div class="bg-system-secondary/50 rounded-lg">
                        <div class="h-4 bg-gray-700 rounded w-1/2"></div>
                        <div class="h-4 bg-gray-700 rounded w-3/4 mt-2"></div>
                    </div>
                    <div class="bg-system-secondary/50 rounded-lg">
                        <div class="h-4 bg-gray-700 rounded w-1/2"></div>
                        <div class="h-4 bg-gray-700 rounded w-3/4 mt-2"></div>
                    </div>
                    <div class="bg-system-secondary/50 rounded-lg">
                        <div class="h-4 bg-gray-700 rounded w-1/2"></div>
                        <div class="h-4 bg-gray-700 rounded w-3/4 mt-2"></div>
                    </div>
                </div>
        </div>
        @endfor
    </div>
    @endif
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle progress bars
        document.querySelectorAll('.progress-bar').forEach(function(bar) {
            const width = bar.getAttribute('data-width');
            bar.style.width = width + '%';
        });

        // Handle CPU progress bars
        document.querySelectorAll('[data-width]').forEach(function(bar) {
            const width = bar.getAttribute('data-width');
            bar.style.width = width + '%';
            bar.classList.add('transition-all', 'duration-500');
        });
    });

</script>
@endsection
