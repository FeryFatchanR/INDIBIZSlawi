<?php

namespace App\Actions\Crm;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadAssignment;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssignLead
{
    public function execute(Lead $lead, ?User $assignee, User $actor): Lead
    {
        Gate::forUser($actor)->authorize('assign', $lead);

        if ($assignee && (! $assignee->is_active || ! ($assignee->can('leads.view_assigned') || $assignee->can('leads.view_all')))) {
            throw ValidationException::withMessages(['assigned_to' => 'Pengguna tidak aktif atau tidak memiliki akses lead.']);
        }

        $lead = DB::transaction(function () use ($lead, $assignee, $actor) {
            /** @var Lead $locked */
            $locked = Lead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();
            if ($locked->assigned_to === $assignee?->id) {
                return $locked;
            }

            LeadAssignment::query()->where('lead_id', $locked->id)->whereNull('unassigned_at')->update(['unassigned_at' => now()]);
            if ($assignee) {
                LeadAssignment::query()->create([
                    'lead_id' => $locked->id, 'assigned_to' => $assignee->id, 'assigned_by' => $actor->id, 'assigned_at' => now(),
                ]);
            }

            $locked->assigned_to = $assignee?->id;
            $locked->save();

            LeadActivity::query()->create([
                'lead_id' => $locked->id,
                'user_id' => $actor->id,
                'type' => 'assigned',
                'description' => $assignee ? "Di-assign ke {$assignee->name}" : 'Assignment dilepas',
                'properties' => ['assigned_to' => $assignee?->id],
            ]);

            return $locked;
        });

        if ($assignee && ! $assignee->is($actor)) {
            $assignee->notify(new LeadAssignedNotification($lead));
        }

        return $lead;
    }
}
