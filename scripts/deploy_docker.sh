#!/bin/sh
# Docker LXC Python API Web Server Setup Script
# This script sets up a Docker container with Nginx and Let's Encrypt for Python API

# Function to display usage information
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo "Options:"
    echo "  --repo-url       GitHub repository URL to clone"
    echo "  --token          GitHub access token for private repositories"
    echo "  --target-dir     Directory to change into after cloning (relative to cloned repo)"
    echo "  --api-port       Port on which the Python API will run (default: 8000)"
    echo "  --domain         Domain name for Let's Encrypt SSL"
    echo "  --cf-tunnel      Domain/subdomain configured in Cloudflare Tunnel"
    echo "  --help           Display this help message"
    echo ""
    echo "Note: If options are not provided as arguments, you will be prompted for input interactively."
    exit 1
}

# Function to prompt for yes/no confirmation
confirm() {
    while true; do
        printf "%s [y/n]: " "$1"
        read -r yn
        case $yn in
        [Yy]*) return 0 ;;
        [Nn]*) return 1 ;;
        *) echo "Please answer yes or no." ;;
        esac
    done
}

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check and install a package
check_install_package() {
    PKG_NAME=$1
    echo "Checking for $PKG_NAME..."

    if dpkg -l | grep -q "^ii.*$PKG_NAME"; then
        echo "$PKG_NAME is already installed."
        return 0
    else
        echo "Installing $PKG_NAME..."
        apt-get install -y "$PKG_NAME" || {
            echo "Failed to install $PKG_NAME. Attempting to fix..."
            apt-get -f install -y
            dpkg --configure -a
            apt-get install -y "$PKG_NAME" || {
                echo "Error: Failed to install $PKG_NAME after recovery attempt."
                return 1
            }
        }
        echo "$PKG_NAME installed successfully."
        return 0
    fi
}

# Detect Linux distribution
if [ -f /etc/os-release ]; then
    . /etc/os-release
    DISTRO=$ID
    DISTRO_VERSION=$VERSION_ID
elif [ -f /etc/lsb-release ]; then
    . /etc/lsb-release
    DISTRO=$DISTRIB_ID
    DISTRO_VERSION=$DISTRIB_VERSION
else
    DISTRO=$(uname -s)
    DISTRO_VERSION=$(uname -r)
fi
echo "Detected distribution: $DISTRO $DISTRO_VERSION"

# Parse command line arguments and/or prompt for input
REPO_URL=""
GITHUB_TOKEN=""
TARGET_DIR=""
API_PORT="8000"
DOMAIN_NAME=""
CF_TUNNEL_DOMAIN=""
USE_CLOUDFLARE_TUNNEL=false

# First, check for command-line arguments
while [ "$#" -gt 0 ]; do
    case "$1" in
    --repo-url)
        REPO_URL="$2"
        shift 2
        ;;
    --token)
        GITHUB_TOKEN="$2"
        shift 2
        ;;
    --target-dir)
        TARGET_DIR="$2"
        shift 2
        ;;
    --api-port)
        API_PORT="$2"
        shift 2
        ;;
    --domain)
        DOMAIN_NAME="$2"
        shift 2
        ;;
    --cf-tunnel)
        CF_TUNNEL_DOMAIN="$2"
        USE_CLOUDFLARE_TUNNEL=true
        shift 2
        ;;
    --help)
        show_usage
        ;;
    *)
        echo "Unknown parameter: $1"
        show_usage
        ;;
    esac
done

# Then, prompt for any missing parameters
# Prompt for GitHub repository URL if not provided
if [ -z "$REPO_URL" ]; then
    echo "Enter GitHub repository URL to clone:"
    read -r REPO_URL

    # Validate input
    if [ -z "$REPO_URL" ]; then
        echo "Error: GitHub repository URL is required"
        exit 1
    fi
fi

# Extract repository name from URL for later use
REPO_NAME=$(basename "$REPO_URL" .git)
REPO_EXISTS=false

# Check if repository already exists
if [ -d "/var/www/$REPO_NAME" ]; then
    REPO_EXISTS=true
    echo "Repository $REPO_NAME already exists in /var/www/"
    echo "Will update existing repository instead of cloning."
fi

# Prompt for GitHub token if not provided and repo doesn't exist
if [ -z "$GITHUB_TOKEN" ] && [ "$REPO_EXISTS" = "false" ]; then
    echo "Do you want to provide a GitHub access token for private repositories?"
    if confirm "Provide GitHub token"; then
        echo "Enter GitHub access token:"
        read -r GITHUB_TOKEN
    fi
fi

# Prompt for target directory if not provided and repo doesn't exist
if [ -z "$TARGET_DIR" ] && [ "$REPO_EXISTS" = "false" ]; then
    echo "Do you want to specify a target directory within the repository?"
    if confirm "Specify target directory"; then
        echo "Enter target directory (relative to cloned repo):"
        read -r TARGET_DIR
    fi
fi

# Prompt for API port if still at default
if [ "$API_PORT" = "8000" ]; then
    echo "Do you want to change the default API port (8000)?"
    if confirm "Change API port"; then
        echo "Enter API port:"
        read -r API_PORT
    fi
fi

# Inform user about port 80 configuration
echo "Note: The application will be accessible on port 80 via Nginx, regardless of the API port setting."

