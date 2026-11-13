<?php

namespace Tests\Feature\Security;

use App\Filament\Pages\WebsiteSettings;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Lead;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\User;
use App\Support\CmsCache;
use App\Support\MediaUploader;
use App\Support\Roles;
use App\Support\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsPages;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use BuildsPages, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_csrf_token_is_required_for_public_forms(): void
    {
        // Laravel menonaktifkan CSRF saat environment "testing"; aktifkan perilaku produksi.
        $this->app['env'] = 'production';

        $this->post('/leads', ['company_name' => 'X'])->assertStatus(419);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_stored_xss_in_builder_content_is_neutralized_on_public_page(): void
    {
        $this->pageWithSections([
            $this->section('heading', ['heading' => '<script>alert("h")</script>Judul']),
            $this->section('rich_text', ['body' => '<p>Aman</p><img src=x onerror=alert(1)><a href="javascript:alert(2)">klik</a>']),
            $this->section('hero', ['heading' => 'Hero', 'primary_button' => ['label' => 'Tombol', 'url' => '/kontak']]),
        ], ['slug' => 'xss'], true);

        $html = $this->get('/xss')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert("h")</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('onerror=alert', $html);
        $this->assertStringNotContainsString('javascript:alert(2)', $html);
    }

    public function test_tampered_snapshot_is_sanitized_again_at_render_time(): void
    {
        $page = $this->pageWithSections([$this->section('rich_text', ['body' => '<p>ok</p>'])], ['slug' => 'tamper'], true);
        // Simulasikan snapshot dimanipulasi langsung di database.
        $snapshot = $page->publishedRevision->snapshot;
        $snapshot['sections'][0]['content']['body'] = '<p>ok</p><script>alert(1)</script>';
        \DB::table('page_revisions')->where('id', $page->published_revision_id)->update(['snapshot' => json_encode($snapshot)]);
        CmsCache::bump();

        $this->get('/tamper')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_upload_rejects_disguised_and_dangerous_files(): void
    {
        Storage::fake('public');
        $uploader = app(MediaUploader::class);
        $dir = sys_get_temp_dir();

        file_put_contents($php = "{$dir}/shell.jpg", '<?php system($_GET["c"]); ?>');
        file_put_contents($svg = "{$dir}/logo.svg", '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        file_put_contents($html = "{$dir}/page.png", '<html><script>alert(1)</script></html>');

        foreach ([$php, $svg, $html] as $file) {
            try {
                $uploader->store($file, basename($file));
                $this->fail('Upload berbahaya seharusnya ditolak: '.basename($file));
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame(0, MediaAsset::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_valid_image_upload_is_reencoded_with_random_name_and_variants(): void
    {
        Storage::fake('public');
        $img = imagecreatetruecolor(1200, 600);
        imagejpeg($img, $path = sys_get_temp_dir().'/foto asli.jpg');

        $asset = app(MediaUploader::class)->store($path, '../../foto asli.jpg');

        $this->assertSame('image', $asset->kind);
        $this->assertSame('image/jpeg', $asset->mime_type);
        $this->assertSame('foto asli.jpg', $asset->original_name);
        $this->assertMatchesRegularExpression('#^media/\d{4}/\d{2}/[0-9a-f\-]{36}\.jpg$#', $asset->path);
        Storage::disk('public')->assertExists($asset->path);
        $this->assertSame([480, 960, 1200], array_column($asset->variants, 'width'));
    }

    public function test_upload_size_limit(): void
    {
        config(['cms.media.max_image_kb' => 1]);
        $img = imagecreatetruecolor(800, 800);
        for ($i = 0; $i < 2000; $i++) {
            imagesetpixel($img, random_int(0, 799), random_int(0, 799), random_int(0, 0xFFFFFF));
        }
        imagepng($img, $path = sys_get_temp_dir().'/besar.png');

        $this->expectException(ValidationException::class);
        app(MediaUploader::class)->store($path, 'besar.png');
    }

    public function test_media_in_use_cannot_be_deleted_by_policy(): void
    {
        $admin = $this->actingAsRole(Roles::ADMINISTRATOR);
        $media = MediaAsset::factory()->create();
        $this->pageWithSections([$this->section('image', ['image_id' => $media->id])]);

        $this->assertFalse($admin->can('delete', $media->fresh()));
        $this->assertTrue($admin->can('delete', MediaAsset::factory()->create()));
    }

    public function test_mass_assignment_protection_on_sensitive_columns(): void
    {
        $user = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'secret-password-1', 'is_active' => false]);
        $this->assertTrue($user->fresh()->is_active); // default DB, bukan nilai dari input

        $page = Page::create(['title' => 'P', 'slug' => 'p', 'status' => 'published', 'published_revision_id' => 1, 'lock_version' => 99]);
        $this->assertSame('draft', $page->fresh()->status->value);
        $this->assertNull($page->fresh()->published_revision_id);
        $this->assertSame(0, $page->fresh()->lock_version);

        $lead = Lead::create(['company_name' => 'A', 'contact_name' => 'B', 'phone' => '0812', 'status' => 'won', 'assigned_to' => $user->id]);
        $this->assertSame('new', $lead->fresh()->status->value);
        $this->assertNull($lead->fresh()->assigned_to);
    }

    public function test_administrator_cannot_escalate_to_super_admin(): void
    {
        $this->actingAsRole(Roles::ADMINISTRATOR);
        $this->get('/admin/users')->assertForbidden(); // tidak punya users.manage

        // Role kustom dengan users.manage tetap tidak bisa memberi super_admin.
        $role = Role::create(['name' => 'hr', 'guard_name' => 'web']);
        $role->givePermissionTo('users.manage');
        $hr = User::factory()->create();
        $hr->assignRole($role);
        $this->actingAs($hr);
        $target = $this->userWithRole(Roles::SALES);

        try {
            Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
                ->fillForm(['role_names' => [Roles::SUPER_ADMIN]])
                ->call('save');
        } catch (\Throwable) {
        }

        $this->assertFalse($target->fresh()->isSuperAdmin());

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Baru', 'email' => 'baru@example.com', 'password' => 'PasswordKuat123', 'is_active' => true, 'role_names' => [Roles::SUPER_ADMIN]])
            ->call('create');
        $this->assertFalse((bool) User::where('email', 'baru@example.com')->first()?->isSuperAdmin());
    }

    public function test_cannot_edit_or_delete_super_admin_without_being_super_admin(): void
    {
        $super = $this->userWithRole(Roles::SUPER_ADMIN);
        $role = Role::create(['name' => 'hr', 'guard_name' => 'web']);
        $role->givePermissionTo('users.manage');
        $hr = User::factory()->create();
        $hr->assignRole($role);

        $this->assertFalse($hr->can('update', $super));
        $this->assertFalse($hr->can('delete', $super));
        $this->assertFalse($hr->can('delete', $hr));
    }

    public function test_last_super_admin_cannot_be_demoted_or_deleted(): void
    {
        $super = $this->actingAsRole(Roles::SUPER_ADMIN);
        $this->assertFalse($super->can('delete', $super));

        Livewire::test(EditUser::class, ['record' => $super->getRouteKey()])
            ->fillForm(['role_names' => [Roles::SALES]])
            ->call('save')
            ->assertHasErrors();

        $this->assertTrue($super->fresh()->isSuperAdmin());
    }

    public function test_custom_scripts_require_special_permission(): void
    {
        $role = Role::create(['name' => 'web_manager', 'guard_name' => 'web']);
        $role->givePermissionTo('settings.manage');
        $manager = User::factory()->create();
        $manager->assignRole($role);
        $this->actingAs($manager);

        Livewire::test(WebsiteSettings::class)
            ->set('data.scripts.head', '<script>alert(1)</script>')
            ->set('data.general.site_name', 'Nama Baru')
            ->call('save');

        $settings = app(SettingsRepository::class);
        $settings->flush();
        $this->assertSame('Nama Baru', $settings->get('general.site_name'));
        $this->assertSame('', $settings->get('scripts.head'));
    }

    public function test_preview_cannot_load_revision_of_another_page(): void
    {
        $this->actingAsRole(Roles::ADMINISTRATOR);
        $a = $this->pageWithSections([$this->section('heading', ['heading' => 'Halaman A'])], [], true);
        $b = $this->pageWithSections([$this->section('heading', ['heading' => 'Rahasia B'])], [], true);

        $this->get(route('pages.preview', ['page' => $a, 'revision' => $b->published_revision_id]))->assertNotFound();
    }

    public function test_lead_form_bot_tokens_cannot_be_forged_without_app_key(): void
    {
        $forged = base64_encode(json_encode(['iv' => 'x', 'value' => (string) (time() - 60), 'mac' => 'y']));
        $this->post('/leads', [
            'company_name' => 'Bot', 'contact_name' => 'Bot', 'phone' => '081234567890', 'type' => 'contact', 'consent' => '1', '_ts' => $forged,
        ])->assertSessionHas('lead_success');
        $this->assertDatabaseCount('leads', 0);

        $this->post('/leads', [
            'company_name' => 'Asli', 'contact_name' => 'Manusia', 'phone' => '081234567890', 'type' => 'contact', 'consent' => '1',
            '_ts' => Crypt::encryptString((string) (time() - 30)),
        ]);
        $this->assertDatabaseCount('leads', 1);
    }
}
