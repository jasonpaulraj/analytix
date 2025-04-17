# Docker Python API Deployment Script for Proxmox LXC

This script automates the setup of a Docker container with Nginx and Let's Encrypt for Python API applications within a Proxmox LXC container. It's designed to work with Alpine Linux LXC containers.

## Prerequisites

- A running Proxmox server with Docker v2.35.0 LXC template installed
- An Alpine Linux LXC container created from this template
- Network connectivity from the container
- A GitHub repository containing your Python API code

## Features

- Automatic setup of Docker and Docker Compose
- Nginx configuration as a reverse proxy for your Python API
- Let's Encrypt SSL certificate setup and auto-renewal
- Cloudflare Tunnel integration for secure access without exposing ports
- GitHub repository cloning (with optional access token for private repos)
- Ability to specify a target directory within the cloned repository
- Firewall configuration with essential ports opened
- User management with SSH key setup
- Interactive prompting for missing parameters
- Support for both FastAPI and Flask frameworks
- Option for Docker deployment or direct host deployment

## Usage

The script can be used in two ways: with command-line arguments or through interactive prompts.

### Command-line Arguments

```bash
./deploy_docker_python_api.sh --repo-url [GITHUB_URL] [OPTIONS]
```

### Interactive Mode

Simply run the script without arguments, and it will prompt you for each required parameter:

```bash
./deploy_docker_python_api.sh
```

The script will guide you through the setup process with interactive prompts for each parameter.

### Required Parameters

- --repo-url : GitHub repository URL to clone (if not provided, will be prompted)

### Optional Parameters

- --token : GitHub access token for private repositories
- --target-dir : Directory to change into after cloning (relative to cloned repo)
- --api-port : Port on which the Python API will run (default: 8000)
- --domain : Domain name for Let's Encrypt SSL
- --cf-tunnel : Domain/subdomain configured in Cloudflare Tunnel
- --help : Display help message

## Examples

### Basic Usage with Command-line Arguments

```bash
./deploy_docker_python_api.sh --repo-url https://github.com/username/python-api.git
```

### With GitHub Token and Custom Directory

```bash
./deploy_docker_python_api.sh \
  --repo-url https://github.com/username/python-api.git \
  --token ghp_yourgithubtoken \
  --target-dir api_folder
```

### With Custom API Port and Domain for SSL

```bash
./deploy_docker_python_api.sh \
  --repo-url https://github.com/username/python-api.git \
  --api-port 5000 \
  --domain api.example.com
```

### With Cloudflare Tunnel

```bash
./deploy_docker_python_api.sh \
  --repo-url https://github.com/username/python-api.git \
  --cf-tunnel api.example.com
```

### Interactive Mode Example

Run the script without arguments and follow the prompts:

```bash
./deploy_docker_python_api.sh

# You will be prompted for:
# - GitHub repository URL (required)
# - GitHub access token (optional)
# - Target directory (optional)
# - API port (optional, defaults to 8000)
# - Domain name for SSL or Cloudflare Tunnel (optional)
```

## What the Script Does

1. Updates the system and installs essential packages
2. Sets up user accounts and SSH access
3. Installs Docker and Docker Compose
4. Configures Nginx as a reverse proxy
5. Clones your GitHub repository
6. Changes to the specified target directory (if provided)
7. Creates Docker Compose and Dockerfile if they don't exist
8. Sets up firewall rules
9. Configures Let's Encrypt SSL (if domain provided) or Cloudflare Tunnel
10. Detects Python framework (FastAPI or Flask) and configures accordingly
11. Deploys application using Docker or directly on the host
12. Provides useful commands for monitoring and management

## Domain Configuration Options

The script offers three options for domain configuration:

1. Let's Encrypt SSL : For standard domains with automatic SSL certificate generation
2. Cloudflare Tunnel : For secure access without exposing ports to the internet
3. No domain : Basic setup without SSL

### Cloudflare Tunnel Setup

If you choose to use Cloudflare Tunnel, you'll need to:

1. Set up a Cloudflare Tunnel in your Cloudflare account before running this script
2. Configure the tunnel to point to this server on port 80
3. Provide the domain/subdomain configured in your Cloudflare Tunnel when prompted
   The script will configure Nginx with the appropriate headers for Cloudflare.

## Deployment Options

The script offers two deployment methods:

1. Docker Deployment (Recommended) : Uses Docker and Docker Compose to containerize your application
2. Direct Host Deployment : Installs dependencies directly on the host and creates a systemd service
   The script will detect your Python framework (FastAPI or Flask) and configure the appropriate settings.
