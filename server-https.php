<?php

/**
 * Custom HTTPS development server for Laravel with SSL support
 */

$host = '0.0.0.0';
$port = 8000;
$certFile = __DIR__ . '/server.crt';
$keyFile = __DIR__ . '/server.key';

$context = stream_context_create([
    'ssl' => [
        'local_cert' => $certFile,
        'local_pk' => $keyFile,
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ],
]);

$socket = stream_socket_server(
    "ssl://{$host}:{$port}",
    $errno,
    $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
    $context
);

if (!$socket) {
    die("Failed to create socket: $errstr ($errno)\n");
}

echo "HTTPS Server running on https://{$host}:{$port}\n";
echo "Press Ctrl+C to stop the server\n\n";

while ($client = stream_socket_accept($socket, -1)) {
    $request = fread($client, 8192);
    
    // Parse the request
    $lines = explode("\r\n", $request);
    $requestLine = $lines[0] ?? '';
    preg_match('/^(\w+)\s+([^\s]+)\s+HTTP/', $requestLine, $matches);
    
    if (count($matches) < 3) {
        fclose($client);
        continue;
    }
    
    $method = $matches[1];
    $uri = $matches[2];
    
    // Forward to Laravel's built-in server handler
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = $port;
    $_SERVER['HTTP_HOST'] = $host . ':' . $port;
    
    // Build the path to the Laravel public directory
    $publicPath = __DIR__ . '/public';
    $scriptPath = $publicPath . '/index.php';
    
    // Determine the actual file to serve
    $requestPath = parse_url($uri, PHP_URL_PATH);
    $filePath = $publicPath . $requestPath;
    
    ob_start();
    
    if ($requestPath !== '/' && file_exists($filePath) && is_file($filePath)) {
        // Serve static file
        $mimeType = mime_content_type($filePath);
        header("Content-Type: $mimeType");
        readfile($filePath);
    } else {
        // Run Laravel
        chdir($publicPath);
        require $scriptPath;
    }
    
    $output = ob_get_clean();
    
    // Send response
    $response = "HTTP/1.1 200 OK\r\n";
    $response .= "Content-Length: " . strlen($output) . "\r\n";
    $response .= "Connection: close\r\n";
    $response .= "\r\n";
    $response .= $output;
    
    fwrite($client, $response);
    fclose($client);
    
    echo date('Y-m-d H:i:s') . " {$method} {$uri}\n";
}

fclose($socket);
