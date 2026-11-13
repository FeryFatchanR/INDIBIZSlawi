<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Cache konten publik</x-slot>
        <p class="text-sm">Versi cache saat ini: <strong>{{ $this->getCacheVersion() }}</strong></p>
        <p class="mt-2 text-sm text-gray-500">Cache otomatis diperbarui setiap kali konten, menu, pengaturan, atau halaman dipublikasikan.
            Gunakan tombol di atas hanya bila tampilan website belum berubah setelah perubahan konten.
            Cache aplikasi (config/route/view) dikelola saat deployment melalui <code>php artisan optimize</code>.</p>
    </x-filament::section>
</x-filament-panels::page>
