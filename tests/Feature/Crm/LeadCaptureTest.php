<?php

namespace Tests\Feature\Crm;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Product;
use App\Notifications\NewLeadNotification;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'PT Maju Jaya', 'contact_name' => 'Siti', 'phone' => '0812-3456-7890', 'email' => 'Siti@Example.com',
            'type' => 'rfq', 'consent' => '1', 'message' => 'Butuh internet 100 Mbps',
            '_ts' => Crypt::encryptString((string) (time() - 10)), 'website' => '',
        ], $overrides);
    }

    public function test_public_form_creates_lead_with_activity_and_notifies_admins(): void
    {
        Notification::fake();
        $this->seedRoles();
        $admin = $this->userWithRole(Roles::ADMINISTRATOR);
        $sales = $this->userWithRole(Roles::SALES);
        $product = Product::factory()->create();

        $this->post('/leads', $this->payload(['product_id' => $product->id, 'locations_count' => 3]))
            ->assertRedirect()->assertSessionHas('lead_success');

        $lead = Lead::firstOrFail();
        $this->assertSame('PT Maju Jaya', $lead->company_name);
        $this->assertSame('081234567890', $lead->phone);
        $this->assertSame('siti@example.com', $lead->email);
        $this->assertSame(LeadStatus::New, $lead->status);
        $this->assertSame(LeadSource::WebsiteForm, $lead->source);
        $this->assertSame($product->id, $lead->product_id);
        $this->assertSame(3, $lead->locations_count);
        $this->assertNotNull($lead->consent_at);
        $this->assertNull($lead->assigned_to);
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'created']);
        Notification::assertSentTo($admin, NewLeadNotification::class);
        Notification::assertNotSentTo($sales, NewLeadNotification::class);
    }

    public function test_sensitive_fields_from_client_are_ignored(): void
    {
        $this->seedRoles();
        $sales = $this->userWithRole(Roles::SALES);

        $this->post('/leads', $this->payload([
            'status' => 'won', 'assigned_to' => $sales->id, 'source' => 'manual', 'won_at' => now()->toDateTimeString(), 'ip_hash' => 'x',
        ]))->assertRedirect();

        $lead = Lead::firstOrFail();
        $this->assertSame(LeadStatus::New, $lead->status);
        $this->assertNull($lead->assigned_to);
        $this->assertNull($lead->won_at);
        $this->assertSame(LeadSource::WebsiteForm, $lead->source);
    }

    public function test_validation_errors(): void
    {
        $this->post('/leads', $this->payload(['company_name' => '', 'phone' => 'abc', 'email' => 'bukan-email', 'consent' => null, 'type' => 'hack']))
            ->assertSessionHasErrors(['company_name', 'phone', 'email', 'consent', 'type']);

        $this->post('/leads', $this->payload(['product_id' => Product::factory()->draft()->create()->id]))
            ->assertSessionHasErrors(['product_id']);

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_honeypot_and_too_fast_submissions_are_silently_dropped(): void
    {
        $this->post('/leads', $this->payload(['website' => 'http://spam']))->assertSessionHasErrors('website');
        $this->post('/leads', $this->payload(['_ts' => Crypt::encryptString((string) time())]))->assertSessionHas('lead_success');
        $this->post('/leads', $this->payload(['_ts' => 'token-palsu']))->assertSessionHas('lead_success');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_rate_limiting(): void
    {
        config(['cms.leads.rate_limit_per_minute' => 2]);

        $this->post('/leads', $this->payload())->assertRedirect();
        $this->post('/leads', $this->payload())->assertRedirect();
        $this->post('/leads', $this->payload())->assertStatus(429);

        $this->assertDatabaseCount('leads', 2);
    }

    public function test_campaign_attribution_from_utm(): void
    {
        $campaign = Campaign::create(['name' => 'Promo Q4', 'slug' => 'promo-q4', 'utm_campaign' => 'q4-promo', 'is_active' => true]);

        $this->get('/layanan?utm_source=google&utm_campaign=q4-promo')->assertOk();
        $this->post('/leads', $this->payload())->assertRedirect();

        $lead = Lead::firstOrFail();
        $this->assertSame($campaign->id, $lead->campaign_id);
        $this->assertSame(LeadSource::Campaign, $lead->source);
        $this->assertSame('google', $lead->utm['utm_source']);
    }
}
