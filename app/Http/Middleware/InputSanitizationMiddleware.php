<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * InputSanitizationMiddleware
 *
 * Sanitizes and validates input data to prevent XSS, SQL injection,
 * and other security vulnerabilities.
 */
class InputSanitizationMiddleware
{
    /**
     * Dangerous patterns to detect and block
     */
    protected array $dangerousPatterns = [
        // SQL Injection patterns
        '/\bunion\s+select\b/i',
        '/\bselect\s+.*\s+from\s+.*\s+where\b/i',
        '/\binsert\s+into\s+.*\s+values\b/i',
        '/\bupdate\s+.*\s+set\b/i',
        '/\bdelete\s+from\b/i',
        '/\bdrop\s+(table|database)\b/i',
        '/\bexec(\s|\()/i',
        '/\bscript\s*>/i',
        '/\b(or|and)\s+\d+\s*=\s*\d+/i',

        // XSS patterns
        '/<script[^>]*>.*?<\/script>/is',
        '/<iframe[^>]*>.*?<\/iframe>/is',
        '/javascript\s*:/i',
        '/on\w+\s*=/i', // onclick, onload, etc.
        '/<embed[^>]*>/i',
        '/<object[^>]*>/i',
        '/vbscript\s*:/i',

        // Path traversal
        '/\.\.\//',
        '/\.\.\\\\/',

        // Command injection
        '/;\s*(ls|cat|rm|mv|cp|wget|curl|bash|sh)\s/i',
        '/\|\s*(ls|cat|rm|mv|cp|wget|curl|bash|sh)\s/i',
        '/`[^`]*`/',
        '/\$\([^)]*\)/',
    ];