# Prompt for domain name if not provided
if [ -z "$DOMAIN_NAME" ] && [ "$USE_CLOUDFLARE_TUNNEL" = "false" ]; then
    echo "Do you want to set up SSL with Let's Encrypt or use a Cloudflare Tunnel?"
    echo "1) Set up Let's Encrypt SSL"
    echo "2) Use Cloudflare Tunnel"
    echo "3) Skip domain configuration"

    while true; do
        printf "Select an option [1-3]: "
        read -r domain_option
        case $domain_option in
        1)
            echo "Enter your domain name for Let's Encrypt SSL (e.g., example.com):"
            read -r DOMAIN_NAME
            break
            ;;
        2)
            USE_CLOUDFLARE_TUNNEL=true
            echo "Enter the domain/subdomain configured in your Cloudflare Tunnel:"
            read -r CF_TUNNEL_DOMAIN
            break
            ;;
        3)
            echo "Skipping domain configuration."
            break
            ;;
        *)
            echo "Please select a valid option (1-3)."
            ;;
        esac
    done

    # If using Cloudflare Tunnel, ask for additional details
    if [ "$USE_CLOUDFLARE_TUNNEL" = "true" ]; then
        echo "Is your Cloudflare Tunnel already set up and running?"
        if ! confirm "Cloudflare Tunnel ready"; then
            echo "Please set up your Cloudflare Tunnel before continuing."
            echo "Visit https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/tunnel-guide/ for instructions."
            echo ""
            echo "After setting up the tunnel, configure it to point to this server on port 80."
        fi
    fi
fi

echo "===== Starting Docker Python API Web Server Setup ====="

# Step 1: Set up basic system components
echo "===== Step 1: Setting up basic system components ====="
echo "Updating system packages..."
apt-get update && apt-get upgrade -y

echo "Installing essential packages..."
ESSENTIAL_PACKAGES="curl wget git vim sudo bash openssh-server"
for pkg in $ESSENTIAL_PACKAGES; do
    check_install_package "$pkg"
done

# Ask about the type of system to install dependencies for
echo "What type of system are you deploying?"
echo "0) Skip/No dependencies needed"
echo "1) Python (Generic)"
echo "2) FastAPI/Python"
echo "3) PHP"
echo "4) Laravel/PHP"
echo "5) Go"
echo "6) React/Web"
echo "7) Node.js"

SYSTEM_TYPE=""
while [ -z "$SYSTEM_TYPE" ]; do
    printf "Select an option [1-7]: "
    read -r system_option
    case $system_option in
    0)
        SYSTEM_TYPE="none"
        echo "Skipping dependency installation..."
        ;;
    1)
        SYSTEM_TYPE="python"
        echo "Installing Python dependencies..."
        check_install_package "python3-pip"
        check_install_package "python3-dev"
        check_install_package "gcc"
        check_install_package "libffi-dev"
        check_install_package "libssl-dev"
        ;;
    2)
        SYSTEM_TYPE="fastapi"
        echo "Installing FastAPI dependencies..."
        check_install_package "python3-pip"
        check_install_package "python3-dev"
        check_install_package "gcc"
        check_install_package "libffi-dev"
        check_install_package "libssl-dev"
        check_install_package "python3-venv"
        ;;
    3)
        SYSTEM_TYPE="php"
        echo "Installing PHP dependencies..."
        check_install_package "php"
        check_install_package "php-fpm"
        check_install_package "php-cli"
        check_install_package "php-common"
        check_install_package "php-mysql"
        check_install_package "php-zip"
        check_install_package "php-gd"
        check_install_package "php-mbstring"
        check_install_package "php-curl"
        check_install_package "php-xml"
        check_install_package "php-bcmath"
        check_install_package "php-json"
        ;;
    4)
        SYSTEM_TYPE="laravel"
        echo "Installing Laravel dependencies..."
        check_install_package "php"
        check_install_package "php-fpm"
        check_install_package "php-cli"
        check_install_package "php-common"
        check_install_package "php-mysql"
        check_install_package "php-zip"
        check_install_package "php-gd"
        check_install_package "php-mbstring"
        check_install_package "php-curl"
        check_install_package "php-xml"
        check_install_package "php-bcmath"
        check_install_package "php-json"
        check_install_package "composer"
        check_install_package "nodejs"
        check_install_package "npm"
        ;;
    5)
        SYSTEM_TYPE="go"
        echo "Installing Go dependencies..."
        check_install_package "golang-go"
        check_install_package "gcc"
        check_install_package "g++"
        check_install_package "make"
        ;;
    6)
        SYSTEM_TYPE="react"
        echo "Installing React/Web dependencies..."
        # Add Node.js repository for latest version
        if [ ! -f /etc/apt/sources.list.d/nodesource.list ]; then
            curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
        fi
        check_install_package "nodejs"
        check_install_package "npm"
        # Install global packages
        npm install -g serve
        ;;
    7)
        SYSTEM_TYPE="nodejs"
        echo "Installing Node.js dependencies..."
        # Add Node.js repository for latest version
        if [ ! -f /etc/apt/sources.list.d/nodesource.list ]; then
            curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
        fi
        check_install_package "nodejs"
        check_install_package "npm"
        check_install_package "build-essential"
        ;;
    *)
        echo "Please select a valid option (1-7)."
        ;;
    esac
done

# SSH server setup
if systemctl list-unit-files | grep -q ssh.service; then
    echo "SSH server is installed. Checking status..."
    if ! systemctl is-active --quiet ssh; then
        echo "SSH server is not running. Starting and enabling..."
        systemctl enable ssh
        systemctl start ssh
    else
        echo "SSH server is already running."
    fi
else
    echo "SSH server not found. Installing..."
    check_install_package "openssh-server"
    systemctl enable ssh
    systemctl start ssh
fi

