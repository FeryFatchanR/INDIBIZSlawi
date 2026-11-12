<?php

namespace Tests\Unit;

use App\Support\UrlGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UrlGuardTest extends TestCase
{
    #[DataProvider('safeUrls')]
    public function test_allows_safe_urls(string $url): void
    {
        $this->assertTrue(UrlGuard::isSafe($url));
    }

    #[DataProvider('unsafeUrls')]
    public function test_rejects_unsafe_urls(string $url): void
    {
        $this->assertFalse(UrlGuard::isSafe($url));
        $this->assertSame('#', UrlGuard::sanitize($url));
    }

    public static function safeUrls(): array
    {
        return [['https://example.com/a?b=1'], ['http://example.com'], ['/kontak'], ['#faq'], ['mailto:sales@example.com'], ['tel:+62812-3456']];
    }

    public static function unsafeUrls(): array
    {
        return [
            ['javascript:alert(1)'], ['JaVaScRiPt:alert(1)'], ['data:text/html;base64,PHNjcmlwdD4='], ['vbscript:msgbox'], ['tel:+62 812 3456'],
            ['//evil.example.com'], ['java script:alert(1)'], ["javascript\t:alert(1)"], ['file:///etc/passwd'], ['mailto:bukan-email'],
        ];
    }
}
