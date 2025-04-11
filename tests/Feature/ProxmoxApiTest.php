<?php

namespace Tests\Feature;

use App\Models\ProxmoxCluster;
use App\Models\ProxmoxNode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;
use App\Services\ProxmoxService;

class ProxmoxApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test getting all clusters.
     *
     * @return void
     */
    public function testGetClusters()
    {
        // Create test clusters
        ProxmoxCluster::factory()->count(3)->create();

        $response = $this->getJson('/api/proxmox/clusters');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data'
                 ])
                 ->assertJson(['success' => true]);
        
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test adding a new cluster.
     *
     * @return void
     */
    public function testAddCluster()
    {
        // Mock ProxmoxService for testing
        $mockService = Mockery::mock(ProxmoxService::class);
        $mockService->shouldReceive('testConnection')
                   ->once()
                   ->andReturn(['success' => true, 'message' => 'Connected successfully']);
        
        $mockService->shouldReceive('syncNodes')
                   ->once()
                   ->andReturn(['success' => true]);
        
        $this->app->instance(ProxmoxService::class, $mockService);
        
        $clusterData = [
            'name' => 'Test Cluster',
            'api_host' => '192.168.1.100',
            'api_port' => 8006,
            'username' => 'root@pam',
            'token_name' => 'test',
            'token_value' => 'abc123',
            'verify_ssl' => false,
        ];

        $response = $this->postJson('/api/proxmox/clusters', $clusterData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Proxmox cluster added successfully'
                 ]);
        
        $this->assertDatabaseHas('proxmox_clusters', [
            'name' => 'Test Cluster',
            'api_host' => '192.168.1.100',
        ]);
    }

    /**
     * Test updating a cluster.
     *
     * @return void
     */
    public function testUpdateCluster()
    {
        // Create a test cluster
        $cluster = ProxmoxCluster::factory()->create();
        
        // Mock ProxmoxService for testing
        $mockService = Mockery::mock(ProxmoxService::class);
        $mockService->shouldReceive('testConnection')
                   ->once()
                   ->andReturn(['success' => true, 'message' => 'Connected successfully']);
        
        $mockService->shouldReceive('syncNodes')
                   ->once()
                   ->andReturn(['success' => true]);
        
        $this->app->instance(ProxmoxService::class, $mockService);
        
        $updateData = [
            'name' => 'Updated Cluster Name',
            'api_host' => $cluster->api_host,
            'api_port' => $cluster->api_port,
            'username' => $cluster->username,
            'token_name' => $cluster->token_name,
            'token_value' => $cluster->token_value,
            'verify_ssl' => $cluster->verify_ssl,
        ];

        $response = $this->putJson("/api/proxmox/clusters/{$cluster->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Proxmox cluster updated successfully'
                 ]);
        
        $this->assertDatabaseHas('proxmox_clusters', [
            'id' => $cluster->id,
            'name' => 'Updated Cluster Name',
        ]);
    }

    /**
     * Test deleting a cluster.
     *
     * @return void
     */
    public function testDeleteCluster()
    {
        // Create a test cluster
        $cluster = ProxmoxCluster::factory()->create();
        
        // Create some nodes for this cluster
        ProxmoxNode::factory()->count(3)->create(['proxmox_cluster_id' => $cluster->id]);

        $response = $this->deleteJson("/api/proxmox/clusters/{$cluster->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Proxmox cluster deleted successfully'
                 ]);
        
        $this->assertDatabaseMissing('proxmox_clusters', ['id' => $cluster->id]);
        $this->assertDatabaseMissing('proxmox_nodes', ['proxmox_cluster_id' => $cluster->id]);
    }

    /**
     * Test syncing nodes for a cluster.
     *
     * @return void
     */
    public function testSyncNodes()
    {
        // Create a test cluster
        $cluster = ProxmoxCluster::factory()->create();
        
        // Mock ProxmoxService for testing
        $mockService = Mockery::mock(ProxmoxService::class);
        $mockService->shouldReceive('syncNodes')
                   ->once()
                   ->andReturn([
                       'success' => true,
                       'message' => 'Nodes synced successfully',
                       'data' => collect([
                           new ProxmoxNode([
                               'proxmox_cluster_id' => $cluster->id,
                               'node_id' => 'node1',
                               'name' => 'Node 1',
                               'status' => 'online',
                           ])
                       ])
                   ]);
        
        $this->app->instance(ProxmoxService::class, $mockService);

        $response = $this->postJson("/api/proxmox/clusters/{$cluster->id}/sync-nodes");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Nodes synced successfully'
                 ]);
    }

    /**
     * Test getting resources for specific nodes.
     *
     * @return void
     */
    public function testGetResources()
    {
        // Create a test cluster
        $cluster = ProxmoxCluster::factory()->create();
        
        // Create some nodes for this cluster
        $nodes = ProxmoxNode::factory()->count(3)->create([
            'proxmox_cluster_id' => $cluster->id,
            'group' => 'test-group'
        ]);
        
        // Mock ProxmoxService for testing
        $mockService = Mockery::mock(ProxmoxService::class);
        $mockService->shouldReceive('getResourcesForNodes')
                   ->once()
                   ->andReturn([
                       $nodes[0]->node_id => [
                           'node' => $nodes[0],
                           'resources' => ['cpu' => 0.5, 'memory' => 8192],
                       ],
                       $nodes[1]->node_id => [
                           'node' => $nodes[1],
                           'resources' => ['cpu' => 0.2, 'memory' => 4096],
                       ],
                   ]);
        
        $this->app->instance(ProxmoxService::class, $mockService);

        // Test filtering by node IDs
        $response = $this->postJson(
            "/api/proxmox/clusters/{$cluster->id}/resources", 
            ['nodes' => [$nodes[0]->node_id, $nodes[1]->node_id]]
        );

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);
        
        // Test filtering by group
        $mockService->shouldReceive('getResourcesForNodes')
                   ->once()
                   ->andReturn([
                       $nodes[0]->node_id => [
                           'node' => $nodes[0],
                           'resources' => ['cpu' => 0.5, 'memory' => 8192],
                       ],
                       $nodes[1]->node_id => [
                           'node' => $nodes[1],
                           'resources' => ['cpu' => 0.2, 'memory' => 4096],
                       ],
                       $nodes[2]->node_id => [
                           'node' => $nodes[2],
                           'resources' => ['cpu' => 0.3, 'memory' => 6144],
                       ],
                   ]);
        
        $response = $this->postJson(
            "/api/proxmox/clusters/{$cluster->id}/resources", 
            ['group' => 'test-group']
        );

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
