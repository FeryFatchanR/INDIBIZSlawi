<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\WhatsappClick;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Redirect ke wa.me sambil mencatat sumber klik (kampanye/produk) untuk laporan. */
class WhatsAppController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $product = $request->filled('product') ? Product::query()->published()->where('slug', (string) $request->query('product'))->first() : null;
        $campaignSlug = $request->query('campaign') ?? $request->session()->get('attribution.utm.campaign');
        $campaign = is_string($campaignSlug) ? Campaign::query()->active()->where('slug', $campaignSlug)->first() : null;

        $message = $campaign?->whatsapp_message
            ?: ($product ? "Halo, saya tertarik dengan layanan {$product->name} untuk bisnis saya." : null);

        $url = WhatsApp::directUrl($message);
        if (! $url) {
            return redirect()->to('/kontak');
        }

        WhatsappClick::query()->create([
            'campaign_id' => $campaign?->id,
            'product_id' => $product?->id,
            'source_path' => mb_substr((string) parse_url((string) $request->headers->get('referer'), PHP_URL_PATH), 0, 250) ?: null,
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 250),
        ]);

        return redirect()->away($url);
    }
}
