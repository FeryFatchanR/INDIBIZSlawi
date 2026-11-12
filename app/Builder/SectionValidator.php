<?php

namespace App\Builder;

use App\Builder\Contracts\SectionType;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validasi & normalisasi seluruh tree section sebelum disimpan atau dipublikasikan:
 * tipe allowlist, permission, UUID unik, kedalaman, ukuran payload, skema per tipe,
 * pembuangan key tak dikenal, dan sanitasi rich text.
 */
class SectionValidator
{
    public function __construct(private readonly SectionRegistry $registry) {}

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     *
     * @throws ValidationException
     */
    public function validateTree(array $sections, ?User $user = null): array
    {
        $limits = config('cms.builder');

        if (count($sections) > $limits['max_sections']) {
            $this->fail('sections', "Maksimal {$limits['max_sections']} section per halaman.");
        }

        $payload = json_encode($sections);
        if ($payload === false || strlen($payload) > $limits['max_payload_bytes']) {
            $this->fail('sections', 'Ukuran konfigurasi halaman melebihi batas atau JSON tidak valid.');
        }

        $byUuid = [];
        foreach ($sections as $i => $section) {
            if (! is_array($section)) {
                $this->fail("sections.{$i}", 'Format section tidak valid.');
            }
            $uuid = $section['uuid'] ?? null;
            if (! is_string($uuid) || ! Str::isUuid($uuid)) {
                $this->fail("sections.{$i}.uuid", 'UUID section tidak valid.');
            }
            if (isset($byUuid[$uuid])) {
                $this->fail("sections.{$i}.uuid", 'UUID section duplikat.');
            }
            $byUuid[$uuid] = $section;
        }

        $normalized = [];
        foreach (array_values($sections) as $i => $section) {
            $this->assertDepth($section, $byUuid, (int) $limits['max_depth']);
            $normalized[] = $this->validateSection($section, $user, $i);
        }

        return $normalized;
    }

    /**
     * Validasi satu section. Mengembalikan section ternormalisasi.
     *
     * @throws ValidationException
     */
    public function validateSection(array $section, ?User $user = null, int $index = 0): array
    {
        $typeKey = $section['type'] ?? null;
        if (! is_string($typeKey) || ! $this->registry->has($typeKey)) {
            $this->fail("sections.{$index}.type", 'Tipe komponen tidak dikenal.');
        }

        $type = $this->registry->get($typeKey);

        if ($user && $type->permission() && ! $user->can($type->permission())) {
            $this->fail("sections.{$index}.type", "Anda tidak memiliki izin untuk komponen {$type->label()}.");
        }

        if ((int) ($section['schema_version'] ?? $type->schemaVersion()) < $type->schemaVersion()) {
            $section = $type->upgrade($section);
        }

        foreach (['content', 'design', 'responsive'] as $part) {
            if (isset($section[$part]) && ! is_array($section[$part])) {
                $this->fail("sections.{$index}.{$part}", "Konfigurasi {$part} harus berupa objek.");
            }
        }

        $data = [
            'content' => $section['content'] ?? [],
            'design' => $section['design'] ?? [],
            'responsive' => $section['responsive'] ?? [],
        ];

        $rules = ['content' => ['present', 'array'], 'design' => ['present', 'array'], 'responsive' => ['present', 'array']];
        foreach ($type->contentRules() as $field => $rule) {
            $rules["content.{$field}"] = $rule;
        }
        foreach (array_merge($type->commonDesignRules(), $type->designRules()) as $field => $rule) {
            $rules["design.{$field}"] = $rule;
        }
        foreach ($type->responsiveRules() as $field => $rule) {
            $rules["responsive.{$field}"] = $rule;
        }

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw ValidationException::withMessages(
                collect($validator->errors()->messages())
                    ->mapWithKeys(fn ($messages, $key) => ["sections.{$index}.{$key}" => $messages])
                    ->all()
            );
        }

        $clean = $validator->validated();
        $content = $this->sanitizeRichText($type, $clean['content'] ?? []);

        return [
            'uuid' => $section['uuid'],
            'parent_uuid' => $section['parent_uuid'] ?? null,
            'type' => $type->key(),
            'schema_version' => $type->schemaVersion(),
            'content' => $content,
            'design' => $clean['design'] ?? [],
            'responsive' => $clean['responsive'] ?? [],
            'is_visible' => (bool) ($section['is_visible'] ?? true),
            'sort_order' => (int) ($section['sort_order'] ?? $index),
        ];
    }

    private function sanitizeRichText(SectionType $type, array $content): array
    {
        foreach ($type->richTextFields() as $path) {
            $content = $this->mapPath($content, explode('.', $path), function ($value) use ($type) {
                return is_string($value)
                    ? ($type->key() === 'custom_html' ? HtmlSanitizer::customHtml($value) : HtmlSanitizer::richText($value))
                    : $value;
            });
        }

        return $content;
    }

    /** Terapkan callback ke path dot yang mendukung wildcard '*'. */
    private function mapPath(array $data, array $segments, callable $callback): array
    {
        $segment = array_shift($segments);
        if ($segment === '*') {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = $segments === [] ? $value : $this->mapPath($value, $segments, $callback);
                }
            }

            return $data;
        }

        if (! Arr::exists($data, $segment)) {
            return $data;
        }

        $data[$segment] = $segments === []
            ? $callback($data[$segment])
            : (is_array($data[$segment]) ? $this->mapPath($data[$segment], $segments, $callback) : $data[$segment]);

        return $data;
    }

    private function assertDepth(array $section, array $byUuid, int $maxDepth): void
    {
        $depth = 1;
        $seen = [$section['uuid'] => true];
        $parent = $section['parent_uuid'] ?? null;

        while ($parent !== null) {
            if (! isset($byUuid[$parent])) {
                $this->fail('sections', 'Parent section tidak ditemukan.');
            }
            if (isset($seen[$parent])) {
                $this->fail('sections', 'Struktur section melingkar.');
            }
            $seen[$parent] = true;
            if (++$depth > $maxDepth) {
                $this->fail('sections', "Kedalaman nested block maksimal {$maxDepth}.");
            }
            $parent = $byUuid[$parent]['parent_uuid'] ?? null;
        }
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
