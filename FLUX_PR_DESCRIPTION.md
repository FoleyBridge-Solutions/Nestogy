# Fix: Remove @pure directive from file input component

## Problem
The `@pure` directive on the file input component causes Blade compilation errors when used with Livewire Blaze:

```
InvalidArgumentException: Cannot end a section without first starting one.
(View: vendor/livewire/flux/stubs/resources/views/flux/input/file.blade.php)
```

## Root Cause
The file input component contains a **nested Flux component** that may not be pure:

```blade
<flux:button as="div" class="cursor-pointer" :$size aria-hidden="true">
```

Per Blaze's documentation:
> Components hardcoded in the template must be pure for the parent to be @pure

The component also contains elements that cannot be safely optimized at compile-time:

1. **Nested component** - `<flux:button>` must also be @pure (unclear if it is)
2. **Translation helpers** - `{!! __('Choose files') !!}` need runtime evaluation  
3. **PHP conditionals** - `<?php if ($multiple) : ?>` requires runtime context
4. **Dynamic attributes** - `wire:model` binding needs runtime evaluation

Blaze attempts to pre-render components marked with `@pure` at compile-time, but these dynamic elements cause the Blade compiler to produce invalid output.

## Solution
Remove the `@pure` directive from this component. The component should render normally at runtime.

### Why this component can't be pure:
- ❌ Translation strings change per locale (runtime-dependent)
- ❌ Multiple file selection is conditional (runtime logic)
- ❌ Wire model binding is dynamic (runtime attribute)
- ❌ File name display updates on change (runtime state)

Per Blaze's documentation:
> Only add @pure to components that render the same way every time they're compiled.
> Components should only use the props you pass in (no session data, no database queries)

This component violates these requirements due to translation helpers and dynamic state.

## Testing
- ✅ All Flux components render correctly
- ✅ File input functionality unchanged  
- ✅ No breaking changes
- ✅ Compatible with Blaze installed (no errors)
- ✅ Compatible with Blaze not installed (works as before)

## Impact
- **Before**: Component fails to render when Blaze is installed
- **After**: Component renders correctly with or without Blaze
- **Performance**: Minimal impact - this component was never successfully optimized anyway

## Related
- Livewire Blaze: https://github.com/livewire/blaze
- Blaze @pure docs: https://github.com/livewire/blaze#when-to-use-pure

---

### Files Changed
- `stubs/resources/views/flux/input/file.blade.php` - Removed `@pure` directive

### Checklist
- [x] Tests pass
- [x] No breaking changes
- [x] Component functionality unchanged
- [x] Works with and without Blaze installed
