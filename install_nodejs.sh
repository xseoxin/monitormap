#!/bin/bash
#
# Node.js Installation Helper for Google Maps Monitor
# Run this script on your server via SSH
#

echo "================================================"
echo "Node.js Installation Helper"
echo "================================================"
echo ""

# Check if Node.js is already installed
echo "[1/5] Checking if Node.js is installed..."
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo "✅ Node.js is already installed: $NODE_VERSION"
    NODE_INSTALLED=1
else
    echo "❌ Node.js is NOT installed"
    NODE_INSTALLED=0
fi

# Check if npm is installed
echo ""
echo "[2/5] Checking if npm is installed..."
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm --version)
    echo "✅ npm is already installed: $NPM_VERSION"
    NPM_INSTALLED=1
else
    echo "❌ npm is NOT installed"
    NPM_INSTALLED=0
fi

# If not installed, try to install
if [ $NODE_INSTALLED -eq 0 ] || [ $NPM_INSTALLED -eq 0 ]; then
    echo ""
    echo "[3/5] Node.js/npm not found. Attempting installation..."

    # Check if we can use package manager
    if command -v apt-get &> /dev/null; then
        echo "Detected: Debian/Ubuntu system"
        echo "Installing Node.js via apt..."

        # Update package list
        sudo apt-get update

        # Install Node.js (will install both node and npm)
        sudo apt-get install -y nodejs npm

    elif command -v yum &> /dev/null; then
        echo "Detected: CentOS/RHEL system"
        echo "Installing Node.js via yum..."

        # Enable EPEL repository
        sudo yum install -y epel-release

        # Install Node.js
        sudo yum install -y nodejs npm

    else
        echo "⚠️  Could not detect package manager"
        echo ""
        echo "Please install Node.js manually:"
        echo ""
        echo "Option 1: Using NVM (Node Version Manager) - RECOMMENDED"
        echo "  curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash"
        echo "  source ~/.bashrc"
        echo "  nvm install 18"
        echo ""
        echo "Option 2: Download from nodejs.org"
        echo "  Visit: https://nodejs.org/en/download/"
        echo ""
        exit 1
    fi

    # Verify installation
    if command -v node &> /dev/null && command -v npm &> /dev/null; then
        NODE_VERSION=$(node --version)
        NPM_VERSION=$(npm --version)
        echo "✅ Node.js installed successfully: $NODE_VERSION"
        echo "✅ npm installed successfully: $NPM_VERSION"
    else
        echo "❌ Installation failed. Please install manually."
        exit 1
    fi
fi

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
SCRAPER_DIR="$SCRIPT_DIR/scraper"

echo ""
echo "[4/5] Installing npm dependencies in scraper directory..."

# Check if scraper directory exists
if [ ! -d "$SCRAPER_DIR" ]; then
    echo "❌ Scraper directory not found: $SCRAPER_DIR"
    echo "Please run this script from the monitormap root directory"
    exit 1
fi

# Navigate to scraper directory
cd "$SCRAPER_DIR"

# Check if package.json exists
if [ ! -f "package.json" ]; then
    echo "❌ package.json not found in scraper directory"
    exit 1
fi

# Install dependencies
echo "Installing dependencies (this may take a few minutes)..."
npm install

if [ $? -eq 0 ]; then
    echo "✅ npm dependencies installed successfully!"
else
    echo "❌ npm install failed"
    exit 1
fi

# Update NODE_PATH in .env if needed
echo ""
echo "[5/5] Updating .env with correct Node.js path..."

ENV_FILE="$SCRIPT_DIR/.env"
NODE_PATH=$(which node)

if [ -f "$ENV_FILE" ]; then
    # Check if NODE_PATH exists in .env
    if grep -q "NODE_PATH=" "$ENV_FILE"; then
        # Update existing NODE_PATH
        sed -i.bak "s|NODE_PATH=.*|NODE_PATH=$NODE_PATH|g" "$ENV_FILE"
        echo "✅ Updated NODE_PATH in .env to: $NODE_PATH"
    else
        # Add NODE_PATH
        echo "NODE_PATH=$NODE_PATH" >> "$ENV_FILE"
        echo "✅ Added NODE_PATH to .env: $NODE_PATH"
    fi
else
    echo "⚠️  .env file not found. Please create it manually."
fi

# Summary
echo ""
echo "================================================"
echo "✅ INSTALLATION COMPLETE!"
echo "================================================"
echo ""
echo "Installed versions:"
echo "  Node.js: $(node --version)"
echo "  npm: $(npm --version)"
echo ""
echo "Node.js path: $(which node)"
echo "Scraper directory: $SCRAPER_DIR"
echo ""
echo "Next steps:"
echo "1. ✅ Complete the web installation (install.php)"
echo "2. ✅ Configure cron jobs for automatic scanning"
echo "3. ✅ Add proxies in the Proxies section"
echo "4. ✅ Start adding phrases to monitor"
echo ""
echo "To test the scraper manually:"
echo "  node $SCRAPER_DIR/scraper.js --phrase-id=1 --scan-id=1"
echo ""
echo "================================================"
