<?php

namespace App\Actions\Crm;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadNote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AddLeadNote
{
    public function execute(Lead $lead, User $actor, string $body, ?\DateTimeInterface $followUpAt = null): LeadNote
    {
        Gate::forUser($actor)->authorize('addNote', $lead);

        return DB::transaction(function () use ($lead, $actor, $body, $followUpAt) {
            $note = $this->store($lead, $actor, $body);
            if ($followUpAt) {
                $lead->forceFill(['follow_up_at' => $followUpAt])->save();
                LeadActivity::query()->create([
                    'lead_id' => $lead->id, 'user_id' => $actor->id, 'type' => 'follow_up_set',
                    'description' => 'Jadwal follow-up: '.$followUpAt->format('d M Y H:i'),
                ]);
            }

            return $note;
        });
    }

    /** Simpan catatan tanpa cek otorisasi (dipanggil dari Action lain yang sudah mengotorisasi). */
    public function store(Lead $lead, User $actor, string $body, bool $logActivity = true): LeadNote
    {
        $body = trim(strip_tags($body));
        if ($body === '' || mb_strlen($body) > 5000) {
            throw ValidationException::withMessages(['body' => 'Catatan wajib diisi (maks. 5000 karakter).']);
        }

        $note = new LeadNote(['body' => $body]);
        $note->lead_id = $lead->id;
        $note->user_id = $actor->id;
        $note->save();

        if ($logActivity) {
            LeadActivity::query()->create([
                'lead_id' => $lead->id, 'user_id' => $actor->id, 'type' => 'note_added',
                'description' => 'Catatan: '.mb_strimwidth($body, 0, 120, '…'),
            ]);
        }

        return $note;
    }
}
