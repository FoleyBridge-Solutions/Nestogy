# Branding & Color Customization

Nestogy supports comprehensive company branding customization, allowing each MSP to match their corporate identity while maintaining a consistent, professional interface.

## Overview

The branding system provides:
- **Multi-tenant color customization** - Each company can have unique colors
- **Live preview** - See changes instantly without page refresh
- **Preset themes** - Quick setup with professional color schemes
- **Blue default** - Falls back to professional blue theme
- **CSS custom properties** - Modern, performant implementation
- **Cached rendering** - Optimized for production performance

## Color System Architecture

### CSS Custom Properties
Colors are implemented using CSS custom properties (CSS variables) for maximum flexibility:

```css
:root {
  --primary-50: #eff6ff;   /* Lightest shade */
  --primary-500: #3b82f6;  /* Main brand color */
  --primary-600: #2563eb;  /* Hover state */
  --primary-700: #1d4ed8;  /* Active/pressed state */
  --primary-900: #1e3a8a;  /* Darkest shade */
}
```

### Tailwind Integration
The system extends Tailwind CSS with custom color tokens:

```javascript
// tailwind.config.js
colors: {
  primary: {
    50: 'var(--primary-50)',
    500: 'var(--primary-500)',
    600: 'var(--primary-600)',
    // ... full palette
  }
}
```

## Database Structure

### Company Customizations Table
```sql
CREATE TABLE company_customizations (
    id BIGINT PRIMARY KEY,
    company_id BIGINT UNIQUE REFERENCES companies(id),
    customizations JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### JSON Structure
```json
{
  "colors": {
    "primary": {
      "50": "#eff6ff",
      "500": "#3b82f6",
      "600": "#2563eb",
      "700": "#1d4ed8"
    },
    "secondary": {
      "500": "#6b7280",
      "600": "#4b5563"
    }
  }
}
```

## Available Color Presets

### Blue (Default)
- **Primary 500**: `#3b82f6` 
- **Use case**: Professional, trustworthy, default choice
- **Industries**: General MSP, technology services

### Green
- **Primary 500**: `#10b981`
- **Use case**: Growth, sustainability, healthcare IT
- **Industries**: Healthcare MSP, environmental tech

### Purple  
- **Primary 500**: `#a855f7`
- **Use case**: Creative, innovative, premium services
- **Industries**: Creative agencies, premium consulting

### Red
- **Primary 500**: `#ef4444`
- **Use case**: Urgent, critical, security-focused
- **Industries**: Security MSP, emergency services

### Orange
- **Primary 500**: `#f97316`
- **Use case**: Energy, enthusiasm, construction/industrial
- **Industries**: Industrial MSP, construction tech

## Implementation Guide

### For Developers

#### Using Semantic Color Classes
Always use semantic color classes instead of hardcoded colors:

```html
<!-- ✅ Good - Uses semantic classes -->
<button class="bg-primary-500 hover:bg-primary-600 text-white">
  Save Changes
</button>

<!-- ❌ Bad - Hardcoded blue -->
<button class="bg-blue-500 hover:bg-blue-600 text-white">
  Save Changes
</button>
```

#### Common Color Classes
- **Buttons**: `bg-primary-500 hover:bg-primary-600`
- **Links**: `text-primary-600 hover:text-primary-700`
- **Borders**: `border-primary-500`
- **Badges**: `bg-primary-100 text-primary-800`
- **Focus states**: `focus:ring-primary-500`

#### Adding New Components
When creating new UI components, follow these guidelines:

1. **Use primary colors** for main actions and branding elements
2. **Use secondary colors** for supporting elements
3. **Provide hover/active states** using darker shades
4. **Include focus indicators** for accessibility

```php
// Example: New button component
<button class="px-4 py-2 bg-primary-500 hover:bg-primary-600 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 text-white rounded-md transition-colors">
    {{ $slot }}
</button>
```

### For Administrators

#### Accessing Color Settings
1. Navigate to **Settings > General**
2. Click the **"Branding & Colors"** tab
3. Choose from presets or customize individual colors

#### Applying Preset Themes
1. Select a preset from the color palette
2. Preview changes in real-time
3. Click **"Apply Preset"** to save

#### Custom Color Configuration
1. Use color pickers to adjust individual shades
2. Enter hex codes directly for precise colors
3. Monitor the live preview panel
4. Click **"Save Colors"** when satisfied

