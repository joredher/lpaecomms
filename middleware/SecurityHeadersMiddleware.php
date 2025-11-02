<?php

class SecurityHeadersMiddleware
{
    public static function emit(): void
    {
        // Prevent duplicate headers
        if (headers_sent()) return;

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer-when-downgrade');
        header('X-Download-Options: noopen');
        header('X-Permitted-Cross-Domain-Policies: none');

        // Baseline CSP suitable for current code (allows inline until we refactor)
        $csp = [
            "default-src 'self'",
            "img-src 'self' data: https:",
            "style-src 'self' 'unsafe-inline' https:",
            "script-src 'self' 'unsafe-inline' https:",
            "connect-src 'self' https:",
            "font-src 'self' https:",
            // Our pages must not be embedded elsewhere
            "frame-ancestors 'none'",
            // Allow embedding third-party content we explicitly use (maps, videos, social)
            "frame-src 'self' https://www.google.com https://maps.google.com https://www.youtube-nocookie.com https://www.youtube.com https://www.facebook.com",
            // Safari/older browsers compatibility
            "child-src 'self' https://www.google.com https://maps.google.com https://www.youtube-nocookie.com https://www.youtube.com https://www.facebook.com",
        ];
        header('Content-Security-Policy: ' . implode('; ', $csp));
    }
}
