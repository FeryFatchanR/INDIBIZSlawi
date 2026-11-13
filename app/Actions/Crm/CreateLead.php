<?php

namespace App\Actions\Crm;

use App\Enums\LeadSource;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateLead
{
    /**
     * @param  array<string, mixed>  $data  data tervalidasi
     * @param  array<string, mixed>  $attribution  ['utm' => [...], 'landing' => string]
     */
    public function execute(array $data, LeadSource $source, array $attribution = [], ?User $actor = null, ?string $ip = null): Lead
    {
        return DB::transaction(function () use ($data, $source, $attribution, $actor, $ip) {
            $utm = $attribution['utm'] ?? [];
            $campaign = null;
            $campaignSlug = $utm['campaign'] ?? $utm['utm_campaign'] ?? null;
            if (is_string($campaignSlug) && $campaignSlug !== '') {
                $campaign = Campaign::query()->where('slug', $campaignSlug)->orWhere('utm_campaign', $campaignSlug)->first();
            }

            $lead = new Lead(collect($data)->only([
                'company_name', 'contact_name', 'phone', 'email', 'company_address', 'industry_solution_id', 'industry',
                'product_id', 'bandwidth_requirement', 'locations_count', 'message', 'type', 'follow_up_at',
            ])->all());

            $lead->source = $campaign && $source === LeadSource::WebsiteForm ? LeadSource::Campaign : $source;
            $lead->campaign_id = $campaign?->id;
            $lead->utm = $utm ?: null;
            $lead->landing_page = $attribution['landing'] ?? null;
            $lead->consent_at = $source === LeadSource::WebsiteForm ? now() : null;
            $lead->ip_hash = $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null;
            $lead->save();

            LeadActivity::query()->create([
                'lead_id' => $lead->id,
                'user_id' => $actor?->id,
                'type' => 'created',
                'description' => 'Lead dibuat dari '.$lead->source->label(),
                'properties' => array_filter(['campaign' => $campaign?->name, 'landing' => $lead->landing_page]),
            ]);

            return $lead;
        });
    }
}