# User management
echo "Do you want to create a new user?"
if confirm "Create new user"; then
    echo "Enter username for the new user:"
    read -r NEW_USER

    # Check if user already exists
    if id "$NEW_USER" >/dev/null 2>&1; then
        echo "User $NEW_USER already exists."
        echo "Do you want to reconfigure this user?"
        if confirm "Reconfigure user"; then
            # Reconfiguration options
            echo "Do you want to reset the password for $NEW_USER?"
            if confirm "Reset password"; then
                passwd "$NEW_USER"
            fi
        else
            echo "Skipping user configuration for $NEW_USER"
        fi
    else
        # Create the user
        echo "Creating new user $NEW_USER..."
        adduser "$NEW_USER"
    fi

    # Add user to sudo group for sudo access if not already a member
    if ! groups "$NEW_USER" | grep -q sudo; then
        echo "%sudo ALL=(ALL) ALL" >/etc/sudoers.d/sudo
        usermod -aG sudo "$NEW_USER"
        echo "Added $NEW_USER to sudo group"
    else
        echo "User $NEW_USER is already in the sudo group"
    fi

    # Check if www-data group exists and offer to add user to it
    if getent group www-data >/dev/null; then
        echo "Do you want to add $NEW_USER to the www-data group for web server access?"
        if confirm "Add to www-data group"; then
            usermod -aG www-data "$NEW_USER"
            echo "Added $NEW_USER to www-data group"
        fi
    else
        echo "Creating www-data group and adding $NEW_USER..."
        groupadd www-data
        usermod -aG www-data "$NEW_USER"
        echo "Created www-data group and added $NEW_USER"
    fi

    # Set up SSH key for the user (works for both new and existing users)
    echo "Do you want to add or update an SSH public key for $NEW_USER?"
    if confirm "Add/update SSH key"; then
        mkdir -p /home/"$NEW_USER"/.ssh
        chmod 700 /home/"$NEW_USER"/.ssh
        echo "Paste the SSH public key for $NEW_USER (then press Ctrl+D):"
        cat >/home/"$NEW_USER"/.ssh/authorized_keys
        chmod 600 /home/"$NEW_USER"/.ssh/authorized_keys
        chown -R "$NEW_USER":"$NEW_USER" /home/"$NEW_USER"/.ssh
        echo "SSH key added/updated for $NEW_USER"
    fi

    # Ask about disabling root SSH access
    echo "Do you want to disable root SSH login?"
    if confirm "Disable root SSH"; then
        sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin no/' /etc/ssh/sshd_config
        service sshd restart
        echo "Root SSH login has been disabled"
    fi
else
    # If no new user, ask about SSH key for root
    echo "Do you want to add an SSH public key for root?"
    if confirm "Add SSH key"; then
        mkdir -p /root/.ssh
        chmod 700 /root/.ssh
        echo "Paste your SSH public key (then press Ctrl+D):"
        cat >/root/.ssh/authorized_keys
        chmod 600 /root/.ssh/authorized_keys
        echo "SSH key added successfully"
    fi
fi

# Step 2: Install Docker and Docker Compose
echo "===== Step 2: Installing Docker and Docker Compose ====="

# Check if Docker is already installed
if command_exists docker; then
    echo "Docker is already installed. Checking version..."
    DOCKER_VERSION=$(docker --version | awk '{print $3}' | sed 's/,//')
    echo "Current Docker version: $DOCKER_VERSION"

    # Check if Docker service is running
    if ! systemctl is-active --quiet docker; then
        echo "Docker service is not running. Starting and enabling..."
        systemctl enable docker
        systemctl start docker
    else
        echo "Docker service is already running."
    fi
else
    echo "Installing Docker..."
    check_install_package "apt-transport-https"
    check_install_package "ca-certificates"
    check_install_package "curl"
    check_install_package "gnupg"
    check_install_package "lsb-release"

    # Add Docker's official GPG key
    if [ ! -f /usr/share/keyrings/docker-archive-keyring.gpg ]; then
        curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
    fi

    # Set up the stable repository
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/debian $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list >/dev/null

    # Install Docker Engine
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io

    # Start and enable Docker
    systemctl enable docker
    systemctl start docker
fi

# Ensure www-data group exists
if ! getent group www-data >/dev/null; then
    echo "Creating www-data group..."
    groupadd www-data
fi

# Set Docker socket permissions for www-data
echo "Setting Docker socket permissions for www-data..."
if [ -S /var/run/docker.sock ]; then
    chown root:www-data /var/run/docker.sock
    chmod 660 /var/run/docker.sock

    # Make permission change persistent
    echo 'DOCKER_OPTS="-G www-data"' >/etc/default/docker
    systemctl daemon-reload
    systemctl restart docker

    echo "Docker socket permissions updated for www-data group"
else
    echo "Warning: Docker socket not found at /var/run/docker.sock"
fi

# Install Docker Compose v2
echo "Checking for Docker Compose..."
if command_exists docker-compose; then
    COMPOSE_VERSION=$(docker-compose --version | awk '{print $3}' | sed 's/,//')
    echo "Docker Compose v1 is installed (version $COMPOSE_VERSION)"
fi

# Check for Docker Compose V2 (docker compose)
if docker compose version >/dev/null 2>&1; then
    COMPOSE_V2_VERSION=$(docker compose version --short)
    echo "Docker Compose V2 is installed (version $COMPOSE_V2_VERSION)"
