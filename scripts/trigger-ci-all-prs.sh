#!/bin/bash

# Script to trigger CI on all open PRs and enable auto-merge
# This fixes PRs that were created but never had CI triggered

set -e

echo "========================================="
echo "Triggering CI and Auto-merge for all PRs"
echo "========================================="
echo ""

# Get all open PRs
open_prs=$(gh pr list --state open --limit 100 --json number,headRefName --jq '.[] | "\(.number):\(.headRefName)"')

total_prs=$(echo "$open_prs" | wc -l)
echo "Found $total_prs open PRs"
echo ""

processed=0
triggered=0
automerge_enabled=0
already_running=0

for pr_info in $open_prs; do
  pr_num=$(echo $pr_info | cut -d: -f1)
  branch=$(echo $pr_info | cut -d: -f2)
  
  echo "[$((processed + 1))/$total_prs] Processing PR #$pr_num (branch: $branch)"
  
  # Check if CI is already running
  checks=$(gh pr view $pr_num --json statusCheckRollup --jq '.statusCheckRollup | length' 2>/dev/null || echo "0")
  
  if [ "$checks" -eq 0 ]; then
    echo "  ⚠️  No CI checks running, triggering..."
    
    # Checkout the PR branch
    if gh pr checkout $pr_num 2>/dev/null; then
      # Push empty commit to trigger CI
      git commit --allow-empty -m "Trigger CI workflows" 
      git push
      triggered=$((triggered + 1))
      
      # Wait for CI to register
      sleep 3
      
      # Enable auto-merge
      echo "  🔄 Enabling auto-merge..."
      if gh pr merge $pr_num --auto --squash 2>&1 | grep -q "error\|Error"; then
        echo "  ❌ Failed to enable auto-merge"
      else
        echo "  ✅ Auto-merge enabled successfully"
        automerge_enabled=$((automerge_enabled + 1))
      fi
    else
      echo "  ❌ Failed to checkout PR"
    fi
  else
    echo "  ✅ CI already running ($checks checks)"
    already_running=$((already_running + 1))
    
    # Try to enable auto-merge anyway
    echo "  🔄 Enabling auto-merge..."
    if gh pr merge $pr_num --auto --squash 2>&1 | grep -q "error\|Error"; then
      echo "  ❌ Failed to enable auto-merge"
    else
      echo "  ✅ Auto-merge enabled successfully"
      automerge_enabled=$((automerge_enabled + 1))
    fi
  fi
  
  processed=$((processed + 1))
  echo ""
done

# Go back to main branch
echo "Switching back to main branch..."
git checkout main 2>/dev/null

echo ""
echo "========================================="
echo "Summary"
echo "========================================="
echo "Total PRs processed: $processed"
echo "CI triggered: $triggered"
echo "CI already running: $already_running"
echo "Auto-merge enabled: $automerge_enabled"
echo ""
echo "PRs will automatically merge once CI checks pass!"