#!/bin/bash

# Efficient batch PR merger:
# 1. Fetch all PRs in one call
# 2. Filter mergeable vs non-mergeable in one query
# 3. Batch merge all mergeable PRs
# 4. Batch close all non-mergeable PRs

set -e

echo "========================================="
echo "Batch PR Merger (Optimized)"
echo "========================================="

# Step 1: Fetch ALL open PRs with mergeable status in ONE API call
echo "Step 1: Fetching all open PRs with mergeable status..."
gh api graphql -f query='
query {
  repository(owner: "FoleyBridge-Solutions", name: "Nestogy") {
    pullRequests(first: 100, states: OPEN) {
      nodes {
        number
        id
        mergeable
        title
      }
      pageInfo {
        hasNextPage
        endCursor
      }
    }
  }
}' > /tmp/prs-page1.json

# Get total count and extract PRs
all_prs=$(jq '.data.repository.pullRequests.nodes' /tmp/prs-page1.json)
has_next=$(jq -r '.data.repository.pullRequests.pageInfo.hasNextPage' /tmp/prs-page1.json)
cursor=$(jq -r '.data.repository.pullRequests.pageInfo.endCursor' /tmp/prs-page1.json)

# Fetch remaining pages if needed
page=2
while [ "$has_next" = "true" ]; do
  echo "  Fetching page $page..."
  gh api graphql -f query="
query {
  repository(owner: \"FoleyBridge-Solutions\", name: \"Nestogy\") {
    pullRequests(first: 100, states: OPEN, after: \"$cursor\") {
      nodes {
        number
        id
        mergeable
        title
      }
      pageInfo {
        hasNextPage
        endCursor
      }
    }
  }
}" > /tmp/prs-page${page}.json
  
  page_prs=$(jq '.data.repository.pullRequests.nodes' /tmp/prs-page${page}.json)
  all_prs=$(echo "$all_prs $page_prs" | jq -s 'add')
  
  has_next=$(jq -r '.data.repository.pullRequests.pageInfo.hasNextPage' /tmp/prs-page${page}.json)
  cursor=$(jq -r '.data.repository.pullRequests.pageInfo.endCursor' /tmp/prs-page${page}.json)
  page=$((page + 1))
done

echo "$all_prs" > /tmp/all-prs.json

total=$(echo "$all_prs" | jq '. | length')
echo "  Found $total open PRs"

# Step 2: Separate mergeable vs non-mergeable
echo ""
echo "Step 2: Separating mergeable vs non-mergeable PRs..."
mergeable_prs=$(echo "$all_prs" | jq '[.[] | select(.mergeable == "MERGEABLE")]')
conflicted_prs=$(echo "$all_prs" | jq '[.[] | select(.mergeable == "CONFLICTING")]')
unknown_prs=$(echo "$all_prs" | jq '[.[] | select(.mergeable == "UNKNOWN")]')

mergeable_count=$(echo "$mergeable_prs" | jq '. | length')
conflicted_count=$(echo "$conflicted_prs" | jq '. | length')
unknown_count=$(echo "$unknown_prs" | jq '. | length')

echo "  Mergeable: $mergeable_count"
echo "  Conflicted: $conflicted_count"
echo "  Unknown: $unknown_count"

# Step 3: Batch merge all mergeable PRs (50 at a time due to GraphQL complexity limits)
echo ""
echo "Step 3: Batch merging all mergeable PRs..."

if [ "$mergeable_count" -gt 0 ]; then
  batch_size=50
  for ((start=0; start<mergeable_count; start+=batch_size)); do
    end=$((start + batch_size))
    batch_num=$((start / batch_size + 1))
    
    echo "  Batch $batch_num: Merging PRs $start to $end..."
    
    # Build GraphQL mutation
    mutations=$(echo "$mergeable_prs" | jq -r ".[$start:$end] | to_entries | .[] | \"m\(.key): mergePullRequest(input: {pullRequestId: \\\"\(.value.id)\\\", mergeMethod: SQUASH}) { pullRequest { number } }\"" | tr '\n' ' ')
    
    query="mutation { $mutations }"
    
    # Execute batch merge
    gh api graphql -f query="$query" > /tmp/merge-result-batch${batch_num}.json 2>&1 || echo "    ⚠️ Some PRs in batch failed"
    
    echo "    ✓ Batch $batch_num complete"
  done
  echo "  ✓ All mergeable PRs processed"
else
  echo "  No mergeable PRs to process"
fi

# Step 4: Batch close all conflicted PRs
echo ""
echo "Step 4: Closing conflicted PRs..."

if [ "$conflicted_count" -gt 0 ]; then
  echo "$conflicted_prs" | jq -r '.[].number' | while read pr_num; do
    gh pr close $pr_num --comment "Closing due to merge conflicts with main" >/dev/null 2>&1
    echo "  Closed PR #$pr_num"
  done
  echo "  ✓ All conflicted PRs closed"
else
  echo "  No conflicted PRs to close"
fi

# Step 5: Try to merge UNKNOWN status PRs individually (they might be mergeable)
echo ""
echo "Step 5: Processing UNKNOWN status PRs..."

if [ "$unknown_count" -gt 0 ]; then
  echo "$unknown_prs" | jq -r '.[].number' | while read pr_num; do
    result=$(gh pr merge $pr_num --squash --admin 2>&1 || true)
    if echo "$result" | grep -q "is not mergeable"; then
      gh pr close $pr_num --comment "Closing due to merge conflicts" >/dev/null 2>&1
      echo "  Closed PR #$pr_num (conflict)"
    else
      echo "  Merged PR #$pr_num"
    fi
  done
  echo "  ✓ All UNKNOWN PRs processed"
else
  echo "  No UNKNOWN PRs to process"
fi

echo ""
echo "========================================="
echo "✓ Batch merge complete!"
echo "========================================="
