# Frontend Assets Structure

## Directory Organization

```
resources/
├── js/                    # JavaScript source files
│   ├── components/        # Reusable JS components
│   ├── legacy/           # Legacy code pending refactor
│   │   ├── quote-integration.js
│   │   └── quote-integration-simple.js
│   ├── app.js            # Main application entry
│   ├── bootstrap.js      # App initialization
│   ├── contract-clauses.js
│   ├── it-documentation-diagram.js
│   └── modal-system.js
├── css/                   # Stylesheets
│   ├── app.css           # Main application styles
│   ├── client-portal.css # Client portal specific
│   └── filament/         # Filament admin styles
└── views/                 # Blade templates
    └── ...               # Contains inline scripts

public/
├── build/                # Vite compiled assets (gitignored)
└── static-assets/        # Static files (images, fonts)
```

## Build Process

All JavaScript and CSS assets are processed through Vite:

```bash
# Development with hot reload
npm run dev

# Production build
npm run build
```

## Asset Guidelines

### DO:
- Place new JS modules in `resources/js/components/`
- Use Vite imports for dependencies
- Add new entry points to `vite.config.js`
- Use `@vite()` directive in Blade templates

### DON'T:
- Put JS files in `public/js/` (use Vite build)
- Create backup files (.bak, .old)
- Mix compiled and source assets
- Use inline scripts for complex logic

## Legacy Code

Files in `resources/js/legacy/` are pending refactor:
- `quote-integration.js` - Quote builder integration
- `quote-integration-simple.js` - Simplified quote functions

These will be refactored into modern ES6 modules.

## Vite Configuration

Entry points defined in `vite.config.js`:
- Main app bundle
- Contract management modules
- IT documentation tools
- Legacy integrations

## Blade Integration

In Blade templates, use:
```blade
@vite(['resources/js/app.js'])
```

For specific modules:
```blade
@vite(['resources/js/contract-clauses.js'])
```