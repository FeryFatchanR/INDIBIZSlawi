{{--
  Form lead (contact / RFQ / consultation). Field status/source/assigned TIDAK ada di form;
  ditentukan server. Anti-spam: honeypot "website" + token waktu "_ts" terenkripsi.
--}}
@php
    $formType = in_array($formType ?? 'contact', ['contact', 'rfq', 'consultation'], true) ? $formType : 'contact';
    // Produk hanya terpilih bila memang dikirim (?produk=slug) atau ditentukan halaman produk.
    $productFromQuery = (request()->filled('produk') && isset($products))
        ? $products->firstWhere('slug', (string) request('produk'))?->id
        : null;
    $selectedProduct = old('product_id', $selectedProductId ?? $productFromQuery);
    $disabled = $editor ?? false;
@endphp
<div id="lead-form">
    @if (session('lead_success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-emerald-900" role="status">
            <p class="font-semibold">Terima kasih! Permintaan Anda sudah kami terima.</p>
            <p class="mt-1 text-sm">Tim kami akan segera menghubungi Anda.</p>
        </div>
    @else
        <form method="POST" action="{{ route('leads.store') }}" class="grid gap-4 sm:grid-cols-2" novalidate>
            @csrf
            <input type="hidden" name="type" value="{{ $formType }}">
            <input type="hidden" name="_ts" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) now()->timestamp) }}">
            <div class="hidden" aria-hidden="true">
                <label for="lead-website-{{ $formId ?? 'f' }}">Website</label>
                <input type="text" id="lead-website-{{ $formId ?? 'f' }}" name="website" tabindex="-1" autocomplete="off">
            </div>

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 sm:col-span-2" role="alert">
                    Mohon periksa kembali isian yang ditandai.
                </div>
            @endif

            @php
                $id = fn ($n) => 'lead-'.$n.'-'.($formId ?? 'f');
            @endphp

            @foreach ([
                ['company_name', 'Nama perusahaan / instansi', 'text', true, 'organization'],
                ['contact_name', 'Nama kontak', 'text', true, 'name'],
                ['phone', 'Nomor telepon / WhatsApp', 'tel', true, 'tel'],
                ['email', 'Email', 'email', false, 'email'],
            ] as [$name, $label, $type, $required, $autocomplete])
                <div>
                    <label for="{{ $id($name) }}" class="form-label">{{ $label }} @if ($required)<span class="text-red-600">*</span>@endif</label>
                    <input id="{{ $id($name) }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name) }}" autocomplete="{{ $autocomplete }}"
                        @if ($required) required @endif maxlength="180" class="form-input @error($name) border-red-500 @enderror" @disabled($disabled)
                        @error($name) aria-invalid="true" aria-describedby="{{ $id($name) }}-error" @enderror>
                    @error($name)<p id="{{ $id($name) }}-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            @endforeach

            @if ($showRfqFields ?? true)
                <div>
                    <label for="{{ $id('product_id') }}" class="form-label">Produk yang diminati</label>
                    <select id="{{ $id('product_id') }}" name="product_id" class="form-input" @disabled($disabled)>
                        <option value="">— Pilih produk —</option>
                        @foreach ($products ?? [] as $p)
                            <option value="{{ $p->id }}" @selected((string) $selectedProduct === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('product_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="{{ $id('industry_solution_id') }}" class="form-label">Industri</label>
                    <select id="{{ $id('industry_solution_id') }}" name="industry_solution_id" class="form-input" @disabled($disabled)>
                        <option value="">— Pilih industri —</option>
                        @foreach ($industries ?? [] as $ind)
                            <option value="{{ $ind->id }}" @selected((string) old('industry_solution_id') === (string) $ind->id)>{{ $ind->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="{{ $id('bandwidth_requirement') }}" class="form-label">Kebutuhan bandwidth</label>
                    <input id="{{ $id('bandwidth_requirement') }}" name="bandwidth_requirement" value="{{ old('bandwidth_requirement') }}" maxlength="120" placeholder="mis. 100 Mbps" class="form-input" @disabled($disabled)>
                </div>
                <div>
                    <label for="{{ $id('locations_count') }}" class="form-label">Jumlah lokasi</label>
                    <input id="{{ $id('locations_count') }}" type="number" min="1" max="10000" name="locations_count" value="{{ old('locations_count') }}" class="form-input" @disabled($disabled)>
                    @error('locations_count')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="{{ $id('company_address') }}" class="form-label">Alamat perusahaan</label>
                    <input id="{{ $id('company_address') }}" name="company_address" value="{{ old('company_address') }}" maxlength="500" autocomplete="street-address" class="form-input" @disabled($disabled)>
                </div>
            @endif

            <div class="sm:col-span-2">
                <label for="{{ $id('message') }}" class="form-label">Pesan / kebutuhan</label>
                <textarea id="{{ $id('message') }}" name="message" rows="4" maxlength="3000" class="form-input" @disabled($disabled)>{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="flex items-start gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="consent" value="1" @checked(old('consent')) class="mt-1 rounded border-slate-300" required @disabled($disabled)>
                    <span>Saya setuju dihubungi oleh tim kami terkait permintaan ini. Data digunakan hanya untuk keperluan penawaran layanan.</span>
                </label>
                @error('consent')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="w-full sm:w-auto {{ \App\Builder\Style\StyleTokens::button('primary') }}" @disabled($disabled)>{{ $submitLabel ?? 'Kirim' }}</button>
            </div>
        </form>
    @endif
</div>
