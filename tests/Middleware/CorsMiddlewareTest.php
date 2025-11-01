<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../middleware/CorsMiddleware.php';

class CorsMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        CorsMiddleware::clearCachedConfig();
        if (function_exists('header_remove')) {
            header_remove();
        }
        parent::setUp();
    }

    protected function tearDown(): void
    {
        if (function_exists('header_remove')) {
            header_remove();
        }
        putenv('APP_ENV');
        putenv('API_CORS_ALLOWED_ORIGINS');
        putenv('API_CORS_SENSITIVE_ORIGINS');
        putenv('API_CORS_DEV_ORIGINS');
        putenv('API_CORS_ALLOW_CREDENTIALS');
        putenv('API_CORS_ALLOWED_METHODS');
        putenv('API_CORS_ALLOWED_HEADERS');
        putenv('API_CORS_EXPOSE_HEADERS');
        putenv('API_CORS_MAX_AGE');
        unset($_SERVER['HTTP_ORIGIN']);
        CorsMiddleware::clearCachedConfig();
        parent::tearDown();
    }

    /**
     * @runInSeparateProcess
     */
    public function testRejectsDisallowedOriginForSensitiveEndpointsInProduction(): void
    {
        putenv('APP_ENV=production');
        putenv('API_CORS_ALLOWED_ORIGINS=https://app.example.com');
        putenv('API_CORS_SENSITIVE_ORIGINS=https://secure.example.com');
        $_SERVER['HTTP_ORIGIN'] = 'https://evil.example.com';
        http_response_code(200);

        ob_start();
        $allowed = CorsMiddleware::handle('POST', true);
        $output = ob_get_clean();

        $this->assertFalse($allowed);
        $this->assertSame(403, http_response_code());
        $this->assertJson($output);
        $payload = json_decode($output, true);
        $this->assertSame('cors_forbidden', $payload['error']);
        $this->assertArrayHasKey('message', $payload);

    }

    /**
     * @runInSeparateProcess
     */
    public function testAllowsDevelopmentOriginForSensitiveEndpointsOutsideProduction(): void
    {
        putenv('APP_ENV=local');
        putenv('API_CORS_ALLOWED_ORIGINS=');
        putenv('API_CORS_SENSITIVE_ORIGINS=');
        putenv('API_CORS_DEV_ORIGINS=http://localhost:8080');
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost:8080';
        http_response_code(200);

        ob_start();
        $allowed = CorsMiddleware::handle('POST', true);
        $output = ob_get_clean();

        $this->assertTrue($allowed);
        $this->assertSame('', $output);
        $this->assertSame(200, http_response_code());

        $ref = new ReflectionClass(CorsMiddleware::class);
        $configMethod = $ref->getMethod('config');
        $configMethod->setAccessible(true);
        $config = $configMethod->invoke(null);
        $resolveMethod = $ref->getMethod('resolveAllowedOrigins');
        $resolveMethod->setAccessible(true);
        $origins = $resolveMethod->invoke(null, $config, true);

        $this->assertContains('http://localhost:8080', $origins);
    }

    /**
     * @runInSeparateProcess
     */
    public function testOptionsPreflightShortCircuitsAfterSendingHeaders(): void
    {
        putenv('APP_ENV=local');
        putenv('API_CORS_ALLOWED_ORIGINS=*');
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost:3000';
        http_response_code(200);

        ob_start();
        $allowed = CorsMiddleware::handle('OPTIONS', false);
        $output = ob_get_clean();

        $this->assertFalse($allowed);
        $this->assertSame('', $output);
        $this->assertSame(204, http_response_code());

        $ref = new ReflectionClass(CorsMiddleware::class);
        $configMethod = $ref->getMethod('config');
        $configMethod->setAccessible(true);
        $config = $configMethod->invoke(null);

        $this->assertContains('OPTIONS', $config['allowed_methods']);
        $this->assertContains('Content-Type', $config['allowed_headers']);
    }
}
