<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Prospek B2B. status/assigned_to/won_at hanya diubah lewat Action CRM (tidak fillable)
 * agar riwayat follow-up selalu tercatat.
 */
class Lead extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_name', 'contact_name', 'phone', 'email', 'company_address', 'industry_solution_id', 'industry',
        'product_id', 'bandwidth_requirement', 'locations_count', 'message', 'type', 'source', 'campaign_id', 'utm',
        'landing_page', 'follow_up_at', 'consent_at',
    ];

    protected $hidden = ['ip_hash'];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'source' => LeadSource::class,
            'type' => LeadType::class,
            'utm' => 'array',
            'follow_up_at' => 'datetime',
            'won_at' => 'datetime',
            'consent_at' => 'datetime',
            'locations_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $lead) {
            $lead->status ??= LeadStatus::New;
            $lead->type ??= LeadType::Contact;
            $lead->source ??= LeadSource::WebsiteForm;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** Batasi query sesuai hak akses: user tanpa leads.view_all hanya melihat lead miliknya. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('leads.view_all')) {
            return $query;
        }

        return $query->where('assigned_to', $user->getKey());
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function industrySolution(): BelongsTo
    {
        return $this->belongsTo(IndustrySolution::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('created_at')->latest('id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LeadAssignment::class)->latest('assigned_at');
    }
}
