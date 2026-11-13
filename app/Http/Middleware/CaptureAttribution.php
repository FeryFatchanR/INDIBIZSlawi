<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyimpan parameter UTM & landing page pertama ke session untuk atribusi lead.
 * Nilai dibatasi panjang & karakter, tidak pernah dirender tanpa escaping.
 */
class CaptureAttribution
{
    private const KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'campaign'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->hasSession()) {
            $utm = [];
            foreach (self::KEYS as $key) {
                $value = $request->query($key);
                if (is_string($value) && $value !== '') {
                    $utm[$key] = mb_substr(preg_replace('/[^\pL\pN _\-.]/u', '', $value), 0, 100);
                }
            }
            if ($utm !== []) {
                $request->session()->put('attribution.utm', $utm);
            }
            if (! $request->session()->has('attribution.landing')) {
                $request->session()->put('attribution.landing', mb_substr($request->path(), 0, 250));
            }
        }

        return $next($request);
    }
}
