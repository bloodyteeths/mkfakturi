#!/bin/bash

# Script to create a GitHub repository for the roadmap3 implementation
# Run this script after manually creating the repository on GitHub

echo "🚀 Creating GitHub repository for Roadmap3 Implementation"
echo "========================================================"
echo ""

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: Please run this script from the mkaccounting-roadmap3 directory"
    exit 1
fi

# Get repository name from user
read -p "Enter your GitHub username: " GITHUB_USERNAME
read -p "Enter repository name (default: mkaccounting-roadmap3): " REPO_NAME
REPO_NAME=${REPO_NAME:-mkaccounting-roadmap3}

echo ""
echo "📋 Repository Details:"
echo "Username: $GITHUB_USERNAME"
echo "Repository: $REPO_NAME"
echo ""

# Instructions for manual repository creation
echo "📝 Manual Steps Required:"
echo "1. Go to https://github.com/new"
echo "2. Repository name: $REPO_NAME"
echo "3. Description: MK Accounting - Roadmap3 Implementation with Multiagent Development"
echo "4. Make it Public or Private (your choice)"
echo "5. DO NOT initialize with README (we already have one)"
echo "6. Click 'Create repository'"
echo ""

read -p "Press Enter after creating the repository on GitHub..."

# Add remote origin
echo "🔗 Adding remote origin..."
git remote add origin https://github.com/$GITHUB_USERNAME/$REPO_NAME.git

# Push to GitHub
echo "📤 Pushing to GitHub..."
git branch -M main
git push -u origin main

echo ""
echo "✅ Repository created and pushed successfully!"
echo "🌐 View your repository at: https://github.com/$GITHUB_USERNAME/$REPO_NAME"
echo ""
echo "📊 Repository Statistics:"
echo "- Files: $(find . -type f | wc -l | tr -d ' ')"
echo "- Lines of code: $(find . -name "*.php" -o -name "*.js" -o -name "*.vue" | xargs wc -l | tail -1 | awk '{print $1}')"
echo "- Commits: $(git log --oneline | wc -l | tr -d ' ')"
echo ""
echo "🎉 Roadmap3 implementation is now live on GitHub!" 