<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<MediaAsset> */
class MediaAssetFactory extends Factory
{
    public function definition(): array
    {
        return [];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (MediaAsset $asset) {
            $uuid = (string) Str::uuid();
            $asset->forceFill([
                'uuid' => $uuid, 'disk' => 'public', 'path' => "media/test/{$uuid}.jpg", 'original_name' => 'foto.jpg',
                'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'kind' => 'image', 'size' => 1000, 'width' => 800, 'height' => 600,
            ]);
        });
    }
}
