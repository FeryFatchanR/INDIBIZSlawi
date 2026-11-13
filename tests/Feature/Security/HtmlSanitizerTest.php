<?php

namespace Tests\Feature\Security;

use App\Support\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_rich_text_strips_scripts_handlers_and_dangerous_links(): void
    {
        $dirty = '<p onclick="alert(1)">Halo <strong>dunia</strong></p><script>alert(1)</script>'
            .'<a href="javascript:alert(1)">x</a><img src=x onerror=alert(1)><iframe src="https://evil"></iframe>'
            .'<a href="https://example.com" target="_blank">ok</a><style>body{}</style>';

        $clean = HtmlSanitizer::richText($dirty);

        $this->assertStringContainsString('<strong>dunia</strong>', $clean);
        $this->assertStringContainsString('href="https://example.com"', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
        foreach (['<script', 'onclick', 'onerror', 'javascript:', '<iframe', '<img', '<style'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $clean);
        }
    }

    public function test_custom_html_allows_layout_but_not_active_content(): void
    {
        $clean = HtmlSanitizer::customHtml('<div class="grid" style="position:fixed"><img src="https://cdn.example.com/a.png" onload="x()"><svg onload="x()"></svg><form action="/x"><input></form></div>');

        $this->assertStringContainsString('<div class="grid">', $clean);
        $this->assertStringContainsString('<img src="https://cdn.example.com/a.png"', $clean);
        foreach (['style=', 'onload', '<svg', '<form', '<input'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $clean);
        }
    }
}
