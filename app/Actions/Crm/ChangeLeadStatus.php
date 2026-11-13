<?php

namespace App\Actions\Crm;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ChangeLeadStatus
{
    public function execute(Lead $lead, LeadStatus $status, User $actor, ?string $note = null, ?string $lostReason = null, ?\DateTimeInterface $followUpAt = null): Lead
    {
        Gate::forUser($actor)->authorize('changeStatus', $lead);

        if ($status === LeadStatus::Lost && blank($lostReason)) {
            throw ValidationException::withMessages(['lost_reason' => 'Alasan wajib diisi untuk status Lost.']);
        }

        return DB::transaction(function () use ($lead, $status, $actor, $note, $lostReason, $followUpAt) {
            /** @var Lead $locked */
            $locked = Lead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            $locked->status = $status;
            $locked->won_at = $status === LeadStatus::Won ? ($locked->won_at ?? now()) : null;
            $locked->lost_reason = $status === LeadStatus::Lost ? mb_substr((string) $lostReason, 0, 255) : null;
            if ($followUpAt !== null) {
                $locked->follow_up_at = $followUpAt;
            }
            $locked->save();

            LeadActivity::query()->create([
                'lead_id' => $locked->id,
                'user_id' => $actor->id,
                'type' => 'status_changed',
                'description' => "Status: {$from->label()} → {$status->label()}",
                'properties' => array_filter(['from' => $from->value, 'to' => $status->value, 'note' => $note, 'lost_reason' => $locked->lost_reason,
                    'follow_up_at' => $followUpAt?->format('Y-m-d H:i')]),
            ]);

            if (filled($note)) {
                app(AddLeadNote::class)->store($locked, $actor, (string) $note, logActivity: false);
            }

            return $locked;
        });
    }
}
