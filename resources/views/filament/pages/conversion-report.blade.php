<x-filament-panels::page>
    <div class="flex items-center gap-3">
        <label for="months" class="text-sm font-medium">Periode</label>
        <select id="months" wire:model.live="months" class="rounded-lg border-gray-300 text-sm dark:bg-gray-900 dark:border-gray-700">
            @foreach ([3 => '3 bulan', 6 => '6 bulan', 12 => '12 bulan'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @php
        $tables = [
            'Per bulan' => ['rows' => $this->byMonth(), 'key' => 'period', 'label' => 'Bulan'],
            'Per sumber' => ['rows' => $this->bySource(), 'key' => 'source', 'label' => 'Sumber'],
            'Per produk' => ['rows' => $this->byProduct(), 'key' => 'product', 'label' => 'Produk'],
        ];
    @endphp

    @foreach ($tables as $heading => $t)
        <x-filament::section :heading="$heading">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b dark:border-white/10">
                        <th class="py-2 pr-4">{{ $t['label'] }}</th><th class="py-2 pr-4">Lead</th><th class="py-2 pr-4">Won</th><th class="py-2">Konversi</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($t['rows'] as $row)
                            <tr class="border-b last:border-0 dark:border-white/5">
                                <td class="py-2 pr-4">{{ $t['key'] === 'source' ? (\App\Enums\LeadSource::tryFrom($row['source'])?->label() ?? $row['source']) : $row[$t['key']] }}</td>
                                <td class="py-2 pr-4">{{ $row['total'] }}</td>
                                <td class="py-2 pr-4">{{ (int) $row['won'] }}</td>
                                <td class="py-2">{{ \App\Filament\Pages\ConversionReport::rate($row['won'], $row['total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-500">Belum ada data pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endforeach
</x-filament-panels::page>
