<x-filament-panels::page>
    @vite(['resources/css/builder.css', 'resources/js/builder.js'])

    <div class="pb-root" x-data="pageBuilder({ selected: @js($selectedUuid) })" x-on:builder-selected.window="highlight($event.detail.uuid, true)">
        @if ($stale)
            <div class="pb-stale" role="alert">
                <span><strong>Halaman telah diubah oleh pengguna lain.</strong> Perubahan Anda tidak disimpan untuk mencegah tertimpanya pekerjaan orang lain.</span>
                <button type="button" class="pb-btn pb-btn--primary" wire:click="reloadFromServer">Muat versi terbaru</button>
            </div>
        @endif

        <div class="pb-status">
            <span class="pb-badge pb-badge--{{ $record->status->value }}">{{ $record->status->label() }}</span>
            @if ($record->hasUnpublishedChanges())
                <span class="pb-badge pb-badge--pending">Ada perubahan draft yang belum dipublikasikan</span>
            @endif
            <span class="pb-muted">Perubahan otomatis tersimpan sebagai draft · versi {{ $lockVersion }}</span>
            <span class="pb-muted" wire:loading.delay>Menyimpan…</span>
        </div>

        <div class="pb-grid">
            {{-- LEFT: Component library + struktur halaman --}}
            <aside class="pb-panel pb-left" aria-label="Komponen dan struktur halaman">
                <div class="pb-panel-head">Komponen</div>
                <p class="pb-hint">Seret ke struktur/canvas, atau klik untuk menambah setelah section terpilih.</p>
                <div class="pb-library" x-ref="library">
                    @foreach ($this->getLibrary() as $category => $types)
                        <div class="pb-lib-group">
                            <div class="pb-lib-title">{{ $category }}</div>
                            <div class="pb-lib-items" data-library>
                                @foreach ($types as $key => $type)
                                    <button type="button" class="pb-chip" data-type="{{ $key }}" wire:click="addSection('{{ $key }}')" title="{{ $type->label() }}">
                                        <x-filament::icon :icon="$type->icon()" class="pb-chip-icon" />
                                        <span>{{ $type->label() }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pb-panel-head">Struktur halaman <span class="pb-muted">({{ count($sections) }})</span></div>
                <ol class="pb-outline" x-ref="outline" aria-label="Urutan section (Alt+↑/↓ untuk memindahkan)">
                    @forelse ($sections as $i => $section)
                        <li wire:key="outline-{{ $section['uuid'] }}" data-uuid="{{ $section['uuid'] }}" tabindex="0"
                            class="pb-row {{ $selectedUuid === $section['uuid'] ? 'is-selected' : '' }} {{ ($section['is_visible'] ?? true) ? '' : 'is-hidden' }}"
                            x-on:keydown.alt.arrow-up.prevent="$wire.moveSection('{{ $section['uuid'] }}', 'up')"
                            x-on:keydown.alt.arrow-down.prevent="$wire.moveSection('{{ $section['uuid'] }}', 'down')"
                            x-on:keydown.enter.prevent="$wire.selectSection('{{ $section['uuid'] }}')">
                            <span class="pb-handle" title="Seret untuk mengurutkan" aria-hidden="true">⋮⋮</span>
                            <button type="button" class="pb-row-label" wire:click="selectSection('{{ $section['uuid'] }}')">
                                <x-filament::icon :icon="$this->sectionIcon($section['type'])" class="pb-row-icon" />
                                <span>{{ $this->sectionLabel($section) }}</span>
                            </button>
                            <span class="pb-row-actions">
                                <button type="button" title="Naik" aria-label="Pindah ke atas" wire:click="moveSection('{{ $section['uuid'] }}', 'up')" @disabled($i === 0)>↑</button>
                                <button type="button" title="Turun" aria-label="Pindah ke bawah" wire:click="moveSection('{{ $section['uuid'] }}', 'down')" @disabled($loop->last)>↓</button>
                                <button type="button" title="Duplikat" aria-label="Duplikat" wire:click="duplicateSection('{{ $section['uuid'] }}')">⧉</button>
                                <button type="button" title="{{ ($section['is_visible'] ?? true) ? 'Sembunyikan' : 'Tampilkan' }}" aria-label="Tampil/sembunyi" wire:click="toggleVisibility('{{ $section['uuid'] }}')">{{ ($section['is_visible'] ?? true) ? '◉' : '○' }}</button>
                                <button type="button" title="Hapus" aria-label="Hapus" class="pb-danger" wire:click="deleteSection('{{ $section['uuid'] }}')" wire:confirm="Hapus section ini? Anda masih bisa memulihkannya dari riwayat revisi.">✕</button>
                            </span>
                        </li>
                    @empty
                        <li class="pb-empty">Belum ada section. Seret komponen ke sini.</li>
                    @endforelse
                </ol>
            </aside>

            {{-- CENTER: Live canvas --}}
            <main class="pb-panel pb-center" aria-label="Canvas">
                <div class="pb-toolbar">
                    <div class="pb-devices" role="group" aria-label="Pratinjau perangkat">
                        @foreach (['desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile'] as $key => $label)
                            <button type="button" wire:click="setDevice('{{ $key }}')" class="{{ $device === $key ? 'is-active' : '' }}" aria-pressed="{{ $device === $key ? 'true' : 'false' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <button type="button" class="pb-btn" x-on:click="$refs.frame.contentWindow.location.reload()">Muat ulang canvas</button>
                </div>
                <div class="pb-stage" x-ref="stage" x-on:resize.window.debounce.100ms="fit()">
                    <div class="pb-frame-box" x-ref="box">
                        <div class="pb-frame pb-frame--{{ $device }}" x-ref="scaler" data-device-width="{{ ['desktop' => 1280, 'tablet' => 768, 'mobile' => 390][$device] }}">
                            <iframe x-ref="frame" src="{{ $this->canvasUrl() }}" title="Canvas halaman" x-on:load="highlight(selected, false); fit()"></iframe>
                        </div>
                    </div>
                    <p class="pb-scale-note" x-show="scale < 1" x-text="'Pratinjau ' + Math.round(scale * 100) + '% — lebar asli ' + deviceWidth + 'px'"></p>
                    <div class="pb-dropzone" x-ref="dropzone" x-show="dragging" x-cloak>
                        <span>Lepaskan untuk menambah section {{ $selectedUuid ? 'setelah section terpilih' : 'di akhir halaman' }}</span>
                    </div>
                </div>
            </main>

            {{-- RIGHT: Property editor --}}
            <aside class="pb-panel pb-right" aria-label="Properti section">
                @if ($type = $this->selectedType())
                    <div class="pb-panel-head">
                        <span>{{ $type->label() }}</span>
                        <button type="button" class="pb-link" wire:click="selectSection(null)">Tutup</button>
                    </div>
                    <div class="pb-props" x-on:input.debounce.900ms="autoApply && $wire.applyProperties()" x-on:change.debounce.600ms="autoApply && $wire.applyProperties()">
                        {{ $this->propertiesForm }}
                    </div>
                    <div class="pb-props-foot">
                        <label class="pb-muted"><input type="checkbox" x-model="autoApply"> Terapkan otomatis</label>
                        <button type="button" class="pb-btn pb-btn--primary" wire:click="applyProperties" wire:loading.attr="disabled">Terapkan</button>
                    </div>
                @else
                    <div class="pb-panel-head">Properti</div>
                    <p class="pb-hint">Pilih section di canvas atau di struktur halaman untuk mengubah konten, desain, dan pengaturan responsif.</p>
                @endif
            </aside>
        </div>
    </div>
</x-filament-panels::page>
