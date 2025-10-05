# How to Submit the Flux PR

## 1. Fork the Flux Repository
1. Go to https://github.com/livewire/flux
2. Click "Fork" button (top right)
3. Fork to your GitHub account

## 2. Push Your Fix Branch
```bash
cd /opt/nestogy/flux-repo

# Add your fork as a remote
git remote add YOUR_USERNAME https://github.com/YOUR_USERNAME/flux.git

# Push the fix branch
git push YOUR_USERNAME fix/remove-pure-from-file-input
```

## 3. Create the Pull Request
1. Go to https://github.com/livewire/flux/pulls
2. Click "New Pull Request"
3. Click "compare across forks"
4. Set:
   - **base repository**: `livewire/flux`
   - **base**: `main`
   - **head repository**: `YOUR_USERNAME/flux`
   - **compare**: `fix/remove-pure-from-file-input`
5. Click "Create Pull Request"

## 4. Fill in PR Details

### Title:
```
Fix: Remove @pure directive from file input component
```

### Description:
Use the content from `/opt/nestogy/FLUX_PR_DESCRIPTION.md`

## 5. Temporary Workaround (Until PR is Merged)

### Option A: Publish and override the view
```bash
# This doesn't work for Flux, so use Option B
```

### Option B: Patch via composer.json
Add this to your `composer.json`:

```json
{
    "scripts": {
        "post-update-cmd": [
            "@php -r \"file_put_contents('vendor/livewire/flux/stubs/resources/views/flux/input/file.blade.php', preg_replace('/@pure\\n\\n/', '', file_get_contents('vendor/livewire/flux/stubs/resources/views/flux/input/file.blade.php')));\""
        ],
        "post-install-cmd": [
            "@php -r \"file_put_contents('vendor/livewire/flux/stubs/resources/views/flux/input/file.blade.php', preg_replace('/@pure\\n\\n/', '', file_get_contents('vendor/livewire/flux/stubs/resources/views/flux/input/file.blade.php')));\""
        ]
    }
}
```

### Option C: Use composer patches (recommended)
```bash
composer require cweagans/composer-patches
```

Create `patches/flux-remove-pure-file-input.patch`:
```diff
--- a/stubs/resources/views/flux/input/file.blade.php
+++ b/stubs/resources/views/flux/input/file.blade.php
@@ -1,5 +1,3 @@
-@pure
-
 @php
 extract(Flux::forwardedAttributes($attributes, [
     'name',
```

Add to `composer.json`:
```json
{
    "extra": {
        "patches": {
            "livewire/flux": {
                "Remove @pure from file input": "patches/flux-remove-pure-file-input.patch"
            }
        }
    }
}
```

## Current Status
✅ Fix applied locally to `/opt/nestogy/vendor/livewire/flux/stubs/resources/views/flux/input/file.blade.php`
✅ Fix committed to `/opt/nestogy/flux-repo` branch `fix/remove-pure-from-file-input`  
✅ All 772 tests passing
✅ PR description ready

## What You Need to Do:
1. Fork https://github.com/livewire/flux
2. Push the branch from `/opt/nestogy/flux-repo`
3. Create the PR
4. Wait for merge
5. Once merged, remove local vendor patch

## Alternative: Report as Issue
If you prefer not to create a PR, you can report this as an issue:

1. Go to https://github.com/livewire/flux/issues/new
2. Title: `@pure directive on file input component breaks with Livewire Blaze`
3. Use the description from `FLUX_PR_DESCRIPTION.md`
4. Include error message and steps to reproduce