#### Best Practices
- **Test accessibility** - Ensure sufficient contrast ratios
- **Preview thoroughly** - Check multiple pages before finalizing
- **Consider brand guidelines** - Match existing corporate colors
- **Document choices** - Keep a record of chosen colors for consistency

## API Reference

### Color Management Endpoints

#### Update Colors
```
PUT /settings/colors
Content-Type: application/json

{
  "colors": {
    "primary": {
      "500": "#3b82f6",
      "600": "#2563eb"
    }
  }
}
```

#### Apply Preset
```
POST /settings/colors/preset
Content-Type: application/json

{
  "preset": "green"
}
```

#### Reset to Default
```
POST /settings/colors/reset
```

### Service Layer

#### SettingsService Methods
```php
// Get company colors with fallbacks
$colors = $settingsService->getCompanyColors($company);

// Update colors
$settingsService->updateCompanyColors($company, $colorArray);

// Apply preset
$settingsService->applyColorPreset($company, 'green');

// Generate CSS
$css = $settingsService->generateCompanyCss($company);
```

#### Model Methods
```php
// CompanyCustomization model
$customization = $company->customization;
$color = $customization->getColor('primary.500', '#3b82f6');
$css = $customization->getCssCustomProperties();
```

## Performance Considerations

### Caching Strategy
- **Company CSS cached** for 24 hours
- **Customization data cached** for 1 hour  
- **Cache invalidation** on color updates
- **Production optimization** with Redis/Memcached

### Loading Performance
- **CSS injected inline** for fastest rendering
- **No additional HTTP requests** for custom colors
- **Minimal overhead** - only 10-20 CSS custom properties
- **Graceful fallbacks** to blue theme if customization fails

## Browser Support

### CSS Custom Properties
- **Chrome/Edge**: Full support (latest versions)
- **Firefox**: Full support (latest versions)
- **Safari**: Full support (latest versions)
- **IE11**: Fallback to blue theme (not supported)

### Fallback Strategy
```css
/* Fallback for older browsers */
.button {
  background-color: #3b82f6; /* Fallback blue */
  background-color: var(--primary-500); /* Custom property */
}
```

## Security Considerations

### Input Validation
- **Hex color validation** - Only valid 6-digit hex codes accepted
- **XSS prevention** - All colors sanitized before injection
- **SQL injection protection** - JSON fields properly escaped
- **CSRF protection** - All update endpoints protected

### Multi-tenancy Security
- **Company isolation** - Colors scoped by company_id
- **Authorization checks** - Only company users can modify colors
- **Audit logging** - Color changes tracked in activity log

## Troubleshooting

### Common Issues

#### Colors Not Applying
1. **Check browser console** for JavaScript errors
2. **Verify cache cleared** after updates
3. **Confirm valid hex colors** in customization
4. **Test with different browser**

#### Performance Issues  
1. **Check cache configuration** (Redis/Memcached)
2. **Monitor CSS generation time**
3. **Verify database query performance**
4. **Consider CDN for static assets**

#### Fallback Not Working
1. **Verify default colors** in CSS file
2. **Check CSS custom property syntax**
3. **Test browser compatibility**
4. **Validate Tailwind configuration**

### Debug Commands
```bash
# Clear all caches
php artisan cache:clear

# Rebuild CSS assets
npm run build

# Check company customization
php artisan tinker
>>> Company::find(1)->customization->getColors();
```

## Future Enhancements

### Planned Features
- **Logo customization** - Upload company logos
- **Font selection** - Choose from curated font library
- **Advanced theming** - Dark mode, custom layouts
- **Brand kit export** - Download color palette files
- **A11y checker** - Automated accessibility validation

### Extensibility
The system is designed for easy extension:
- **Additional color schemes** can be added to presets
- **New customization types** fit into existing JSON structure
- **Custom CSS properties** can be added for new features
- **API endpoints** follow RESTful conventions

## Support

For technical support or feature requests related to branding customization:

1. **Documentation**: Refer to this guide first
2. **Code Review**: Check implementation in `app/Models/CompanyCustomization.php`
3. **Testing**: Use the built-in settings interface
4. **Issues**: Report bugs through your preferred issue tracking system

---

**Last Updated**: August 2025  
**Version**: 1.0  
**Compatibility**: Laravel 12.x, Tailwind CSS 3.x