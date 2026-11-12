<?php

namespace App\Support;

use App\Models\MediaAsset;
use App\Models\User;
use finfo;
use GdImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Upload media yang aman:
 * - MIME dideteksi dari ISI file (finfo), bukan dari nama/Content-Type klien.
 * - Ekstensi ditentukan server dari MIME allowlist; nama file acak (UUID).
 * - Gambar JPEG/PNG/WebP di-re-encode via GD (membuang metadata EXIF/GPS & payload sisipan).
 * - Varian WebP responsif dibuat untuk srcset.
 */
class MediaUploader
{
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
        'video/mp4' => 'mp4', 'video/webm' => 'webm', 'application/pdf' => 'pdf',
    ];

    public function store(string $absolutePath, string $originalName, ?int $folderId = null, ?User $user = null, ?MediaAsset $replace = null): MediaAsset
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            $this->fail('File tidak ditemukan.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($absolutePath) ?: '';
        $kind = $this->kindFor($mime);
        $size = (int) filesize($absolutePath);
        $limitKb = (int) config("cms.media.max_{$kind}_kb");
        if ($size <= 0 || $size > $limitKb * 1024) {
            $this->fail("Ukuran file melebihi batas {$limitKb} KB.");
        }

        $width = $height = null;
        if ($kind === 'image') {
            $info = @getimagesize($absolutePath);
            if ($info === false || ($info['mime'] ?? null) !== $mime) {
                $this->fail('File gambar rusak atau tidak valid.');
            }
            [$width, $height] = $info;
            if ($width > 8000 || $height > 8000) {
                $this->fail('Dimensi gambar maksimal 8000×8000 piksel.');
            }
        }

        $disk = (string) config('cms.media.disk', 'public');
        $uuid = (string) Str::uuid();
        $dir = 'media/'.now()->format('Y/m');
        $ext = self::EXTENSIONS[$mime];
        $path = "{$dir}/{$uuid}.{$ext}";
        $written = [];

        try {
            if ($kind === 'image' && $mime !== 'image/gif') {
                $image = $this->load($absolutePath, $mime);
                Storage::disk($disk)->put($path, $this->encode($image, $mime));
                $written[] = $path;
                $variants = $this->variants($image, $width, $disk, "{$dir}/{$uuid}", $written);
                imagedestroy($image);
            } else {
                Storage::disk($disk)->putFileAs($dir, $absolutePath, "{$uuid}.{$ext}");
                $written[] = $path;
                $variants = null;
            }

            return DB::transaction(function () use ($replace, $folderId, $originalName, $mime, $ext, $kind, $size, $width, $height, $disk, $path, $variants, $user, $uuid) {
                $asset = $replace ?? new MediaAsset;
                $oldFiles = $replace ? $this->filesOf($replace) : [];

                $asset->forceFill([
                    'uuid' => $replace?->uuid ?? $uuid,
                    'folder_id' => $replace ? $replace->folder_id : $folderId,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => mb_substr(basename($originalName), 0, 255),
                    'mime_type' => $mime,
                    'extension' => $ext,
                    'kind' => $kind,
                    'size' => $size,
                    'width' => $width,
                    'height' => $height,
                    'variants' => $variants,
                    'uploaded_by' => $user?->id ?? $asset->uploaded_by,
                ])->save();

                if ($oldFiles !== []) {
                    DB::afterCommit(fn () => Storage::disk($replace->disk)->delete($oldFiles));
                }

                return $asset;
            });
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($written);
            throw $e;
        }
    }

    /** Hapus permanen file fisik sebuah asset (dipanggil saat force delete). */
    public function deleteFiles(MediaAsset $asset): void
    {
        Storage::disk($asset->disk)->delete($this->filesOf($asset));
    }

    /** @return list<string> */
    private function filesOf(MediaAsset $asset): array
    {
        return array_values(array_filter([$asset->path, ...array_column($asset->variants ?? [], 'path')]));
    }

    private function kindFor(string $mime): string
    {
        return match (true) {
            in_array($mime, config('cms.media.image_mimes'), true) => 'image',
            in_array($mime, config('cms.media.video_mimes'), true) => 'video',
            in_array($mime, config('cms.media.document_mimes'), true) => 'document',
            default => $this->fail('Tipe file tidak diizinkan. Gunakan JPG, PNG, WebP, GIF, MP4, WebM, atau PDF.'),
        };
    }

    private function load(string $path, string $mime): GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
        if (! $image instanceof GdImage) {
            $this->fail('Gambar tidak dapat diproses.');
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    private function encode(GdImage $image, string $mime): string
    {
        ob_start();
        match ($mime) {
            'image/jpeg' => imagejpeg($image, null, 85),
            'image/png' => imagepng($image, null, 6),
            default => imagewebp($image, null, 82),
        };

        return (string) ob_get_clean();
    }

    /** @return list<array{width:int,path:string}> */
    private function variants(GdImage $image, int $originalWidth, string $disk, string $base, array &$written): array
    {
        $variants = [];
        foreach (config('cms.media.variant_widths', []) as $target) {
            $target = (int) $target;
            if ($target >= $originalWidth) {
                continue;
            }
            $scaled = imagescale($image, $target);
            if (! $scaled instanceof GdImage) {
                continue;
            }
            imagesavealpha($scaled, true);
            $variantPath = "{$base}-{$target}.webp";
            Storage::disk($disk)->put($variantPath, $this->encode($scaled, 'image/webp'));
            $written[] = $variantPath;
            $variants[] = ['width' => $target, 'path' => $variantPath];
            imagedestroy($scaled);
        }
        // Varian ukuran asli (WebP) agar browser modern selalu mendapat format ringan.
        $fullPath = "{$base}-{$originalWidth}.webp";
        Storage::disk($disk)->put($fullPath, $this->encode($image, 'image/webp'));
        $written[] = $fullPath;
        $variants[] = ['width' => $originalWidth, 'path' => $fullPath];

        return $variants;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