else
    echo "Installing Docker Compose V2..."

    # Install dependencies
    check_install_package "python3-pip"
    check_install_package "gcc"
    check_install_package "python3-dev"
    check_install_package "libffi-dev"
    check_install_package "libssl-dev"

    # Install Docker Compose plugin
    mkdir -p /usr/local/lib/docker/cli-plugins
    curl -SL https://github.com/docker/compose/releases/download/v2.20.3/docker-compose-linux-x86_64 -o /usr/local/lib/docker/cli-plugins/docker-compose
    chmod +x /usr/local/lib/docker/cli-plugins/docker-compose

    # Create alias for backward compatibility
    echo 'alias docker-compose="docker compose"' >>/etc/bash.bashrc

    # Verify installation
    if docker compose version >/dev/null 2>&1; then
        echo "Docker Compose V2 installed successfully"
    else
        echo "Warning: Docker Compose V2 installation may have failed"

        # Fallback to pip installation for v1
        echo "Installing Docker Compose V1 as fallback..."
        pip3 install docker-compose
    fi
fi

# Step 3: Set up Nginx
echo "===== Step 3: Setting up Nginx web server ====="

# Determine Nginx user and configuration paths based on distribution
NGINX_USER="www-data" # Default for Debian/Ubuntu
SITES_AVAILABLE="/etc/nginx/sites-available"
SITES_ENABLED="/etc/nginx/sites-enabled"

# Check if the distribution uses a different structure
if [ "$DISTRO" = "centos" ] || [ "$DISTRO" = "fedora" ] || [ "$DISTRO" = "rhel" ]; then
    SITES_AVAILABLE="/etc/nginx/conf.d"
    SITES_ENABLED="/etc/nginx/conf.d"
    NGINX_USER="nginx"
elif [ "$DISTRO" = "alpine" ]; then
    SITES_AVAILABLE="/etc/nginx/conf.d"
    SITES_ENABLED="/etc/nginx/conf.d"
    NGINX_USER="nginx"
fi

# Ask user if they want to install Nginx
echo "Do you want to install and configure Nginx?"
if confirm "Install Nginx"; then
    # User wants to install Nginx - follow existing logic

    # Determine Nginx user and configuration paths based on distribution
    NGINX_USER="www-data" # Default for Debian/Ubuntu
    SITES_AVAILABLE="/etc/nginx/sites-available"
    SITES_ENABLED="/etc/nginx/sites-enabled"

    # Check if the distribution uses a different structure
    if [ "$DISTRO" = "centos" ] || [ "$DISTRO" = "fedora" ] || [ "$DISTRO" = "rhel" ]; then
        SITES_AVAILABLE="/etc/nginx/conf.d"
        SITES_ENABLED="/etc/nginx/conf.d"
        NGINX_USER="nginx"
    elif [ "$DISTRO" = "alpine" ]; then
        SITES_AVAILABLE="/etc/nginx/conf.d"
        SITES_ENABLED="/etc/nginx/conf.d"
        NGINX_USER="nginx"
    fi

    # Ensure www-data user and group exist
    if [ "$NGINX_USER" = "www-data" ]; then
        if ! getent group www-data >/dev/null; then
            echo "Creating www-data group..."
            groupadd www-data
        fi

        if ! id -u www-data >/dev/null 2>&1; then
            echo "Creating www-data user..."
            useradd -r -g www-data -s /usr/sbin/nologin -d /var/www www-data
        fi
    fi

    # First check if nginx is already installed
    if dpkg -l | grep -q nginx; then
        echo "Nginx appears to be installed. Checking for issues..."
        if ! systemctl is-active --quiet nginx; then
            echo "Nginx service is not running. Attempting to fix by reinstalling..."
            apt-get remove --purge -y nginx nginx-common nginx-full
            rm -rf /etc/nginx /var/log/nginx /var/lib/nginx

            # Reinstall Nginx
            apt-get update
            apt-get install -y nginx
        else
            echo "Nginx is running properly."
        fi
    else
        echo "Installing Nginx..."
        apt-get update
        apt-get install -y nginx
    fi

    # Verify nginx installation
    if ! dpkg -l | grep -q "^ii.*nginx"; then
        echo "Failed to install Nginx. Please check system logs and try again."
        exit 1
    fi

    # Set proper ownership for Nginx directories
    echo "Setting proper ownership for Nginx directories..."
    chown -R $NGINX_USER:$NGINX_USER /var/www
    chown -R $NGINX_USER:$NGINX_USER /var/log/nginx

    # Create necessary directories
    mkdir -p $SITES_AVAILABLE $SITES_ENABLED

    # Backup original Nginx configuration
    if [ -f /etc/nginx/nginx.conf ]; then
        echo "Backing up original Nginx configuration..."
        cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup.$(date +%Y%m%d%H%M%S)
    fi

    # Create a basic Nginx configuration
    cat >/etc/nginx/nginx.conf <<EOF
user $NGINX_USER;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    
    log_format main '\$remote_addr - \$remote_user [\$time_local] "\$request" '
                    '\$status \$body_bytes_sent "\$http_referer" '
                    '"\$http_user_agent" "\$http_x_forwarded_for"';
    
    access_log /var/log/nginx/access.log main;
    
EOF
else
    # User doesn't want to install Nginx
    echo "Skipping Nginx installation..."

    # Check if Nginx is already installed
    if dpkg -l | grep -q nginx; then
        echo "Nginx is already installed on this system."
        echo "Do you want to completely remove all traces of Nginx?"
        if confirm "Remove Nginx"; then
            echo "Removing Nginx and all configuration files..."
            apt-get remove --purge -y nginx nginx-common nginx-full
            rm -rf /etc/nginx /var/log/nginx /var/lib/nginx
            echo "Nginx has been completely removed from the system."
        else
            echo "Keeping existing Nginx installation."

            # Determine Nginx user and configuration paths based on distribution
            NGINX_USER="www-data" # Default for Debian/Ubuntu
            SITES_AVAILABLE="/etc/nginx/sites-available"
            SITES_ENABLED="/etc/nginx/sites-enabled"

            # Check if the distribution uses a different structure
            if [ "$DISTRO" = "centos" ] || [ "$DISTRO" = "fedora" ] || [ "$DISTRO" = "rhel" ]; then
                SITES_AVAILABLE="/etc/nginx/conf.d"
                SITES_ENABLED="/etc/nginx/conf.d"
                NGINX_USER="nginx"
            elif [ "$DISTRO" = "alpine" ]; then
                SITES_AVAILABLE="/etc/nginx/conf.d"
                SITES_ENABLED="/etc/nginx/conf.d"
                NGINX_USER="nginx"
            fi
        fi
    else
        echo "Nginx is not installed. Skipping Nginx configuration."
        # Set variables to indicate Nginx is not available
        NGINX_AVAILABLE=false
    fi
