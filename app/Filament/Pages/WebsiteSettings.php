<?php

namespace App\Filament\Pages;

use App\Builder\Sections\MapSection;
use App\Builder\Style\StyleTokens;
use App\Filament\Support\FormFields;
use App\Models\ActivityLog;
use App\Models\GlobalSetting;
use App\Support\MediaUsageTracker;
use App\Support\SettingsRepository;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use UnitEnum;

/** Pengaturan website (branding, kontak, header/footer, SEO, analytics, script kustom). */
class WebsiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Website Settings';

    protected static ?string $title = 'Website Settings';

    protected string $view = 'filament.pages.website-settings';

    /** Grup yang boleh disimpan dari halaman ini (allowlist). */
    private const GROUPS = ['general', 'branding', 'contact', 'social', 'seo', 'analytics', 'announcement', 'header', 'footer', 'scripts'];

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(app(SettingsRepository::class)->all());
    }

    public function form(Schema $schema): Schema
    {
        $hex = ['required', 'regex:'.StyleTokens::HEX_PATTERN];
        $fonts = array_combine(array_keys(config('cms.fonts')), array_keys(config('cms.fonts')));

        return $schema->statePath('data')->components([
            Tabs::make('settings')->persistTabInQueryString()->tabs([
                Tab::make('Umum')->icon('heroicon-o-building-office')->columns(2)->schema([
                    TextInput::make('general.site_name')->label('Nama website / perusahaan')->required()->maxLength(120),
                    TextInput::make('general.tagline')->label('Tagline')->maxLength(200),
                    FormFields::media('general.logo_id', 'Logo'),
                    FormFields::media('general.favicon_id', 'Favicon'),
                ]),
                Tab::make('Branding')->icon('heroicon-o-swatch')->columns(3)->schema([
                    ColorPicker::make('branding.primary')->label('Primary')->rules($hex),
                    ColorPicker::make('branding.secondary')->label('Secondary')->rules($hex),
                    ColorPicker::make('branding.accent')->label('Accent')->rules($hex),
                    ColorPicker::make('branding.background')->label('Background')->rules($hex),
                    ColorPicker::make('branding.surface')->label('Surface')->rules($hex),
                    ColorPicker::make('branding.text')->label('Teks')->rules($hex),
                    Select::make('branding.font_heading')->label('Font judul')->options($fonts)->required()->in(array_keys($fonts)),
                    Select::make('branding.font_body')->label('Font teks')->options($fonts)->required()->in(array_keys($fonts)),
                ]),
                Tab::make('Kontak')->icon('heroicon-o-phone')->columns(2)->schema([
                    TextInput::make('contact.phone')->label('Telepon')->maxLength(40),
                    TextInput::make('contact.email')->label('Email')->email()->maxLength(180),
                    TextInput::make('contact.whatsapp')->label('Nomor WhatsApp')->maxLength(20)->regex('/^\+?[0-9\s\-]{9,20}$/')
                        ->helperText('Format: 08xxxxxxxxxx atau 628xxxxxxxxxx. Kosongkan untuk menyembunyikan tombol WhatsApp.'),
                    TextInput::make('contact.whatsapp_default_message')->label('Pesan WhatsApp default')->maxLength(300),
                    Textarea::make('contact.address')->label('Alamat')->maxLength(500)->rows(2),
                    TextInput::make('contact.business_hours')->label('Jam operasional')->maxLength(120),
                    TextInput::make('contact.maps_embed_url')->label('URL embed Google Maps')->maxLength(2048)->regex(MapSection::EMBED_PATTERN)
                        ->helperText('Google Maps → Bagikan → Sematkan peta → salin nilai src (https://www.google.com/maps/embed?pb=...).')->columnSpanFull(),
                ]),
                Tab::make('Header & Pengumuman')->icon('heroicon-o-megaphone')->columns(2)->schema([
                    TextInput::make('header.cta_label')->label('Label tombol header')->maxLength(40),
                    FormFields::url('header.cta_url', 'URL tombol header'),
                    Toggle::make('header.sticky')->label('Header menempel saat scroll'),
                    Toggle::make('announcement.enabled')->label('Tampilkan announcement bar')->columnSpanFull(),
                    TextInput::make('announcement.text')->label('Teks pengumuman')->maxLength(200),
                    Select::make('announcement.style')->label('Warna')->options(['accent' => 'Accent', 'primary' => 'Primary', 'secondary' => 'Secondary', 'dark' => 'Gelap']),
                    TextInput::make('announcement.link_label')->label('Label tautan')->maxLength(40),
                    FormFields::url('announcement.link_url', 'URL tautan'),
                ]),
                Tab::make('Footer')->icon('heroicon-o-bars-3-bottom-left')->schema([
                    Textarea::make('footer.about')->label('Tentang singkat')->maxLength(500)->rows(3),
                    TextInput::make('footer.copyright')->label('Teks copyright')->maxLength(200)->helperText('Gunakan {year} untuk tahun berjalan.'),
                ]),
                Tab::make('Sosial Media')->icon('heroicon-o-share')->columns(2)->schema(
                    collect(['facebook', 'instagram', 'linkedin', 'youtube', 'tiktok', 'x'])
                        ->map(fn ($n) => TextInput::make("social.{$n}")->label(ucfirst($n))->url()->maxLength(255)->rule('starts_with:https://'))->all()
                ),
                Tab::make('SEO')->icon('heroicon-o-magnifying-glass')->columns(2)->schema([
                    TextInput::make('seo.title_suffix')->label('Akhiran judul halaman')->maxLength(80),
                    Select::make('seo.organization_type')->label('Tipe organisasi (structured data)')
                        ->options(['Organization' => 'Organization', 'LocalBusiness' => 'LocalBusiness', 'Corporation' => 'Corporation']),
                    Textarea::make('seo.default_description')->label('Meta description default')->maxLength(320)->rows(2)->columnSpanFull(),
                    FormFields::media('seo.default_og_image_id', 'Gambar Open Graph default'),
                ]),
                Tab::make('Analytics')->icon('heroicon-o-chart-bar')->columns(2)->schema([
                    TextInput::make('analytics.ga4_id')->label('Google Analytics 4 Measurement ID')->regex('/^G-[A-Z0-9]{4,12}$/')->placeholder('G-XXXXXXXXXX'),
                    TextInput::make('analytics.gtm_id')->label('Google Tag Manager ID')->regex('/^GTM-[A-Z0-9]{4,10}$/')->placeholder('GTM-XXXXXXX'),
                ]),
                Tab::make('Custom Scripts')->icon('heroicon-o-code-bracket')
                    ->visible(fn () => auth()->user()->can('settings.custom_scripts'))
                    ->schema([
                        Callout::make('Risiko keamanan')->warning()
                            ->description('Script di sini dijalankan di setiap halaman publik tanpa sanitasi. Hanya tempel kode dari sumber tepercaya. Setiap perubahan tercatat di Activity Log.'),
                        Textarea::make('scripts.head')->label('Sebelum </head>')->rows(6)->maxLength(20000),
                        Textarea::make('scripts.body_end')->label('Sebelum </body>')->rows(6)->maxLength(20000),
                    ]),
            ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $state = $this->form->getState();
        $settings = app(SettingsRepository::class);

        foreach (self::GROUPS as $group) {
            if ($group === 'scripts' && ! auth()->user()->can('settings.custom_scripts')) {
                continue; // tanpa izin khusus, script tidak pernah ditulis walau payload dimanipulasi
            }
            if (isset($state[$group]) && is_array($state[$group])) {
                $settings->setGroup($group, $state[$group]);
            }
        }

        MediaUsageTracker::sync(GlobalSetting::query()->firstOrCreate(['group' => 'general', 'key' => 'site_name']), [
            'logo' => $state['general']['logo_id'] ?? null,
            'favicon' => $state['general']['favicon_id'] ?? null,
            'og' => $state['seo']['default_og_image_id'] ?? null,
        ], 'settings');

        ActivityLog::record('settings.updated', null, 'Website settings diperbarui', ['groups' => array_keys($state)]);
        Notification::make()->title('Pengaturan disimpan.')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Simpan')->submit('save')];
    }
}
