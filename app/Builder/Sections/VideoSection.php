<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;

class VideoSection extends SectionType
{
    public function key(): string
    {
        return 'video';
    }

    public function label(): string
    {
        return 'Video';
    }

    public function category(): string
    {
        return 'Media';
    }

    public function icon(): string
    {
        return 'heroicon-o-play-circle';
    }

    public function view(): string
    {
        return 'builder.video';
    }

    public function defaultContent(): array
    {
        return ['heading' => null, 'provider' => 'youtube', 'video_id' => null, 'media_id' => null, 'caption' => null];
    }

    public function defaultDesign(): array
    {
        return ['container' => 'narrow'];
    }

    public function contentRules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:200'],
            'provider' => ['required', 'in:youtube,vimeo,upload'],
            // Hanya ID video (bukan URL bebas) → iframe dibangun dari host yang di-whitelist.
            'video_id' => ['nullable', 'required_unless:provider,upload', 'string', 'max:32', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'media_id' => ['nullable', 'required_if:provider,upload', 'integer', 'exists:media_assets,id'],
            'caption' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function mediaFields(): array
    {
        return ['media_id'];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul (opsional)', 200),
            Fields::select('provider', 'Sumber video', ['youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'upload' => 'Upload (Media Library)'])->live(),
            Fields::text('video_id', 'ID video', 32)->helperText('Contoh YouTube: dQw4w9WgXcQ (bagian setelah v=).')
                ->visible(fn ($get) => $get('provider') !== 'upload'),
            Fields::media('media_id', 'File video', 'video')->visible(fn ($get) => $get('provider') === 'upload'),
            Fields::text('caption', 'Keterangan', 300),
        ];
    }
}
