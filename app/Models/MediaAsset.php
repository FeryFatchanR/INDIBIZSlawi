<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    /** Metadata file diisi oleh MediaUploader, bukan dari input pengguna. */
    protected $fillable = ['folder_id', 'alt', 'title'];

    protected function casts(): array
    {
        return ['variants' => 'array', 'size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    protected static function booted(): void
    {
        // File fisik dihapus hanya saat force delete (soft delete tetap bisa dipulihkan).
        static::forceDeleted(fn (self $asset) => app(\App\Support\MediaUploader::class)->deleteFiles($asset));
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    public function isImage(): bool
    {
        return $this->kind === 'image';
    }

    public function url(?int $width = null): string
    {
        $path = $this->path;
        if ($width && $this->isImage() && is_array($this->variants)) {
            foreach ($this->variants as $variant) {
                if ((int) ($variant['width'] ?? 0) >= $width) {
                    $path = $variant['path'];
                    break;
                }
            }
        }

        return Storage::disk($this->disk)->url($path);
    }

    /** srcset dari varian WebP responsif. */
    public function srcset(): ?string
    {
        if (! $this->isImage() || empty($this->variants)) {
            return null;
        }

        return collect($this->variants)
            ->map(fn (array $v) => Storage::disk($this->disk)->url($v['path']).' '.$v['width'].'w')
            ->implode(', ');
    }

    public function isInUse(): bool
    {
        return $this->usages()->exists();
    }

    public function activityLabel(): string
    {
        return $this->original_name;
    }
}
