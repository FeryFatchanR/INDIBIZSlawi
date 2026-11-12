<?php

namespace Tests\Feature\Crm;

use App\Actions\Crm\AddLeadNote;
use App\Actions\Crm\AssignLead;
use App\Actions\Crm\ChangeLeadStatus;
use App\Enums\LeadStatus;
use App\Filament\Exports\LeadExporter;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;
use App\Support\Roles;
use Filament\Actions\Testing\TestAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_admin_changes_status_with_history(): void
    {
        $admin = $this->actingAsRole(Roles::ADMINISTRATOR);
        $lead = Lead::factory()->create();

        app(ChangeLeadStatus::class)->execute($lead, LeadStatus::Contacted, $admin, 'Sudah ditelepon');
        app(ChangeLeadStatus::class)->execute($lead, LeadStatus::Won, $admin);

        $lead->refresh();
        $this->assertSame(LeadStatus::Won, $lead->status);
        $this->assertNotNull($lead->won_at);
        $this->assertSame(2, $lead->activities()->where('type', 'status_changed')->count());
        $this->assertDatabaseHas('lead_notes', ['lead_id' => $lead->id, 'body' => 'Sudah ditelepon', 'user_id' => $admin->id]);
    }

    public function test_lost_status_requires_reason(): void
    {
        $admin = $this->actingAsRole(Roles::ADMINISTRATOR);
        $lead = Lead::factory()->create();

        $this->expectException(ValidationException::class);
        app(ChangeLeadStatus::class)->execute($lead, LeadStatus::Lost, $admin);
    }

    public function test_status_change_via_filament_action(): void
    {
        $this->actingAsRole(Roles::ADMINISTRATOR);
        $lead = Lead::factory()->create();

        Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
            ->callAction('change_status', ['status' => 'qualified', 'note' => 'Lolos kualifikasi'])
            ->assertHasNoActionErrors();

        $this->assertSame(LeadStatus::Qualified, $lead->fresh()->status);
    }

    public function test_assign_sales_records_history_and_notifies(): void
    {
        Notification::fake();
        $admin = $this->actingAsRole(Roles::ADMINISTRATOR);
        $sales1 = $this->userWithRole(Roles::SALES);
        $sales2 = $this->userWithRole(Roles::SALES);
        $lead = Lead::factory()->create();

        app(AssignLead::class)->execute($lead, $sales1, $admin);
        app(AssignLead::class)->execute($lead, $sales2, $admin);

        $this->assertSame($sales2->id, $lead->fresh()->assigned_to);
        $this->assertSame(2, $lead->assignments()->count());
        $this->assertSame(1, $lead->assignments()->whereNull('unassigned_at')->count());
        Notification::assertSentTo($sales2, LeadAssignedNotification::class);
    }

    public function test_cannot_assign_to_user_without_lead_access(): void
    {
        $admin = $this->actingAsRole(Roles::ADMINISTRATOR);
        $noAccess = User::factory()->create();

        $this->expectException(ValidationException::class);
        app(AssignLead::class)->execute(Lead::factory()->create(), $noAccess, $admin);
    }

    public function test_sales_can_add_note_and_update_status_on_assigned_lead_only(): void
    {
        $sales = $this->actingAsRole(Roles::SALES);
        $mine = Lead::factory()->assignedTo($sales)->create();
        $other = Lead::factory()->create();

        app(AddLeadNote::class)->execute($mine, $sales, 'Follow-up via telepon', now()->addDay());
        app(ChangeLeadStatus::class)->execute($mine, LeadStatus::Contacted, $sales);
        $this->assertSame(1, $mine->notes()->count());
        $this->assertNotNull($mine->fresh()->follow_up_at);

        $this->expectException(AuthorizationException::class);
        app(AddLeadNote::class)->execute($other, $sales, 'Tidak boleh');
    }

    public function test_sales_cannot_assign_leads(): void
    {
        $sales = $this->actingAsRole(Roles::SALES);
        $lead = Lead::factory()->assignedTo($sales)->create();

        $this->expectException(AuthorizationException::class);
        app(AssignLead::class)->execute($lead, $sales, $sales);
    }

    public function test_sales_only_sees_assigned_leads_idor_protection(): void
    {
        $sales = $this->actingAsRole(Roles::SALES);
        $mine = Lead::factory()->assignedTo($sales)->create();
        $other = Lead::factory()->create();

        Livewire::test(ListLeads::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$other]);

        $this->get(LeadResource::getUrl('view', ['record' => $mine]))->assertOk();
        $this->get(LeadResource::getUrl('view', ['record' => $other]))->assertNotFound();
        $this->get(LeadResource::getUrl('edit', ['record' => $other]))->assertNotFound();
    }

    public function test_marketing_sees_only_assigned_leads(): void
    {
        $marketing = $this->actingAsRole(Roles::MARKETING);
        $mine = Lead::factory()->assignedTo($marketing)->create();
        $other = Lead::factory()->create();

        Livewire::test(ListLeads::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$other]);
    }

    public function test_export_permission(): void
    {
        $this->actingAsRole(Roles::SALES);
        Livewire::test(ListLeads::class)->assertActionHidden(TestAction::make('export')->table());

        $this->actingAsRole(Roles::ADMINISTRATOR);
        Lead::factory()->count(3)->create();
        Livewire::test(ListLeads::class)
            ->assertActionVisible(TestAction::make('export')->table())
            ->callAction(TestAction::make('export')->table())
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('exports', ['exporter' => LeadExporter::class, 'total_rows' => 3]);
        $this->assertDatabaseHas('activity_logs', ['event' => 'leads.exported']);
    }
}
