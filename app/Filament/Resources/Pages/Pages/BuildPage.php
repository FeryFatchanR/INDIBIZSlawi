<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Actions\Cms\PublishPage;
use App\Actions\Cms\SaveDraft;
use App\Actions\Cms\UnpublishPage;
use App\Builder\Contracts\SectionType;
use App\Builder\SectionRegistry;
use App\Builder\Style\StyleTokens;
use App\Exceptions\StaleDraftException;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page as PageModel;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

/**
 * Visual Page Builder. Server adalah sumber kebenaran: setiap mutasi divalidasi lalu disimpan
 * sebagai draft (SaveDraft) dengan optimistic locking. Section diidentifikasi UUID stabil.
 */
class BuildPage extends Page
{
    use InteractsWithRecord;

    protected static string $resource = PageResource::class;

    protected string $view = 'filament.pages.page-builder';

    protected Width|string|null $maxContentWidth = Width::Full;

    /** @var list<array<string, mixed>> */
    public array $sections = [];

    public ?string $selectedUuid = null;

    /** State form property editor (content/design/responsive/is_visible). */
    public array $selectedData = [];

    #[Locked]
    public int $lockVersion = 0;

    public string $device = 'desktop';

    public int $canvasVersion = 1;

    public bool $stale = false;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        abort_unless(auth()->user()->can('build', $this->record), 403);
        $this->loadFromDatabase();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Builder: '.$this->record->title;
    }

    public function getBreadcrumbs(): array
    {
        return [PageResource::getUrl() => 'Pages', '#' => $this->record->title];
    }

    /* ----------------------------------------------------------------- */
    /* Data untuk view                                                    */
    /* ----------------------------------------------------------------- */

    /** @return array<string, array<string, SectionType>> */
    public function getLibrary(): array
    {
        $user = auth()->user();
        $groups = [];
        foreach (app(SectionRegistry::class)->grouped() as $category => $types) {
            $allowed = array_filter($types, fn (SectionType $t) => ! $t->permission() || $user->can($t->permission()));
            if ($allowed !== []) {
                $groups[$category] = $allowed;
            }
        }

        return $groups;
    }

    public function sectionLabel(array $section): string
    {
        $registry = app(SectionRegistry::class);
        $type = $registry->has($section['type']) ? $registry->get($section['type'])->label() : $section['type'];
        $hint = $section['content']['heading'] ?? $section['content']['title'] ?? null;

        return $hint ? $type.' — '.Str::limit(strip_tags((string) $hint), 28) : $type;
    }

    public function sectionIcon(string $typeKey): string
    {
        $registry = app(SectionRegistry::class);

        return $registry->has($typeKey) ? $registry->get($typeKey)->icon() : 'heroicon-o-question-mark-circle';
    }

    public function canvasUrl(): string
    {
        return route('pages.preview', ['page' => $this->record, 'canvas' => 1, 'v' => $this->canvasVersion]);
    }

    public function selectedType(): ?SectionType
    {
        $section = $this->findSection($this->selectedUuid);

        return $section ? app(SectionRegistry::class)->get($section['type']) : null;
    }

    /* ----------------------------------------------------------------- */
    /* Property editor (Filament schema dinamis per tipe section)          */
    /* ----------------------------------------------------------------- */

    public function propertiesForm(Schema $schema): Schema
    {
        $type = $this->selectedType();
        if (! $type) {
            return $schema->components([])->statePath('selectedData');
        }

        $responsiveTabs = [];
        foreach (['desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile'] as $device => $label) {
            $fields = [
                Toggle::make("{$device}.visible")->label("Tampil di {$label}")->default(true),
                Select::make("{$device}.padding_y")->label('Padding vertikal')->options(StyleTokens::SPACING)->placeholder('Ikuti '.($device === 'desktop' ? 'desain' : 'ukuran lebih besar')),
                Select::make("{$device}.text_align")->label('Perataan teks')->options(StyleTokens::ALIGNMENTS)->placeholder('Ikuti '.($device === 'desktop' ? 'desain' : 'ukuran lebih besar')),
            ];
            if ($type->supportsColumns()) {
                $fields[] = Select::make("{$device}.columns")->label('Jumlah kolom')->options(array_combine(range(1, 6), range(1, 6)));
            }
            $responsiveTabs[] = Tab::make($label)->schema($fields);
        }

        return $schema
            ->statePath('selectedData')
            ->components([
                Tabs::make('props')->contained(false)->tabs([
                    Tab::make('Konten')->icon('heroicon-o-pencil-square')->schema([
                        Group::make($type->contentFields())->statePath('content'),
                    ]),
                    Tab::make('Desain')->icon('heroicon-o-swatch')->schema([
                        Group::make([
                            ...$type->designFields(),
                            Select::make('background')->label('Latar belakang')->options(StyleTokens::BACKGROUNDS)->live(),
                            ColorPicker::make('background_color')->label('Warna latar kustom')->regex(StyleTokens::HEX_PATTERN)
                                ->visible(fn ($get) => $get('background') === 'custom'),
                            Select::make('text_color')->label('Warna teks')->options(StyleTokens::TEXT_COLORS)->live(),
                            ColorPicker::make('text_hex')->label('Warna teks kustom')->regex(StyleTokens::HEX_PATTERN)
                                ->visible(fn ($get) => $get('text_color') === 'custom'),
                            Select::make('container')->label('Lebar konten')->options(StyleTokens::CONTAINERS),
                            Select::make('text_align')->label('Perataan teks')->options(StyleTokens::ALIGNMENTS),
                            Select::make('padding_y')->label('Padding vertikal')->options(StyleTokens::SPACING),
                            Select::make('margin_top')->label('Margin atas')->options(StyleTokens::SPACING),
                            Select::make('margin_bottom')->label('Margin bawah')->options(StyleTokens::SPACING),
                            TextInput::make('anchor_id')->label('Anchor ID (untuk link #)')->maxLength(60)->regex('/^[a-z][a-z0-9\-]*$/'),
                        ])->statePath('design'),
                    ]),
                    Tab::make('Responsif')->icon('heroicon-o-device-phone-mobile')->schema([
                        Group::make([Tabs::make('devices')->tabs($responsiveTabs)])->statePath('responsive'),
                    ]),
                    Tab::make('Visibilitas')->icon('heroicon-o-eye')->schema([
                        Toggle::make('is_visible')->label('Tampilkan section ini')
                            ->helperText('Section tersembunyi tetap tersimpan, tidak tampil di website.'),
                    ]),
                ]),
            ]);
    }

    /* ----------------------------------------------------------------- */
    /* Aksi builder (semua dipanggil dari Alpine/Livewire)               */
    /* ----------------------------------------------------------------- */

    public function selectSection(?string $uuid): void
    {
        $section = $this->findSection($uuid);
        $this->selectedUuid = $section['uuid'] ?? null;
        $this->resetPropertiesForm();
        $this->dispatch('builder-selected', uuid: $this->selectedUuid);
    }

    public function addSection(string $type, ?int $index = null): void
    {
        $this->guard();
        $registry = app(SectionRegistry::class);
        if (! $registry->has($type)) {
            Notification::make()->title('Tipe komponen tidak dikenal.')->danger()->send();

            return;
        }
        $definition = $registry->get($type);
        if ($definition->permission() && ! auth()->user()->can($definition->permission())) {
            Notification::make()->title('Anda tidak memiliki izin untuk komponen ini.')->danger()->send();

            return;
        }

        $defaults = $definition->defaults();
        $section = [
            'uuid' => (string) Str::uuid(),
            'parent_uuid' => null,
            'type' => $type,
            'schema_version' => $definition->schemaVersion(),
            'content' => $defaults['content'],
            'design' => $defaults['design'],
            'responsive' => $defaults['responsive'],
            'is_visible' => true,
        ];

        $sections = $this->sections;
        $index ??= $this->selectedUuid !== null ? $this->indexOf($this->selectedUuid) + 1 : count($sections);
        array_splice($sections, max(0, min($index, count($sections))), 0, [$section]);

        if ($this->persist($sections)) {
            $this->selectSection($section['uuid']);
        }
    }

    public function duplicateSection(string $uuid): void
    {
        $this->guard();
        $index = $this->indexOf($uuid);
        if ($index < 0) {
            return;
        }
        $copy = $this->sections[$index];
        $copy['uuid'] = (string) Str::uuid(); // UUID baru → tidak ada konflik identitas

        $sections = $this->sections;
        array_splice($sections, $index + 1, 0, [$copy]);
        if ($this->persist($sections)) {
            $this->selectSection($copy['uuid']);
        }
    }

    public function deleteSection(string $uuid): void
    {
        $this->guard();
        $sections = array_values(array_filter($this->sections, fn ($s) => $s['uuid'] !== $uuid && ($s['parent_uuid'] ?? null) !== $uuid));
        if ($this->persist($sections) && $this->selectedUuid === $uuid) {
            $this->selectSection(null);
        }
    }

    public function toggleVisibility(string $uuid): void
    {
        $this->guard();
        $sections = $this->sections;
        $index = $this->indexOf($uuid);
        if ($index < 0) {
            return;
        }
        $sections[$index]['is_visible'] = ! ($sections[$index]['is_visible'] ?? true);
        if ($this->persist($sections) && $this->selectedUuid === $uuid) {
            $this->resetPropertiesForm();
        }
    }

    public function moveSection(string $uuid, string $direction): void
    {
        $this->guard();
        $index = $this->indexOf($uuid);
        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if ($index < 0 || $target < 0 || $target >= count($this->sections)) {
            return;
        }
        $sections = $this->sections;
        [$sections[$index], $sections[$target]] = [$sections[$target], $sections[$index]];
        $this->persist($sections);
    }

    /** @param list<string> $order urutan UUID baru dari drag-and-drop */
    public function reorder(array $order): void
    {
        $this->guard();
        $current = array_column($this->sections, 'uuid');
        $order = array_values(array_filter($order, 'is_string'));
        // Himpunan UUID harus identik: mencegah duplikasi atau kehilangan section.
        if (count($order) !== count($current) || array_diff($current, $order) !== [] || count(array_unique($order)) !== count($order)) {
            Notification::make()->title('Urutan tidak valid, memuat ulang struktur.')->warning()->send();
            $this->loadFromDatabase();

            return;
        }
        $byUuid = array_column($this->sections, null, 'uuid');
        $this->persist(array_map(fn ($uuid) => $byUuid[$uuid], $order));
    }

    /** Inline editing teks polos dari canvas. Hanya field teks yang memang ada di skema. */
    public function inlineEdit(string $uuid, string $field, string $value): void
    {
        $this->guard();
        if (! in_array($field, ['heading', 'subheading', 'subtitle', 'eyebrow', 'title', 'description'], true)) {
            return;
        }
        $index = $this->indexOf($uuid);
        if ($index < 0) {
            return;
        }
        $type = app(SectionRegistry::class)->get($this->sections[$index]['type']);
        if (! array_key_exists($field, $type->contentRules())) {
            return;
        }
        $sections = $this->sections;
        $sections[$index]['content'][$field] = Str::limit(trim(strip_tags($value)), 1000, '');
        if ($this->persist($sections, reloadCanvas: false) && $this->selectedUuid === $uuid) {
            $this->resetPropertiesForm();
        }
    }

    /** Terapkan perubahan property editor ke section terpilih. */
    public function applyProperties(): void
    {
        $this->guard();
        $index = $this->indexOf($this->selectedUuid);
        if ($index < 0) {
            return;
        }

        $state = $this->propertiesForm->getState(); // validasi field Filament
        $sections = $this->sections;
        $sections[$index]['content'] = self::normalizeLists($state['content'] ?? []);
        $sections[$index]['design'] = array_filter($state['design'] ?? [], fn ($v) => $v !== null && $v !== '');
        $sections[$index]['responsive'] = self::cleanResponsive($state['responsive'] ?? []);
        $sections[$index]['is_visible'] = (bool) ($state['is_visible'] ?? true);

        $this->persist($sections);
    }

    public function setDevice(string $device): void
    {
        $this->device = in_array($device, StyleTokens::DEVICES, true) ? $device : 'desktop';
    }

    public function reloadFromServer(): void
    {
        $this->loadFromDatabase();
        Notification::make()->title('Builder dimuat ulang dengan versi terbaru.')->success()->send();
    }

    /* ----------------------------------------------------------------- */
    /* Header actions                                                     */
    /* ----------------------------------------------------------------- */

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveDraft')->label('Simpan Draft')->icon('heroicon-o-bookmark')->color('gray')
                ->schema([TextInput::make('note')->label('Catatan revisi (opsional)')->maxLength(200)])
                ->action(function (array $data) {
                    $this->guard();
                    try {
                        $page = app(SaveDraft::class)->execute($this->record, $this->sections, $this->lockVersion, auth()->user(), true, $data['note'] ?? null);
                        $this->lockVersion = $page->lock_version;
                        $this->record = $page;
                        Notification::make()->title('Draft disimpan sebagai revisi.')->success()->send();
                    } catch (StaleDraftException $e) {
                        $this->markStale($e);
                    } catch (ValidationException $e) {
                        $this->notifyInvalid($e);
                    }
                }),
            Action::make('preview')->label('Preview')->icon('heroicon-o-eye')->color('gray')
                ->url(fn () => route('pages.preview', $this->record), shouldOpenInNewTab: true),
            Action::make('publish')->label('Publish')->icon('heroicon-o-rocket-launch')->color('success')
                ->visible(fn () => auth()->user()->can('publish', $this->record))
                ->requiresConfirmation()
                ->modalDescription('Draft saat ini akan tampil di website publik. Versi sebelumnya tetap tersimpan di riwayat revisi.')
                ->schema([TextInput::make('note')->label('Catatan (opsional)')->maxLength(200)])
                ->action(function (array $data) {
                    try {
                        $page = app(PublishPage::class)->execute($this->record, $this->lockVersion, auth()->user(), $data['note'] ?? null);
                        $this->record = $page;
                        Notification::make()->title('Halaman dipublikasikan.')->success()
                            ->actions([Action::make('open')->label('Lihat')->url($page->publicUrl(), shouldOpenInNewTab: true)])->send();
                    } catch (StaleDraftException $e) {
                        $this->markStale($e);
                    } catch (ValidationException $e) {
                        $this->notifyInvalid($e);
                    }
                }),
            ActionGroup::make([
                Action::make('unpublish')->label('Unpublish')->icon('heroicon-o-eye-slash')->color('warning')
                    ->visible(fn () => $this->record->isPublished() && ! $this->record->isHome() && auth()->user()->can('publish', $this->record))
                    ->requiresConfirmation()
                    ->action(function () {
                        $this->record = app(UnpublishPage::class)->execute($this->record, auth()->user());
                        Notification::make()->title('Halaman tidak lagi tampil di website.')->success()->send();
                    }),
                Action::make('revisions')->label('Riwayat revisi & restore')->icon('heroicon-o-clock')
                    ->url(fn () => PageResource::getUrl('edit', ['record' => $this->record]).'?relation=0'),
                Action::make('settings')->label('Pengaturan & SEO halaman')->icon('heroicon-o-cog-6-tooth')
                    ->url(fn () => PageResource::getUrl('edit', ['record' => $this->record])),
            ]),
        ];
    }

    /* ----------------------------------------------------------------- */
    /* Internal                                                           */
    /* ----------------------------------------------------------------- */

    private function guard(): void
    {
        abort_unless(auth()->user()->can('build', $this->record), 403);
        if ($this->stale) {
            throw new StaleDraftException;
        }
    }

    /** Simpan seluruh tree sebagai draft. @return bool sukses */
    private function persist(array $sections, bool $reloadCanvas = true): bool
    {
        try {
            $page = app(SaveDraft::class)->execute($this->record, $sections, $this->lockVersion, auth()->user());
        } catch (StaleDraftException $e) {
            $this->markStale($e);

            return false;
        } catch (ValidationException $e) {
            $this->notifyInvalid($e);

            return false;
        }

        $this->record = $page;
        $this->lockVersion = $page->lock_version;
        $this->sections = $page->sections()->get()->map->toBuilderArray()->values()->all();
        if ($reloadCanvas) {
            $this->canvasVersion++;
        }

        return true;
    }

    private function loadFromDatabase(): void
    {
        $this->record->refresh();
        $this->lockVersion = $this->record->lock_version;
        $this->sections = $this->record->sections()->get()->map->toBuilderArray()->values()->all();
        $this->stale = false;
        if (! $this->findSection($this->selectedUuid)) {
            $this->selectedUuid = null;
        }
        $this->resetPropertiesForm();
        $this->canvasVersion++;
    }

    private function resetPropertiesForm(): void
    {
        $this->cacheSchema('propertiesForm', null);
        $section = $this->findSection($this->selectedUuid);
        $this->selectedData = [];
        if ($section) {
            $defaults = app(SectionRegistry::class)->get($section['type'])->defaults();
            $this->propertiesForm->fill([
                'content' => array_replace($defaults['content'], $section['content'] ?? []),
                'design' => array_replace($defaults['design'], $section['design'] ?? []),
                'responsive' => array_replace_recursive($defaults['responsive'], $section['responsive'] ?? []),
                'is_visible' => $section['is_visible'] ?? true,
            ]);
        }
    }

    private function markStale(StaleDraftException $e): void
    {
        $this->stale = true;
        Notification::make()->title('Halaman diubah pengguna lain')->body($e->getMessage())->danger()->persistent()->send();
    }

    private function notifyInvalid(ValidationException $e): void
    {
        Notification::make()->title('Perubahan tidak disimpan')->body(collect($e->errors())->flatten()->first())->danger()->send();
    }

    private function findSection(?string $uuid): ?array
    {
        foreach ($this->sections as $section) {
            if ($section['uuid'] === $uuid) {
                return $section;
            }
        }

        return null;
    }

    private function indexOf(?string $uuid): int
    {
        foreach ($this->sections as $i => $section) {
            if ($section['uuid'] === $uuid) {
                return $i;
            }
        }

        return -1;
    }

    /** Ubah array ber-key UUID (state Repeater) menjadi list berurutan secara rekursif. */
    public static function normalizeLists(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::normalizeLists($value);
            }
        }
        $keys = array_keys($data);
        if ($keys !== [] && collect($keys)->every(fn ($k) => is_string($k) && Str::isUuid($k))) {
            return array_values($data);
        }

        return $data;
    }

    private static function cleanResponsive(array $responsive): array
    {
        $clean = [];
        foreach (StyleTokens::DEVICES as $device) {
            $values = array_filter($responsive[$device] ?? [], fn ($v) => $v !== null && $v !== '');
            if (isset($values['columns'])) {
                $values['columns'] = (int) $values['columns'];
            }
            $values['visible'] = (bool) ($values['visible'] ?? true);
            $clean[$device] = $values;
        }

        return $clean;
    }
}