fi

# Step 4: Clone or update repository
echo "===== Step 4: Setting up application code ====="
mkdir -p /var/www

# Set base directory
BASE_DIR="/var/www/$REPO_NAME"

if [ "$REPO_EXISTS" = "true" ]; then
    echo "Updating existing repository at $BASE_DIR..."
    cd "$BASE_DIR"

    # Determine default branch (main or master)
    git fetch
    DEFAULT_BRANCH=$(git remote show origin | grep "HEAD branch" | awk '{print $NF}')
    echo "Default branch is: $DEFAULT_BRANCH"

    # Pull latest changes
    git checkout "$DEFAULT_BRANCH"
    git pull origin "$DEFAULT_BRANCH"

    # Use existing target directory if it was previously set
    if [ -n "$TARGET_DIR" ] && [ -d "$BASE_DIR/$TARGET_DIR" ]; then
        APP_DIR="$BASE_DIR/$TARGET_DIR"
    else
        APP_DIR="$BASE_DIR"
    fi
else
    echo "Cloning repository from $REPO_URL..."
    if [ -n "$GITHUB_TOKEN" ]; then
        # Extract username and repo from URL
        REPO_PATH=$(echo "$REPO_URL" | sed -E 's|https://github.com/||' | sed -E 's|.git$||')

        # Clone with token
        git clone "https://$GITHUB_TOKEN@github.com/$REPO_PATH.git" "$BASE_DIR"
    else
        # Clone without token
        git clone "$REPO_URL" "$BASE_DIR"
    fi

    # Change to target directory if specified
    APP_DIR="$BASE_DIR"
    if [ -n "$TARGET_DIR" ]; then
        if [ -d "$BASE_DIR/$TARGET_DIR" ]; then
            APP_DIR="$BASE_DIR/$TARGET_DIR"
            echo "Changed to target directory: $APP_DIR"
        else
            echo "Warning: Target directory '$TARGET_DIR' not found in the repository"
        fi
    fi
fi

# Create Nginx site configuration for Python API
echo "Creating Nginx site configuration..."
SITE_CONFIG_FILE="$SITES_AVAILABLE/default.conf"
cat >"$SITE_CONFIG_FILE" <<EOF
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    
    root $APP_DIR;
    index index.html index.htm;
    
    server_name _;
    
    # Main location block to proxy all requests to the Python API
    location / {
        proxy_pass http://localhost:$API_PORT;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
    }
    
    # Deny access to .htaccess files
    location ~ /\.ht {
        deny all;
    }
    
    # Additional security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;
    add_header X-XSS-Protection "1; mode=block";
}
EOF

# Enable the site
if [ "$DISTRO" = "debian" ] || [ "$DISTRO" = "ubuntu" ]; then
    ln -sf "$SITES_AVAILABLE/default.conf" "$SITES_ENABLED/default.conf"
    # Remove default site if it exists
    if [ -f "$SITES_ENABLED/default" ]; then
        rm -f "$SITES_ENABLED/default"
    fi
fi

# Step 5: Create Docker Compose file if it doesn't exist
if [ ! -f "$APP_DIR/docker-compose.yml" ]; then
    echo "No docker-compose.yml found. Creating a basic one for Python API..."
    cat >"$APP_DIR/docker-compose.yml" <<EOF
version: '3'

services:
  api:
    build:
      context: .
    # Only expose port internally, not to the host directly
    # This ensures all traffic goes through Nginx
    expose:
      - "$API_PORT"
    volumes:
      - .:/app
    restart: unless-stopped
    environment:
      - PORT=$API_PORT
    networks:
      - web_network

networks:
  web_network:
    driver: bridge
EOF

    # Create a basic Dockerfile if it doesn't exist
    if [ ! -f "$APP_DIR/Dockerfile" ]; then
        echo "Creating a basic Dockerfile..."
        cat >"$APP_DIR/Dockerfile" <<EOF
FROM python:3.9-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE $API_PORT

CMD ["python", "app.py"]
EOF
    fi

    # Create a basic requirements.txt if it doesn't exist
    if [ ! -f "$APP_DIR/requirements.txt" ]; then
        echo "Creating a basic requirements.txt..."
        cat >"$APP_DIR/requirements.txt" <<EOF
flask==2.0.1
gunicorn==20.1.0
EOF
    fi
fi

