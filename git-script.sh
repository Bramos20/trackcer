#!/bin/bash

# Check for commit message argument
if [ -z "$1" ]; then
  echo "❌ Error: No commit message provided."
  echo "Usage: ./git-script.sh \"Your commit message here\""
  exit 1
fi

# Display current git status
echo "📌 Checking git status..."
git status

# Add all changes
echo "➕ Adding changes..."
git add .

# Commit with provided message
echo "📝 Committing with message: $1"
git commit -m "$1"

# Push to the current branch
echo "🚀 Pushing to remote..."
git push

echo "✅ Done."