    /**
     * File upload restrictions
     */
    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
    ];

    protected array $blockedExtensions = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps',
        'exe', 'com', 'bat', 'cmd', 'sh', 'bash',
        'js', 'vbs', 'vbe', 'jse', 'ws', 'wsf',
        'scr', 'pif', 'msi', 'jar', 'com', 'gadget',
        'application', 'msc', 'hta', 'cpl', 'msp', 'lib',
    ];

    protected int $maxFileSize = 10485760; // 10MB default

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip sanitization for specific routes if needed
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Detect dangerous patterns
        if ($this->detectDangerousPatterns($request)) {
            return $this->handleMaliciousRequest($request);
        }

        // Sanitize input data
        $this->sanitizeInput($request);

        // Validate file uploads
        if ($request->hasFile('file') || $request->allFiles()) {
            if (! $this->validateFileUploads($request)) {
                return $this->handleMaliciousUpload($request);
            }
        }

        return $next($request);
    }

    /**
     * Check if request should skip sanitization.
     */
    protected function shouldSkip(Request $request): bool
    {
        // Skip for API routes that handle their own validation
        if ($request->is('api/*') && config('security.input_sanitization.skip_api', false)) {
            return true;
        }

        // Skip for specific routes
        $skipRoutes = config('security.input_sanitization.skip_routes', []);
        foreach ($skipRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect dangerous patterns in request data.
     */
    protected function detectDangerousPatterns(Request $request): bool
    {
        $data = array_merge(
            $request->all(),
            $request->headers->all(),
            ['url' => $request->fullUrl()]
        );

        $flatData = $this->flattenArray($data);

        foreach ($flatData as $value) {
            if (! is_string($value)) {
                continue;
            }

            foreach ($this->dangerousPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    $this->logDangerousPattern($request, $pattern, $value);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Flatten multi-dimensional array.
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Sanitize input data.
     */
    protected function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        $sanitized = $this->sanitizeArray($input);

        // Replace request input with sanitized data
        $request->replace($sanitized);
    }

    /**
     * Recursively sanitize array data.
     */
    protected function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            // Sanitize key
            $sanitizedKey = $this->sanitizeString($key);

            if ($sanitizedKey !== $key) {
                unset($data[$key]);
                $key = $sanitizedKey;
            }

            // Sanitize value
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            }
        }

        return $data;
    }

    /**
     * Sanitize string value.
     */
    protected function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);

        // Strip tags but preserve content
        $value = strip_tags($value);

        // Convert special characters to HTML entities
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove any remaining control characters
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        // Trim whitespace
        $value = trim($value);

        return $value;
    }

    /**
     * Validate file uploads.
     */
    protected function validateFileUploads(Request $request): bool
    {
        $files = $request->allFiles();

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $uploadedFile) {
                    if (! $this->validateSingleFile($uploadedFile)) {
                        return false;
                    }
                }
            } else {
                if (! $this->validateSingleFile($file)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate a single uploaded file.
     */
    protected function validateSingleFile(UploadedFile $file): bool
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            $this->logFileValidationError($file, 'File size exceeds limit');

            return false;
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (! in_array($mimeType, $this->allowedMimeTypes)) {
            $this->logFileValidationError($file, 'Invalid MIME type: '.$mimeType);

            return false;
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, $this->blockedExtensions)) {
            $this->logFileValidationError($file, 'Blocked file extension: '.$extension);

            return false;
        }

        // Additional checks for images
        if (str_starts_with($mimeType, 'image/')) {
            if (! $this->validateImageFile($file)) {
                return false;
            }
        }

        // Check for PHP code in file content
        if ($this->containsPhpCode($file)) {
            $this->logFileValidationError($file, 'File contains PHP code');

            return false;
        }

        return true;
    }

    /**
     * Validate image file.
     */
    protected function validateImageFile(UploadedFile $file): bool
    {
        try {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                $this->logFileValidationError($file, 'Invalid image file');

                return false;
            }

            // Check for embedded PHP in EXIF data
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($file->getPathname());
                if ($exif !== false) {
                    $exifString = json_encode($exif);
                    if (stripos($exifString, '<?php') !== false) {
                        $this->logFileValidationError($file, 'Image EXIF contains PHP code');

                        return false;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logFileValidationError($file, 'Image validation error: '.$e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Check if file contains PHP code.
     */
    protected function containsPhpCode(UploadedFile $file): bool
    {
        $content = file_get_contents($file->getPathname());

        $phpPatterns = [
            '/<\?php/i',
            '/<\?=/i',
            '/<\?/i',
            '/\beval\s*\(/i',
            '/\bbase64_decode\s*\(/i',
            '/\bsystem\s*\(/i',
            '/\bexec\s*\(/i',
            '/\bshell_exec\s*\(/i',
            '/\bpassthru\s*\(/i',
            '/\bproc_open\s*\(/i',
        ];

        foreach ($phpPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log dangerous pattern detection.
     */
    protected function logDangerousPattern(Request $request, string $pattern, string $value): void
    {
        AuditLog::logSecurity('Dangerous Pattern Detected', [
            'pattern' => $pattern,
            'value' => substr($value, 0, 200), // Truncate for safety
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], AuditLog::SEVERITY_CRITICAL);
    }

    /**
     * Log file validation error.
     */
    protected function logFileValidationError(UploadedFile $file, string $reason): void
    {
        AuditLog::logSecurity('File Upload Validation Failed', [
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'reason' => $reason,
        ], AuditLog::SEVERITY_WARNING);
    }

    /**
     * Handle malicious request.
     */
    protected function handleMaliciousRequest(Request $request): Response
    {
        // Block the IP temporarily
        $this->blockIpTemporarily($request->ip());

        abort(400, 'Bad Request');
    }

    /**
     * Handle malicious upload.
     */
    protected function handleMaliciousUpload(Request $request): Response
    {
        abort(422, 'Invalid file upload');
    }

    /**
     * Block IP temporarily.
     */
    protected function blockIpTemporarily(string $ip): void
    {
        $key = 'blocked_ip_'.$ip;
        $attempts = cache()->increment($key);

        if ($attempts >= 3) {
            // Block for 1 hour after 3 attempts
            cache()->put('ip_blocked_'.$ip, true, now()->addHour());
        }
    }
}