# Step 6: Firewall setup
echo "===== Step 6: Setting up firewall ====="
if command_exists ufw; then
    echo "UFW is already installed. Checking status..."
    if ! ufw status | grep -q "Status: active"; then
        echo "UFW is not active. Configuring and enabling..."
        # Configure basic firewall rules
        ufw default deny incoming
        ufw default allow outgoing

        # Allow SSH
        ufw allow ssh

        # Allow HTTP and HTTPS
        ufw allow 80/tcp
        ufw allow 443/tcp

        # Allow the API port if different from 80
        if [ "$API_PORT" != "80" ]; then
            ufw allow "$API_PORT"/tcp
        fi

        # Enable firewall (non-interactive)
        echo "y" | ufw enable
        echo "Firewall configured and enabled"
    else
        echo "UFW is already active. Checking rules..."
        # Check if necessary ports are allowed
        if ! ufw status | grep -q "80/tcp"; then
            echo "Adding HTTP port to firewall rules..."
            ufw allow 80/tcp
        fi

        if ! ufw status | grep -q "443/tcp"; then
            echo "Adding HTTPS port to firewall rules..."
            ufw allow 443/tcp
        fi

        if [ "$API_PORT" != "80" ] && ! ufw status | grep -q "$API_PORT/tcp"; then
            echo "Adding API port to firewall rules..."
            ufw allow "$API_PORT"/tcp
        fi
    fi
else
    echo "Installing and configuring firewall..."
    apt-get install -y ufw

    # Configure basic firewall rules
    ufw default deny incoming
    ufw default allow outgoing

    # Allow SSH
    ufw allow ssh

    # Allow HTTP and HTTPS
    ufw allow 80/tcp
    ufw allow 443/tcp

    # Allow the API port if different from 80
    if [ "$API_PORT" != "80" ]; then
        ufw allow "$API_PORT"/tcp
    fi

    # Enable firewall (non-interactive)
    echo "y" | ufw enable
    echo "Firewall configured and enabled"
fi

# Step 7: Let's Encrypt setup
echo "===== Step 7: Setting up Let's Encrypt ====="
if [ -n "$DOMAIN_NAME" ]; then
    echo "Setting up Let's Encrypt for $DOMAIN_NAME..."

    # Check if certbot is installed
    if ! command_exists certbot; then
        echo "Installing certbot and dependencies..."
        apt-get update
        apt-get install -y certbot python3-certbot-nginx
    else
        echo "Certbot is already installed."
    fi

    # Check if domain is a subdomain (contains at least one dot)
    if echo "$DOMAIN_NAME" | grep -q "\..*\."; then
        echo "Detected subdomain. Setting up Let's Encrypt with DNS validation..."

        # Ask if using Cloudflare
        echo "Are you using Cloudflare for DNS?"
        if confirm "Using Cloudflare"; then
            # Check if Cloudflare plugin is installed
            if ! pip3 list | grep -q certbot-dns-cloudflare; then
                echo "Installing Cloudflare DNS plugin for certbot..."
                apt-get install -y python3-pip
                pip3 install certbot-dns-cloudflare
            fi

            # Create Cloudflare credentials file
            echo "Please enter your Cloudflare Global API Key:"
            read -r CF_API_KEY
            echo "Please enter your Cloudflare email address:"
            read -r CF_EMAIL

            mkdir -p /root/.secrets/
            cat >/root/.secrets/cloudflare.ini <<EOF
dns_cloudflare_email = $CF_EMAIL
dns_cloudflare_api_key = $CF_API_KEY
EOF
            chmod 600 /root/.secrets/cloudflare.ini

            # Obtain certificate using DNS validation
            certbot certonly --dns-cloudflare --dns-cloudflare-credentials /root/.secrets/cloudflare.ini \
                -d "$DOMAIN_NAME" --non-interactive --agree-tos --email "$CF_EMAIL" \
                --server https://acme-v02.api.letsencrypt.org/directory

            # Update Nginx configuration with SSL
            cat >"$SITES_AVAILABLE/default.conf" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN_NAME;
    
    # Redirect all HTTP requests to HTTPS
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    
    server_name $DOMAIN_NAME;
    
    ssl_certificate /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN_NAME/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/$DOMAIN_NAME/chain.pem;
    
    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:10m;
    ssl_session_tickets off;
    
    # OCSP Stapling
    ssl_stapling on;
    ssl_stapling_verify on;
    resolver 1.1.1.1 1.0.0.1 valid=300s;
    resolver_timeout 5s;
    
    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    
    root $APP_DIR;
    index index.html index.htm;
    
    # Main location block to proxy all requests to the Python API
    location / {
        proxy_pass http://localhost:$API_PORT;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
    }
    
    # Deny access to .htaccess files
    location ~ /\.ht {
        deny all;
    }
}
EOF
        else
            # Standard Let's Encrypt setup with HTTP validation
            # Update Nginx configuration with domain name
            sed -i "s/server_name _;/server_name $DOMAIN_NAME;/" "$SITES_AVAILABLE/default.conf"
            systemctl reload nginx

            # Run certbot to obtain SSL certificate
            certbot --nginx -d "$DOMAIN_NAME" --non-interactive --agree-tos --email webmaster@"$DOMAIN_NAME" --redirect
        fi
    else
        # Standard domain setup
        # Update Nginx configuration with domain name
        sed -i "s/server_name _;/server_name $DOMAIN_NAME;/" "$SITES_AVAILABLE/default.conf"
        systemctl reload nginx

        # Run certbot to obtain SSL certificate
        certbot --nginx -d "$DOMAIN_NAME" --non-interactive --agree-tos --email webmaster@"$DOMAIN_NAME" --redirect
    fi

    # Set up auto-renewal
    echo "Setting up automatic certificate renewal..."
    echo "0 3 * * * certbot renew --quiet" | crontab -

    echo "Let's Encrypt SSL certificate installed and auto-renewal configured"
