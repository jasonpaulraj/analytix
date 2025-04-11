<?php

use App\Http\Controllers\ProxmoxController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Disabled auth routes for development
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Proxmox API Routes
Route::prefix('proxmox')->group(function () {
    // Cluster management
    Route::get('/clusters', [ProxmoxController::class, 'getClusters']);
    Route::post('/clusters', [ProxmoxController::class, 'addCluster']);
    Route::put('/clusters/{id}', [ProxmoxController::class, 'updateCluster']);
    Route::delete('/clusters/{id}', [ProxmoxController::class, 'deleteCluster']);
    Route::post('/clusters/{id}/test-connection', [ProxmoxController::class, 'testConnection']);
    
    // Node management
    Route::post('/clusters/{id}/sync-nodes', [ProxmoxController::class, 'syncNodes']);
    Route::put('/nodes/{id}', [ProxmoxController::class, 'updateNode']);
    
    // Resource retrieval
    Route::get('/clusters/{id}/resources', [ProxmoxController::class, 'getClusterResources']);
    Route::post('/clusters/{clusterId}/resources', [ProxmoxController::class, 'getResources']);
    Route::get('/clusters/{clusterId}/nodes/{nodeId}', [ProxmoxController::class, 'getNodeDetails']);
    Route::get('/clusters/{clusterId}/nodes/{nodeId}/vms', [ProxmoxController::class, 'getNodeVMs']);
    Route::get('/clusters/{clusterId}/nodes/{nodeId}/containers', [ProxmoxController::class, 'getNodeContainers']);
});
