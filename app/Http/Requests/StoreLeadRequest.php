<?php

namespace App\Http\Requests;

use App\Enums\LeadType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi form lead publik. Field sensitif (status, assigned_to, source) tidak diterima dari klien. */
class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/[^\d+]/', '', (string) $this->input('phone')),
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:180'],
            'contact_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'min:8', 'max:20', 'regex:/^\+?\d{8,19}$/'],
            'email' => ['nullable', 'email:rfc', 'max:180'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'industry_solution_id' => ['nullable', 'integer', Rule::exists('industry_solutions', 'id')->where('is_visible', true)],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('status', 'published')->whereNull('deleted_at')],
            'bandwidth_requirement' => ['nullable', 'string', 'max:120'],
            'locations_count' => ['nullable', 'integer', 'between:1,10000'],
            'message' => ['nullable', 'string', 'max:3000'],
            'type' => ['required', Rule::enum(LeadType::class)],
            'consent' => ['accepted'],
            // Anti-spam: honeypot harus kosong; _ts berisi waktu render form terenkripsi.
            'website' => ['nullable', 'max:0'],
            '_ts' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name' => 'nama perusahaan', 'contact_name' => 'nama kontak', 'phone' => 'nomor telepon',
            'email' => 'email', 'company_address' => 'alamat perusahaan', 'product_id' => 'produk',
            'bandwidth_requirement' => 'kebutuhan bandwidth', 'locations_count' => 'jumlah lokasi', 'message' => 'pesan',
            'consent' => 'persetujuan',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka (boleh diawali +).',
            'consent.accepted' => 'Anda perlu menyetujui agar kami dapat menghubungi Anda.',
        ];
    }
}