else
    # No domain provided, check if using Cloudflare Tunnel
    echo "Do you have a Cloudflare Tunnel set up for this server?"
    if confirm "Using Cloudflare Tunnel"; then
        echo "Enter the domain/subdomain configured in your Cloudflare Tunnel:"
        read -r CF_TUNNEL_DOMAIN

        # Update Nginx configuration with the Cloudflare Tunnel domain
        sed -i "s/server_name _;/server_name $CF_TUNNEL_DOMAIN;/" "$SITES_AVAILABLE/default.conf"

        # Add special headers for Cloudflare
        sed -i '/server_name/a \    # Cloudflare headers\n    set_real_ip_from 103.21.244.0\/22;\n    set_real_ip_from 103.22.200.0\/22;\n    set_real_ip_from 103.31.4.0\/22;\n    set_real_ip_from 104.16.0.0\/13;\n    set_real_ip_from 104.24.0.0\/14;\n    set_real_ip_from 108.162.192.0\/18;\n    set_real_ip_from 131.0.72.0\/22;\n    set_real_ip_from 141.101.64.0\/18;\n    set_real_ip_from 162.158.0.0\/15;\n    set_real_ip_from 172.64.0.0\/13;\n    set_real_ip_from 173.245.48.0\/20;\n    set_real_ip_from 188.114.96.0\/20;\n    set_real_ip_from 190.93.240.0\/20;\n    set_real_ip_from 197.234.240.0\/22;\n    set_real_ip_from 198.41.128.0\/17;\n    set_real_ip_from 2400:cb00::\/32;\n    set_real_ip_from 2606:4700::\/32;\n    set_real_ip_from 2803:f800::\/32;\n    set_real_ip_from 2405:b500::\/32;\n    set_real_ip_from 2405:8100::\/32;\n    set_real_ip_from 2a06:98c0::\/29;\n    set_real_ip_from 2c0f:f248::\/32;\n    real_ip_header CF-Connecting-IP;' "$SITES_AVAILABLE/default.conf"

        echo "Nginx configured for Cloudflare Tunnel with domain: $CF_TUNNEL_DOMAIN"
        echo "Note: Make sure your Cloudflare Tunnel is properly configured to point to this server."
    else
        echo "No domain name provided. Skipping Let's Encrypt setup."
        echo "You can manually set up SSL later by running certbot."
    fi
fi

# Step 8: Configure for Python API (FastAPI/Flask with Gunicorn/Uvicorn)
echo "===== Step 8: Configuring Python API environment ====="

# Determine the Python framework being used
PYTHON_FRAMEWORK="unknown"
if [ -f "$APP_DIR/requirements.txt" ]; then
    if grep -q "fastapi" "$APP_DIR/requirements.txt"; then
        PYTHON_FRAMEWORK="fastapi"
    elif grep -q "flask" "$APP_DIR/requirements.txt"; then
        PYTHON_FRAMEWORK="flask"
    fi
fi

echo "Detected Python framework: $PYTHON_FRAMEWORK"

# Ask about using Docker or direct deployment
echo "Do you want to deploy using Docker (recommended) or directly on the host?"
if confirm "Use Docker deployment"; then
    # Docker deployment
    echo "Using Docker deployment..."

    # Check if docker-compose.yml exists, if not create it
    if [ ! -f "$APP_DIR/docker-compose.yml" ]; then
        echo "Creating docker-compose.yml file..."

        if [ "$PYTHON_FRAMEWORK" = "fastapi" ]; then
            # FastAPI specific docker-compose
            cat >"$APP_DIR/docker-compose.yml" <<EOF
services:
  api:
    build:
      context: .
    expose:
      - "$API_PORT"
    volumes:
      - .:/app
    restart: unless-stopped
    environment:
      - PORT=$API_PORT
      - HOST=0.0.0.0
      - WORKERS=4
    networks:
      - web_network

networks:
  web_network:
    driver: bridge
EOF

            # Create a FastAPI-specific Dockerfile if it doesn't exist
            if [ ! -f "$APP_DIR/Dockerfile" ]; then
                echo "Creating FastAPI Dockerfile..."
                cat >"$APP_DIR/Dockerfile" <<EOF
FROM python:3.9-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE $API_PORT

# Use Uvicorn with multiple workers for production
CMD ["sh", "-c", "uvicorn main:app --host \${HOST:-0.0.0.0} --port \${PORT:-$API_PORT} --workers \${WORKERS:-4}"]
EOF
            fi

        elif [ "$PYTHON_FRAMEWORK" = "flask" ]; then
            # Flask specific docker-compose
            cat >"$APP_DIR/docker-compose.yml" <<EOF
services:
  api:
    build:
      context: .
    expose:
      - "$API_PORT"
    volumes:
      - .:/app
    restart: unless-stopped
    environment:
      - PORT=$API_PORT
      - WORKERS=4
    networks:
      - web_network

networks:
  web_network:
    driver: bridge
EOF

            # Create a Flask-specific Dockerfile if it doesn't exist
            if [ ! -f "$APP_DIR/Dockerfile" ]; then
                echo "Creating Flask Dockerfile..."
                cat >"$APP_DIR/Dockerfile" <<EOF
FROM python:3.9-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE $API_PORT

# Use Gunicorn with multiple workers for production
CMD ["sh", "-c", "gunicorn --bind 0.0.0.0:\${PORT:-$API_PORT} --workers \${WORKERS:-4} app:app"]
EOF
            fi

        else
            # Generic Python API docker-compose
            cat >"$APP_DIR/docker-compose.yml" <<EOF
version: '3'

services:
  api:
    build:
      context: .
    expose:
      - "$API_PORT"
    volumes:
      - .:/app
    restart: unless-stopped
    environment:
      - PORT=$API_PORT
    networks:
      - web_network

networks:
  web_network:
    driver: bridge
