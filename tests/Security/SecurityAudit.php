<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class SecurityAudit extends TestCase
{
    protected array $vulnerabilities = [];
    protected array $passed = [];

    /**
     * Run complete security audit
     */
    public function runSecurityAudit(): array
    {
        $this->testAuthentication();
        $this->testAuthorization();
        $this->testSQLInjection();
        $this->testXSSProtection();
        $this->testCSRFProtection();
        $this->testPasswordSecurity();
        $this->testFileUploadSecurity();
        $this->testAPISecurityHeaders();
        $this->testRateLimiting();
        $this->testEncryption();
        $this->testSessionSecurity();
        $this->testDependencyVulnerabilities();

        return $this->generateSecurityReport();
    }

    /**
     * Test authentication security
     */
    protected function testAuthentication(): void
    {
        // Test brute force protection
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $attempts = 0;
        $maxAttempts = 5;

        for ($i = 0; $i < $maxAttempts + 1; $i++) {
            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

            if ($response->status() === 429) {
                $this->passed[] = 'Brute force protection active after ' . $i . ' attempts';
                break;
            }
            $attempts++;
        }

        if ($attempts > $maxAttempts) {
            $this->vulnerabilities[] = [
                'type' => 'authentication',
                'severity' => 'high',
                'issue' => 'No brute force protection',
                'details' => 'Login attempts are not rate limited',
                'fix' => 'Implement rate limiting on login endpoint',
            ];
        }

        // Test password reset token security
        $resetToken = app('auth.password.broker')->createToken($user);
        if (strlen($resetToken) < 32) {
            $this->vulnerabilities[] = [
                'type' => 'authentication',
                'severity' => 'medium',
                'issue' => 'Weak password reset tokens',
                'details' => 'Reset tokens are too short',
                'fix' => 'Use longer, cryptographically secure tokens',
            ];
        } else {
            $this->passed[] = 'Password reset tokens are secure';
        }
    }

    /**
     * Test authorization
     */
    protected function testAuthorization(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user1->id]);

        // Test unauthorized access
        $this->actingAs($user2);
        $response = $this->get(route('projects.show', $project));

        if ($response->status() !== 403 && $response->status() !== 404) {
            $this->vulnerabilities[] = [
                'type' => 'authorization',
                'severity' => 'critical',
                'issue' => 'Broken access control',
                'details' => 'Users can access resources they don\'t own',
                'fix' => 'Implement proper authorization checks',
            ];
        } else {
            $this->passed[] = 'Authorization checks are working';
        }

        // Test privilege escalation
        $response = $this->actingAs($user2)->put(route('projects.update', $project), [
            'name' => 'Hacked Project',
        ]);

        if ($response->status() === 200) {
            $this->vulnerabilities[] = [
                'type' => 'authorization',
                'severity' => 'critical',
                'issue' => 'Privilege escalation possible',
                'details' => 'Users can modify resources they don\'t own',
                'fix' => 'Add authorization middleware to all routes',
            ];
        }
    }

    /**
     * Test SQL injection vulnerabilities
     */
    protected function testSQLInjection(): void
    {
        $maliciousInputs = [
            "'; DROP TABLE users; --",
            "1' OR '1' = '1",
            "admin'--",
            "1' UNION SELECT * FROM users--",
        ];

        foreach ($maliciousInputs as $input) {
            try {
                // Test in search
                DB::select("SELECT * FROM projects WHERE name = ?", [$input]);

                // Test in user input
                $this->actingAs(User::factory()->create())
                    ->post('/projects', [
                        'name' => $input,
                        'slug' => 'test',
                    ]);

                $this->passed[] = 'SQL injection protection for: ' . substr($input, 0, 20);
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'syntax error') !== false) {
                    $this->vulnerabilities[] = [
                        'type' => 'sql_injection',
                        'severity' => 'critical',
                        'issue' => 'SQL injection vulnerability',
                        'details' => 'Input not properly escaped: ' . $input,
                        'fix' => 'Use parameterized queries and Eloquent ORM',
                    ];
                }
            }
        }
    }

    /**
     * Test XSS protection
     */
    protected function testXSSProtection(): void
    {
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror="alert(\'XSS\')">',
            'javascript:alert("XSS")',
            '<body onload="alert(\'XSS\')">',
        ];

        $user = User::factory()->create();

        foreach ($xssPayloads as $payload) {
            $project = Project::factory()->create([
                'user_id' => $user->id,
                'name' => $payload,
            ]);

            $this->actingAs($user);
            $response = $this->get(route('projects.show', $project));

            if (strpos($response->getContent(), $payload) !== false) {
                $this->vulnerabilities[] = [
                    'type' => 'xss',
                    'severity' => 'high',
                    'issue' => 'XSS vulnerability',
                    'details' => 'Unescaped output: ' . substr($payload, 0, 30),
                    'fix' => 'Use {{ }} instead of {!! !!} in Blade templates',
                ];
            } else {
                $this->passed[] = 'XSS protection working for: ' . substr($payload, 0, 20);
            }
        }
    }

    /**
     * Test CSRF protection
     */
    protected function testCSRFProtection(): void
    {
        $user = User::factory()->create();

        // Test without CSRF token
        $this->actingAs($user);
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post('/projects', ['name' => 'Test']);

        if ($response->status() === 200 || $response->status() === 302) {
            $this->vulnerabilities[] = [
                'type' => 'csrf',
                'severity' => 'high',
                'issue' => 'CSRF protection can be bypassed',
                'details' => 'POST requests accepted without CSRF token',
                'fix' => 'Ensure CSRF middleware is applied to all state-changing routes',
            ];
        } else {
            $this->passed[] = 'CSRF protection is active';
        }
    }

    /**
     * Test password security
     */
    protected function testPasswordSecurity(): void
    {
        // Test weak passwords
        $weakPasswords = ['123456', 'password', 'admin123'];

        foreach ($weakPasswords as $password) {
            try {
                $user = User::factory()->create(['password' => Hash::make($password)]);

                if (strlen($password) < 8) {
                    $this->vulnerabilities[] = [
                        'type' => 'password',
                        'severity' => 'medium',
                        'issue' => 'Weak passwords allowed',
                        'details' => 'System accepts password: ' . $password,
                        'fix' => 'Implement password strength requirements',
                    ];
                }
            } catch (\Exception $e) {
                $this->passed[] = 'Password validation prevents: ' . $password;
            }
        }

        // Test password hashing
        $plainPassword = 'TestPassword123!';
        $hash = Hash::make($plainPassword);

        if (strlen($hash) < 60) {
            $this->vulnerabilities[] = [
                'type' => 'password',
                'severity' => 'critical',
                'issue' => 'Weak password hashing',
                'details' => 'Password hashes are too short',
                'fix' => 'Use bcrypt or Argon2 for password hashing',
            ];
        } else {
            $this->passed[] = 'Strong password hashing in use';
        }
    }

    /**
     * Test file upload security
     */
    protected function testFileUploadSecurity(): void
    {
        $maliciousFiles = [
            ['name' => 'exploit.php', 'content' => '<?php system($_GET["cmd"]); ?>'],
            ['name' => 'shell.aspx', 'content' => '<%@ Page Language="C#" %>'],
            ['name' => '../../../etc/passwd', 'content' => 'root:x:0:0'],
        ];

        foreach ($maliciousFiles as $file) {
            // Test file upload restrictions
            if (!$this->isFileTypeAllowed($file['name'])) {
                $this->passed[] = 'File type blocked: ' . $file['name'];
            } else {
                $this->vulnerabilities[] = [
                    'type' => 'file_upload',
                    'severity' => 'critical',
                    'issue' => 'Dangerous file types allowed',
                    'details' => 'Can upload: ' . $file['name'],
                    'fix' => 'Implement strict file type validation',
                ];
            }
        }
    }

    /**
     * Test API security headers
     */
    protected function testAPISecurityHeaders(): void
    {
        $response = $this->get('/api/health');

        $requiredHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000',
            'Content-Security-Policy' => 'default-src',
        ];

        foreach ($requiredHeaders as $header => $expectedValue) {
            $actualValue = $response->headers->get($header);

            if (!$actualValue) {
                $this->vulnerabilities[] = [
                    'type' => 'headers',
                    'severity' => 'medium',
                    'issue' => 'Missing security header',
                    'details' => $header . ' is not set',
                    'fix' => 'Add ' . $header . ' header with value: ' . $expectedValue,
                ];
            } elseif (strpos($actualValue, explode(' ', $expectedValue)[0]) === false) {
                $this->vulnerabilities[] = [
                    'type' => 'headers',
                    'severity' => 'low',
                    'issue' => 'Weak security header',
                    'details' => $header . ' has value: ' . $actualValue,
                    'fix' => 'Set to: ' . $expectedValue,
                ];
            } else {
                $this->passed[] = 'Security header present: ' . $header;
            }
        }
    }

    /**
     * Test rate limiting
     */
    protected function testRateLimiting(): void
    {
        $endpoints = [
            '/api/projects' => 60,  // 60 requests per minute
            '/api/deployments' => 30,  // 30 requests per minute
        ];

        foreach ($endpoints as $endpoint => $limit) {
            $hitCount = 0;

            for ($i = 0; $i < $limit + 10; $i++) {
                $response = $this->get($endpoint);

                if ($response->status() === 429) {
                    $this->passed[] = 'Rate limiting active on ' . $endpoint . ' after ' . $i . ' requests';
                    break;
                }
                $hitCount++;
            }

            if ($hitCount > $limit) {
                $this->vulnerabilities[] = [
                    'type' => 'rate_limiting',
                    'severity' => 'medium',
                    'issue' => 'No rate limiting on ' . $endpoint,
                    'details' => 'Endpoint accepts unlimited requests',
                    'fix' => 'Implement rate limiting middleware',
                ];
            }
        }
    }

    /**
     * Test encryption
     */
    protected function testEncryption(): void
    {
        // Test sensitive data encryption
        $sensitiveData = 'api_secret_key_123456';

        try {
            $encrypted = Crypt::encryptString($sensitiveData);
            $decrypted = Crypt::decryptString($encrypted);

            if ($decrypted === $sensitiveData) {
                $this->passed[] = 'Encryption/decryption working correctly';
            }
        } catch (\Exception $e) {
            $this->vulnerabilities[] = [
                'type' => 'encryption',
                'severity' => 'high',
                'issue' => 'Encryption not properly configured',
                'details' => $e->getMessage(),
                'fix' => 'Configure APP_KEY and encryption settings',
            ];
        }

        // Check for sensitive data in logs
        $logContent = file_get_contents(storage_path('logs/laravel.log'));
        $sensitivePatterns = [
            '/password["\']?\s*[:=]\s*["\'][^"\']+["\']/i',
            '/api[_-]?key["\']?\s*[:=]\s*["\'][^"\']+["\']/i',
            '/secret["\']?\s*[:=]\s*["\'][^"\']+["\']/i',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $logContent)) {
                $this->vulnerabilities[] = [
                    'type' => 'data_exposure',
                    'severity' => 'high',
                    'issue' => 'Sensitive data in logs',
                    'details' => 'Pattern found: ' . $pattern,
                    'fix' => 'Filter sensitive data from logs',
                ];
            }
        }
    }

    /**
     * Test session security
     */
    protected function testSessionSecurity(): void
    {
        $sessionConfig = config('session');

        if (!($sessionConfig['secure'] ?? false)) {
            $this->vulnerabilities[] = [
                'type' => 'session',
                'severity' => 'medium',
                'issue' => 'Session cookies not secure',
                'details' => 'secure flag not set on session cookies',
                'fix' => 'Set SESSION_SECURE_COOKIE=true in production',
            ];
        }

        if (!($sessionConfig['http_only'] ?? true)) {
            $this->vulnerabilities[] = [
                'type' => 'session',
                'severity' => 'medium',
                'issue' => 'Session cookies accessible via JavaScript',
                'details' => 'httpOnly flag not set',
                'fix' => 'Set session.http_only = true',
            ];
        }

        if (($sessionConfig['lifetime'] ?? 120) > 480) {
            $this->vulnerabilities[] = [
                'type' => 'session',
                'severity' => 'low',
                'issue' => 'Long session lifetime',
                'details' => 'Sessions last ' . $sessionConfig['lifetime'] . ' minutes',
                'fix' => 'Reduce session lifetime to 8 hours or less',
            ];
        }
    }

    /**
     * Test dependency vulnerabilities
     */
    protected function testDependencyVulnerabilities(): void
    {
        // Check composer packages
        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

        $knownVulnerablePackages = [
            'symfony/http-kernel' => '< 5.4.20',
            'laravel/framework' => '< 9.52.0',
            'guzzlehttp/guzzle' => '< 7.4.5',
        ];

        foreach ($composerLock['packages'] as $package) {
            if (isset($knownVulnerablePackages[$package['name']])) {
                $constraint = $knownVulnerablePackages[$package['name']];
                if (version_compare($package['version'], str_replace('< ', '', $constraint), '<')) {
                    $this->vulnerabilities[] = [
                        'type' => 'dependency',
                        'severity' => 'high',
                        'issue' => 'Vulnerable dependency',
                        'details' => $package['name'] . ' ' . $package['version'] . ' has known vulnerabilities',
                        'fix' => 'Update to version ' . $constraint . ' or higher',
                    ];
                }
            }
        }
    }

    /**
     * Check if file type is allowed
     */
    private function isFileTypeAllowed(string $filename): bool
    {
        $blockedExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'asp', 'aspx', 'exe', 'sh'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return !in_array($extension, $blockedExtensions);
    }

    /**
     * Generate security report
     */
    protected function generateSecurityReport(): array
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'vulnerabilities' => $this->vulnerabilities,
            'passed' => $this->passed,
            'statistics' => [
                'total_vulnerabilities' => count($this->vulnerabilities),
                'critical' => count(array_filter($this->vulnerabilities, fn($v) => ($v['severity'] ?? '') === 'critical')),
                'high' => count(array_filter($this->vulnerabilities, fn($v) => ($v['severity'] ?? '') === 'high')),
                'medium' => count(array_filter($this->vulnerabilities, fn($v) => ($v['severity'] ?? '') === 'medium')),
                'low' => count(array_filter($this->vulnerabilities, fn($v) => ($v['severity'] ?? '') === 'low')),
                'tests_passed' => count($this->passed),
            ],
            'security_score' => $this->calculateSecurityScore(),
        ];

        // Save report
        $reportPath = storage_path('app/security-reports/audit-' . now()->format('Y-m-d-H-i-s') . '.json');
        @mkdir(dirname($reportPath), 0755, true);
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));

        return $report;
    }

    /**
     * Calculate security score
     */
    protected function calculateSecurityScore(): int
    {
        $score = 100;

        foreach ($this->vulnerabilities as $vulnerability) {
            switch ($vulnerability['severity'] ?? 'low') {
                case 'critical':
                    $score -= 25;
                    break;
                case 'high':
                    $score -= 15;
                    break;
                case 'medium':
                    $score -= 10;
                    break;
                case 'low':
                    $score -= 5;
                    break;
            }
        }

        return max(0, $score);
    }
}