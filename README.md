# analytix Monitor Backend

A Laravel-based backend service for connecting to and retrieving information from multiple Proxmox nodes.

## Project Overview

This service allows you to:
- Connect to multiple Proxmox clusters and nodes
- Retrieve comprehensive information as JSON responses
- Configure information retrieval for all nodes or specific groups of nodes
- Manage clusters and nodes through RESTful API endpoints

## Features

- **Cluster Management**: Add, update, delete, and test connections to Proxmox clusters
- **Node Management**: Sync nodes from clusters, update node information
- **Resource Retrieval**: Get resources from specific clusters, nodes, or filtered by resource type
- **Clean Architecture**: Implemented using service-repository pattern for separation of concerns
- **Error Handling**: Custom exception handling for Proxmox API interactions
- **Containerization**: Docker setup for production deployment

## API Documentation

A complete Postman collection (`Proxmox_API_Collection.json`) is included with the project. Import this collection into Postman to explore all available API endpoints.

### Key Endpoints

#### Cluster Management
- `GET /api/proxmox/clusters` - Get all clusters
- `POST /api/proxmox/clusters` - Add a new cluster
- `PUT /api/proxmox/clusters/{id}` - Update a cluster
- `DELETE /api/proxmox/clusters/{id}` - Delete a cluster
- `POST /api/proxmox/clusters/{id}/test-connection` - Test connection to a cluster

#### Node Management
- `POST /api/proxmox/clusters/{id}/sync-nodes` - Sync nodes from a cluster
- `PUT /api/proxmox/nodes/{id}` - Update node information

#### Resource Retrieval
- `GET /api/proxmox/clusters/{id}/resources` - Get all resources for a cluster
- `POST /api/proxmox/clusters/{id}/resources` - Get filtered resources
- `GET /api/proxmox/clusters/{id}/nodes/{node_id}` - Get details for a specific node
- `GET /api/proxmox/clusters/{id}/nodes/{node_id}/vms` - Get all VMs for a node
- `GET /api/proxmox/clusters/{id}/nodes/{node_id}/containers` - Get all containers for a node

## Development Setup

### Prerequisites
- PHP 8.1+
- Composer
- PostgreSQL (for Replit) or MySQL (for Docker)

### Local Development

1. Clone the repository
2. Run `composer install`
3. Configure your database in `.env`
4. Run migrations: `php artisan migrate`
5. Start the development server: `php artisan serve`

### Docker Deployment

For production deployment, use Docker:

1. Configure `.env` with your production settings
2. Run `docker-compose up -d`

## Configuration

The application can be configured through the following files:

- `.env` - Environment configuration
- `config/proxmox.php` - Proxmox-specific settings (timeout, retry, logging)
- `config/database.php` - Database configuration

## Testing

Run the test suite with:

```
php artisan test
```

## License

This project is open-sourced software.