EOF

            # Create a generic Dockerfile if it doesn't exist
            if [ ! -f "$APP_DIR/Dockerfile" ]; then
                echo "Creating generic Python Dockerfile..."
                cat >"$APP_DIR/Dockerfile" <<EOF
FROM python:3.9-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE $API_PORT

# Default to running app.py, modify as needed
CMD ["python", "app.py"]
EOF
            fi
        fi

        # Create a basic requirements.txt if it doesn't exist
        if [ ! -f "$APP_DIR/requirements.txt" ]; then
            echo "Creating a basic requirements.txt..."
            cat >"$APP_DIR/requirements.txt" <<EOF
# Choose your framework by uncommenting one of these:
# fastapi==0.95.1
# uvicorn==0.22.0
flask==2.0.1
gunicorn==20.1.0
EOF
        fi
    fi

    # Start Docker Compose
    echo "Starting Docker Compose..."
    cd "$APP_DIR"

    # Use docker compose v2 if available, otherwise fallback to v1
    if docker compose version >/dev/null 2>&1; then
        docker compose up -d
    else
        docker-compose up -d
    fi

    echo "Docker Compose started successfully"

else
    # Direct deployment on host
    echo "Setting up direct deployment on host..."

    # Install Python dependencies
    echo "Installing Python dependencies..."
    check_install_package "python3-pip"
    check_install_package "python3-venv"

    # Create virtual environment
    echo "Creating Python virtual environment..."
    cd "$APP_DIR"
    python3 -m venv venv
    . venv/bin/activate

    # Install requirements
    if [ -f "requirements.txt" ]; then
        pip install -r requirements.txt
    else
        # Install basic requirements based on detected framework
        if [ "$PYTHON_FRAMEWORK" = "fastapi" ]; then
            pip install fastapi uvicorn
        elif [ "$PYTHON_FRAMEWORK" = "flask" ]; then
            pip install flask gunicorn
        else
            pip install flask gunicorn
        fi
    fi

    # Create systemd service file
    echo "Creating systemd service file..."

    # Determine app file and command
    APP_FILE="app.py"
    if [ -f "$APP_DIR/main.py" ]; then
        APP_FILE="main.py"
    fi

    SERVICE_NAME=$(echo "$REPO_NAME" | tr '.' '-')

    if [ "$PYTHON_FRAMEWORK" = "fastapi" ]; then
        # FastAPI with Uvicorn
        cat >"/etc/systemd/system/$SERVICE_NAME.service" <<EOF
[Unit]
Description=Python FastAPI application
After=network.target

[Service]
User=$NGINX_USER
Group=$NGINX_USER
WorkingDirectory=$APP_DIR
ExecStart=$APP_DIR/venv/bin/uvicorn main:app --host 127.0.0.1 --port $API_PORT --workers 4
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=$SERVICE_NAME
Environment="PATH=$APP_DIR/venv/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

[Install]
WantedBy=multi-user.target
EOF
    else
        # Flask with Gunicorn (or generic)
        cat >"/etc/systemd/system/$SERVICE_NAME.service" <<EOF
[Unit]
Description=Python API application
After=network.target

[Service]
User=$NGINX_USER
Group=$NGINX_USER
WorkingDirectory=$APP_DIR
ExecStart=$APP_DIR/venv/bin/gunicorn --workers 4 --bind 127.0.0.1:$API_PORT app:app
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=$SERVICE_NAME
Environment="PATH=$APP_DIR/venv/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

[Install]
WantedBy=multi-user.target
EOF
    fi

    # Set proper permissions
    chown -R "$NGINX_USER:$NGINX_USER" "$APP_DIR"

    # Enable and start the service
    systemctl daemon-reload
    systemctl enable "$SERVICE_NAME"
    systemctl start "$SERVICE_NAME"

    echo "Python API service installed and started"
fi

# Step 9: Restart Nginx to apply changes
echo "===== Step 9: Finalizing setup ====="
systemctl restart nginx

# Check if Nginx is running properly
if ! systemctl is-active --quiet nginx; then
    echo "Warning: Nginx failed to start. Checking for errors..."
    nginx -t
    echo "Please fix the errors and restart Nginx manually with: systemctl restart nginx"
else
    echo "Nginx is running properly."
fi

# Add monitoring setup (optional)
echo "Do you want to set up basic monitoring with Prometheus and Grafana?"
if confirm "Set up monitoring"; then
    echo "Setting up monitoring tools..."
    # This would be implemented in a future version
    echo "Monitoring setup is not yet implemented in this version."
fi

# Final status report
echo ""
echo "===== Useful Commands ====="
echo "Check Nginx status: systemctl status nginx"
echo "Check Nginx configuration: nginx -t"
echo "View Nginx logs: tail -f /var/log/nginx/error.log"

if [ "$USE_CLOUDFLARE_TUNNEL" = "true" ]; then
    echo "Cloudflare Tunnel: Your application is accessible via https://$CF_TUNNEL_DOMAIN"
fi

if command_exists docker && [ -d "$APP_DIR" ]; then
    echo "View Docker containers: docker ps"
    echo "View Docker logs: docker logs <container_id>"
    echo "Restart Docker containers: cd $APP_DIR && docker compose restart"
    echo "Update application: cd $APP_DIR && git pull && docker compose down && docker compose up -d"
else
    echo "Check API service status: systemctl status $SERVICE_NAME"
    echo "View API logs: journalctl -u $SERVICE_NAME"
    echo "Restart API service: systemctl restart $SERVICE_NAME"
    echo "Update application: cd $APP_DIR && git pull && systemctl restart $SERVICE_NAME"
fi

echo ""
echo "Thank you for using the Docker Python API Web Server Setup Script!"
