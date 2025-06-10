#!/bin/bash

# SlimmerMetAI Dependency Cleanup Script
# Implementeert best practices voor node_modules beheer

echo "🧹 Starting dependency cleanup for SlimmerMetAI..."

# 1. Remove unused packages
echo "📦 Removing unused packages..."
npm prune

# 2. Check for outdated packages
echo "🔍 Checking for outdated packages..."
npm outdated

# 3. Audit for vulnerabilities
echo "🔒 Security audit..."
npm audit

# 4. Show disk usage before cleanup
echo "💾 Current node_modules size:"
du -sh node_modules/ 2>/dev/null || echo "node_modules not found"

# 5. Clean npm cache
echo "🗑️ Cleaning npm cache..."
npm cache clean --force

# 6. Reinstall with production flag test
echo "🚀 Testing production install (dry-run)..."
echo "Production dependencies only would be:"
npm ls --prod --depth=0

echo "✅ Cleanup completed!"
echo ""
echo "💡 Tips:"
echo "- Use 'npm ci' in CI/CD for faster, reliable builds"
echo "- Consider switching to pnpm for significant space savings"
echo "- Run 'npm prune' regularly to remove unused packages" 