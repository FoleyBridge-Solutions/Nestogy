# Auto-Merge Guide for Nestogy

## Enabling Auto-Merge on Pull Requests

Auto-merge is **enabled** for this repository. You can set PRs to automatically merge once all requirements are met.

### Via GitHub CLI

```bash
# Enable auto-merge with squash (recommended for feature branches)
gh pr merge <PR-NUMBER> --auto --squash

# Enable auto-merge with merge commit
gh pr merge <PR-NUMBER> --auto --merge

# Enable auto-merge with rebase
gh pr merge <PR-NUMBER> --auto --rebase
```

### Via GitHub Web UI

1. Open your PR on GitHub
2. Scroll down to the merge section
3. Click the dropdown arrow next to the merge button
4. Select "Enable auto-merge"
5. Choose your merge method
6. Confirm

### Requirements for Auto-Merge

The PR will automatically merge when:
- ✅ All CI checks pass (Tests & Coverage)
- ✅ CodeQL security scanning passes
- ✅ No merge conflicts
- ✅ Required reviews approved (if configured)
- ✅ Branch is up-to-date with main (if required)

### Canceling Auto-Merge

If you need to cancel auto-merge:
```bash
gh pr merge <PR-NUMBER> --disable-auto
```

Or click "Disable auto-merge" on the GitHub web UI.

### Best Practices

1. **Always enable auto-merge after creating a PR** to save time
2. **Use squash merge** for feature branches to keep history clean
3. **Ensure your branch is up-to-date** with main before enabling
4. **Add clear PR descriptions** so reviewers can approve quickly

### Example Workflow

```bash
# Create feature branch
git checkout -b feature/my-feature

# Make changes and commit
git add .
git commit -m "Add new feature"

# Push to GitHub
git push -u origin feature/my-feature

# Create PR
gh pr create --title "Add new feature" --body "Description..."

# Enable auto-merge immediately
gh pr merge --auto --squash

# PR will merge automatically once checks pass!
```