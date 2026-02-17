<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class XssSanitizer
{
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                // Remove only dangerous XSS patterns, keep normal characters like quotes
                $value = $this->cleanXss($value);
            }
        });

        $request->merge($input);

        return $next($request);
    }

    private function cleanXss($value)
    {
        // Remove script tags
        $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);        

        // Remove event handlers (onclick, onerror, etc.)
        $value = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);

        // Remove javascript: protocol
        $value = preg_replace('/javascript:/i', '', $value);

        // Remove data: protocol (used for base64 encoded XSS)
        $value = preg_replace('/data:text\/html/i', '', $value);

        // Remove vbscript: protocol
        $value = preg_replace('/vbscript:/i', '', $value);

        // Remove dangerous HTML tags but keep content
        $value = strip_tags($value);

        // DON'T use htmlspecialchars here - it encodes quotes
        // Only escape when displaying in HTML views, not when storing

        return trim($value);
    }
}
