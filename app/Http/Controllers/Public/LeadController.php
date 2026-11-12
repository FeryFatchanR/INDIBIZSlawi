<?php

namespace App\Http\Controllers\Public;

use App\Actions\Crm\CreateLead;
use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Notifications\NewLeadNotification;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request, CreateLead $createLead): RedirectResponse
    {
        if ($this->looksLikeBot((string) $request->input('_ts'))) {
            // Jangan beri sinyal ke bot; respons sama seperti sukses, tanpa menyimpan data.
            Log::info('Lead diabaikan: terdeteksi bot (waktu isi form terlalu cepat/tidak valid).');

            return back()->with('lead_success', true);
        }

        $lead = $createLead->execute(
            $request->safe()->except(['consent', 'website', '_ts']),
            LeadSource::WebsiteForm,
            ['utm' => $request->session()->get('attribution.utm', []), 'landing' => $request->session()->get('attribution.landing')],
            null,
            $request->ip(),
        );

        $recipients = User::permission('leads.view_all')->where('is_active', true)->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new NewLeadNotification($lead));
        }

        return back()->with('lead_success', true)->withFragment('lead-form');
    }

    private function looksLikeBot(string $token): bool
    {
        try {
            $renderedAt = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return true;
        }

        $elapsed = time() - $renderedAt;

        return $elapsed < (int) config('cms.leads.min_fill_seconds', 3) || $elapsed > 86400;
    }
}
