# Nginx Configuration for Nestogy ERP

## Problem: 400 Bad Request - Request Header Or Cookie Too Large

This error occurs when nginx's default buffer sizes are too small to handle the application's cookies and headers.

## Solution

### Option 1: Using the provided nginx.conf

If you have access to your nginx server configuration:

1. Include the provided `nginx.conf` in your server block:
```nginx
server {
    # ... your existing configuration ...
    
    include /var/www/html/nginx.conf;
    
    # ... rest of configuration ...
}
```

2. Test and reload nginx:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### Option 2: Manual Configuration

Add these directives to your nginx server block or http block:

```nginx
# Increase buffer sizes
client_header_buffer_size 16k;
large_client_header_buffers 4 32k;
client_max_body_size 100M;

# FastCGI buffers
fastcgi_buffer_size 128k;
fastcgi_buffers 4 256k;
fastcgi_busy_buffers_size 256k;
```

### Option 3: Laravel Cloud/Forge

If using Laravel Cloud or Forge:

1. Go to your server's **Nginx Configuration**
2. Add the buffer size directives to the server block
3. Click **Update Configuration**

Example:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    # Add these lines
    client_header_buffer_size 16k;
    large_client_header_buffers 4 32k;
    
    root /home/forge/your-site/public;
    # ... rest of config ...
}
```

### Option 4: Platform.sh

Create or update `.platform/routes.yaml`:

```yaml
"https://{default}/":
    type: upstream
    upstream: "app:http"
    cache:
        enabled: true
        cookies: ['*']
        headers: ['Accept', 'Accept-Language']
    client_max_body_size: 100M
```

And update `.platform.app.yaml`:

```yaml
web:
    locations:
        "/":
            passthru: "/index.php"
            root: "public"
            expires: 1h
            allow: true
            rules:
                # Increase buffer sizes
                \\.php$:
                    allow: false
                    scripts: true
                    passthru: "/index.php"
```

### Option 5: Docker/Kubernetes

If using Docker, update your nginx configuration in your Dockerfile or ConfigMap:

```dockerfile
# Add to nginx.conf or default.conf
RUN echo "client_header_buffer_size 16k;" >> /etc/nginx/conf.d/default.conf && \
    echo "large_client_header_buffers 4 32k;" >> /etc/nginx/conf.d/default.conf
```

## Application-Level Fixes

You can also reduce session data size:

### 1. Use Database Sessions (Already Configured)
Your `config/session.php` already uses database driver, which is good.

### 2. Reduce Session Encryption Overhead (Optional)
In `config/session.php`, you can disable encryption if using HTTPS:
```php
'encrypt' => env('SESSION_ENCRYPT', false),
```

### 3. Clean Up Large Session Data
Add to your middleware or controller:
```php
// Remove unnecessary session data
session()->forget('large_data_key');

// Or use flash data that auto-expires
session()->flash('key', 'value');
```

### 4. Use Cache for Large Data
Instead of storing large data in session, use cache:
```php
// Instead of session
Cache::put("user.{$userId}.data", $largeData, 3600);
```

## Verification

After applying fixes, test with:

```bash
# Test nginx configuration
nginx -t

# Check current buffer sizes
nginx -V 2>&1 | grep -o 'with-http_[^ ]*'

# Monitor nginx error logs
tail -f /var/log/nginx/error.log
```

## Troubleshooting

### If error persists:

1. **Check actual header size:**
```bash
# In browser console:
document.cookie.length
```

2. **Clear browser cookies:**
```javascript
// In browser console:
document.cookie.split(";").forEach(c => {
    document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
});
```

3. **Check session table size:**
```sql
SELECT id, LENGTH(payload) as size 
FROM sessions 
ORDER BY size DESC 
LIMIT 10;
```

4. **Enable debug mode:**
```bash
# In .env
APP_DEBUG=true
LOG_LEVEL=debug
```

## Recommended Settings

For production:

```nginx
# Minimum recommended
client_header_buffer_size 16k;
large_client_header_buffers 4 32k;

# For applications with complex sessions
client_header_buffer_size 32k;
large_client_header_buffers 8 64k;

# Maximum (if needed)
client_header_buffer_size 64k;
large_client_header_buffers 16 128k;
```

## Security Considerations

- Only increase buffer sizes as needed
- Monitor for potential abuse (header injection attacks)
- Set reasonable `client_max_body_size` limits
- Use HTTPS with large cookies
- Enable `http_only` and `secure` cookie flags

## References

- [Nginx HttpCoreModule](http://nginx.org/en/docs/http/ngx_http_core_module.html)
- [Laravel Session Configuration](https://laravel.com/docs/session)
- [Cookie Best Practices](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies)
