<?php

namespace Tests\Feature\Builder;

use App\Builder\SectionValidator;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsPages;
use Tests\TestCase;

class SectionValidatorTest extends TestCase
{
    use BuildsPages, RefreshDatabase;

    private function validator(): SectionValidator
    {
        return app(SectionValidator::class);
    }

    private function assertInvalid(array $sections, ?User $user = null): void
    {
        try {
            $this->validator()->validateTree($sections, $user);
            $this->fail('Seharusnya ValidationException.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_valid_section_is_normalized_and_unknown_keys_are_stripped(): void
    {
        $section = $this->section('heading', ['heading' => 'Halo', 'evil_key' => 'x']);
        $section['design']['onclick'] = 'alert(1)';

        $result = $this->validator()->validateTree([$section]);

        $this->assertSame('Halo', $result[0]['content']['heading']);
        $this->assertArrayNotHasKey('evil_key', $result[0]['content']);
        $this->assertArrayNotHasKey('onclick', $result[0]['design']);
        $this->assertSame(1, $result[0]['schema_version']);
    }

    public function test_rejects_unknown_component_type(): void
    {
        $this->assertInvalid([['uuid' => (string) Str::uuid(), 'type' => '../../etc/passwd', 'content' => []]]);
        $this->assertInvalid([['uuid' => (string) Str::uuid(), 'type' => 'script', 'content' => []]]);
    }

    public function test_rejects_invalid_json_configuration_shapes(): void
    {
        $section = $this->section('heading');
        $section['content'] = 'bukan-objek';
        $this->assertInvalid([$section]);

        $section = $this->section('heading');
        $section['responsive'] = 123;
        $this->assertInvalid([$section]);
    }

    public function test_rejects_invalid_or_duplicate_uuid(): void
    {
        $this->assertInvalid([array_merge($this->section('heading'), ['uuid' => '1'])]);
        $a = $this->section('heading');
        $this->assertInvalid([$a, $a]);
    }

    public function test_responsive_values_must_be_whitelisted(): void
    {
        $this->assertInvalid([$this->section('features', [], [], ['desktop' => ['columns' => 9]])]);
        $this->assertInvalid([$this->section('features', [], [], ['mobile' => ['padding_y' => '999px']])]);
        $this->assertInvalid([$this->section('heading', [], ['background' => 'url(javascript:alert(1))'])]);
        $this->assertInvalid([$this->section('heading', [], ['background' => 'custom', 'background_color' => 'red;position:fixed'])]);

        $ok = $this->validator()->validateTree([$this->section('features', [], [], ['desktop' => ['columns' => 4], 'tablet' => ['columns' => 2], 'mobile' => ['columns' => 1, 'visible' => false]])]);
        $this->assertSame(4, $ok[0]['responsive']['desktop']['columns']);
        $this->assertFalse($ok[0]['responsive']['mobile']['visible']);
    }

    public function test_rejects_dangerous_urls_and_video_ids(): void
    {
        $this->assertInvalid([$this->section('hero', ['primary_button' => ['label' => 'X', 'url' => 'javascript:alert(document.cookie)']])]);
        $this->assertInvalid([$this->section('video', ['provider' => 'youtube', 'video_id' => '"><script>'])]);
        $this->assertInvalid([$this->section('map', ['mode' => 'embed', 'embed_url' => 'https://evil.example.com/maps/embed?pb=1'])]);
    }

    public function test_rich_text_is_sanitized_on_save(): void
    {
        $result = $this->validator()->validateTree([$this->section('rich_text', ['body' => '<p>Aman</p><script>alert(1)</script><img src=x onerror=alert(1)>'])]);

        $this->assertSame('<p>Aman</p>', $result[0]['content']['body']);
    }

    public function test_payload_limits_and_nesting_depth(): void
    {
        config(['cms.builder.max_sections' => 3]);
        $this->assertInvalid(array_map(fn () => $this->section('spacer'), range(1, 4)));

        config(['cms.builder.max_sections' => 100, 'cms.builder.max_payload_bytes' => 500]);
        $this->assertInvalid([$this->section('rich_text', ['body' => str_repeat('a', 1000)])]);

        config(['cms.builder.max_payload_bytes' => 262144]);
        $a = $this->section('spacer');
        $b = array_merge($this->section('spacer'), ['parent_uuid' => $a['uuid']]);
        $c = array_merge($this->section('spacer'), ['parent_uuid' => $b['uuid']]);
        $this->assertInvalid([$a, $b, $c]); // depth 3 > 2
        $this->assertInvalid([array_merge($this->section('spacer'), ['parent_uuid' => (string) Str::uuid()])]); // parent hilang
    }

    public function test_custom_html_requires_special_permission(): void
    {
        $this->seedRoles();
        $admin = $this->userWithRole(Roles::ADMINISTRATOR);
        $super = $this->userWithRole(Roles::SUPER_ADMIN);
        $section = $this->section('custom_html', ['html' => '<div>ok</div><script>x</script>']);

        $this->assertInvalid([$section], $admin);
        $result = $this->validator()->validateTree([$section], $super);
        $this->assertSame('<div>ok</div>', $result[0]['content']['html']);
    }
}
