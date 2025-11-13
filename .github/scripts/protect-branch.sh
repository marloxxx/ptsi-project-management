#!/bin/bash

# Script to protect master/main branch in GitHub
# Usage: ./protect-branch.sh [branch-name]
# Example: ./protect-branch.sh master

set -e

BRANCH="${1:-master}"
REPO="${GITHUB_REPOSITORY:-$(git config --get remote.origin.url | sed 's/.*github.com[:/]\(.*\)\.git/\1/')}"

if [ -z "$REPO" ]; then
    echo "Error: Could not determine repository. Set GITHUB_REPOSITORY or ensure git remote is configured."
    exit 1
fi

echo "Protecting branch: $BRANCH in repository: $REPO"
echo ""

# Check if gh CLI is installed
if ! command -v gh &> /dev/null; then
    echo "Error: GitHub CLI (gh) is not installed."
    echo "Install it from: https://cli.github.com/"
    exit 1
fi

# Check if authenticated
if ! gh auth status &> /dev/null; then
    echo "Error: Not authenticated with GitHub CLI."
    echo "Run: gh auth login"
    exit 1
fi

echo "Setting up branch protection rules..."

# Create temporary JSON file for protection settings
TMP_FILE=$(mktemp)
cat > "$TMP_FILE" <<EOF
{
  "required_status_checks": {
    "strict": true,
    "contexts": [
      "Laravel Pint",
      "PHPStan",
      "PHPUnit Tests",
      "Composer Security Audit"
    ]
  },
  "enforce_admins": true,
  "required_pull_request_reviews": {
    "required_approving_review_count": 1,
    "dismiss_stale_reviews": true,
    "require_code_owner_reviews": true,
    "require_last_push_approval": false
  },
  "restrictions": null,
  "required_linear_history": true,
  "allow_force_pushes": false,
  "allow_deletions": false,
  "block_creations": false,
  "required_conversation_resolution": true,
  "lock_branch": false,
  "allow_fork_syncing": true
}
EOF

# Protect the branch using JSON file
gh api repos/$REPO/branches/$BRANCH/protection \
  --method PUT \
  --input "$TMP_FILE"

# Clean up temporary file
rm -f "$TMP_FILE"

echo ""
echo "✅ Branch protection rules applied successfully!"
echo ""
echo "Branch Protection Settings:"
echo "  - Require pull request reviews: 1 approval"
echo "  - Require code owner reviews: Yes"
echo "  - Dismiss stale reviews: Yes"
echo "  - Require status checks: Laravel Pint, PHPStan, PHPUnit Tests, Composer Security Audit"
echo "  - Require branches to be up to date: Yes"
echo "  - Require linear history: Yes"
echo "  - Require conversation resolution: Yes"
echo "  - Allow force pushes: No"
echo "  - Allow deletions: No"
echo "  - Enforce admins: Yes"
echo ""
echo "To view protection rules: gh api repos/$REPO/branches/$BRANCH/protection"

