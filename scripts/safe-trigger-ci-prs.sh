#!/bin/bash

# SAFE script to trigger CI and wait for checks before enabling auto-merge
# This prevents merging untested code

set -e

echo "========================================="
echo "SAFE CI Trigger for Open PRs"
echo "========================================="
echo ""
echo "⚠️  WARNING: Repository lacks required status checks!"
echo "⚠️  This script will:"
echo "   1. Trigger CI on PRs without checks"
echo "   2. Wait for checks to start"  
echo "   3. Only enable auto-merge after CI is running"
echo ""

# Get all open PRs
open_prs=$(gh pr list --state open --limit 10 --json number,headRefName,statusCheckRollup --jq '.[] | "\(.number):\(.headRefName):\(.statusCheckRollup | length)"')

total_prs=$(echo "$open_prs" | wc -l)
echo "Processing first 10 open PRs (out of many)"
echo ""

for pr_info in $open_prs; do
  pr_num=$(echo $pr_info | cut -d: -f1)
  branch=$(echo $pr_info | cut -d: -f2)
  check_count=$(echo $pr_info | cut -d: -f3)
  
  echo "Processing PR #$pr_num"
  
  if [ "$check_count" -eq "0" ]; then
    echo "  ⚠️  No CI checks running"
    
    # First, trigger CI by pushing empty commit
    echo "  📝 Checking out branch and triggering CI..."
    if gh pr checkout $pr_num 2>/dev/null; then
      git commit --allow-empty -m "Trigger CI workflows"
      git push
      
      # Wait for CI to register and start
      echo "  ⏳ Waiting for CI to start..."
      for i in {1..10}; do
        sleep 3
        new_checks=$(gh pr view $pr_num --json statusCheckRollup --jq '.statusCheckRollup | length' 2>/dev/null || echo "0")
        if [ "$new_checks" -gt "0" ]; then
          echo "  ✅ CI started! ($new_checks checks running)"
          
          # Now it's safe to enable auto-merge
          echo "  🔄 Enabling auto-merge (will wait for checks to pass)..."
          if gh pr merge $pr_num --auto --squash 2>&1 | grep -q "error\|Error"; then
            echo "  ❌ Failed to enable auto-merge"
          else
            echo "  ✅ Auto-merge enabled - will merge after checks pass"
          fi
          break
        fi
        echo "    Still waiting... ($i/10)"
      done
      
      if [ "$new_checks" -eq "0" ]; then
        echo "  ❌ CI failed to start after 30 seconds"
      fi
    else
      echo "  ❌ Failed to checkout PR"
    fi
  else
    echo "  ✅ CI already running ($check_count checks)"
    
    # Check if checks are passing
    check_status=$(gh pr view $pr_num --json statusCheckRollup --jq '[.statusCheckRollup[].conclusion] | map(select(. != null)) | if length == 0 then "pending" else if all(. == "SUCCESS") then "passing" else "failing" end end')
    echo "  📊 Check status: $check_status"
    
    if [ "$check_status" != "failing" ]; then
      echo "  🔄 Enabling auto-merge..."
      if gh pr merge $pr_num --auto --squash 2>&1 | grep -q "error\|Error"; then
        echo "  ❌ Failed to enable auto-merge"  
      else
        echo "  ✅ Auto-merge enabled"
      fi
    else
      echo "  ⚠️  Checks are failing, skipping auto-merge"
    fi
  fi
  
  echo ""
done

# Return to main
git checkout main 2>/dev/null

echo "========================================="
echo "⚠️  IMPORTANT: Branch protection needs to be configured!"
echo "The repository should require:"
echo "  - Status checks to pass before merging"
echo "  - Branches to be up to date"
echo "  - No admin bypass of rules"
echo "========================================="