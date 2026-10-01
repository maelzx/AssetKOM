<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_upload_an_attachment_to_an_asset(): void
    {
        Storage::fake('local');

        $staff = User::factory()->staff()->create();
        $asset = Asset::factory()->create();

        $this->actingAs($staff);

        Volt::test('attachments.panel', ['attachable' => $asset])
            ->set('file', UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'))
            ->call('upload')
            ->assertHasNoErrors();

        $attachment = Attachment::firstOrFail();

        $this->assertSame($asset->id, $attachment->attachable_id);
        $this->assertSame($asset->getMorphClass(), $attachment->attachable_type);
        $this->assertSame('invoice.pdf', $attachment->original_name);
        $this->assertSame($staff->id, $attachment->uploaded_by);
        Storage::disk('local')->assertExists($attachment->file_path);
    }

    public function test_attachments_can_be_added_to_maintenance_records(): void
    {
        Storage::fake('local');

        $asset = Asset::factory()->create();
        $record = Maintenance::factory()->create(['asset_id' => $asset->id]);

        $this->actingAs(User::factory()->staff()->create());

        Volt::test('attachments.panel', ['attachable' => $record])
            ->set('file', UploadedFile::fake()->create('report.pdf', 50, 'application/pdf'))
            ->call('upload')
            ->assertHasNoErrors();

        $this->assertSame(Maintenance::class, Attachment::firstOrFail()->attachable_type);
    }

    public function test_disallowed_file_types_are_rejected(): void
    {
        Storage::fake('local');

        $asset = Asset::factory()->create();

        $this->actingAs(User::factory()->staff()->create());

        Volt::test('attachments.panel', ['attachable' => $asset])
            ->set('file', UploadedFile::fake()->create('malware.exe', 10))
            ->call('upload')
            ->assertHasErrors(['file']);
    }

    public function test_files_over_the_size_limit_are_rejected(): void
    {
        Storage::fake('local');

        $asset = Asset::factory()->create();

        $this->actingAs(User::factory()->staff()->create());

        Volt::test('attachments.panel', ['attachable' => $asset])
            ->set('file', UploadedFile::fake()->create('huge.pdf', 11000, 'application/pdf'))
            ->call('upload')
            ->assertHasErrors(['file']);
    }

    public function test_authenticated_users_can_download_attachments(): void
    {
        Storage::fake('local');

        $asset = Asset::factory()->create();
        $attachment = Attachment::factory()->create([
            'attachable_type' => $asset->getMorphClass(),
            'attachable_id' => $asset->id,
            'original_name' => 'warranty.pdf',
        ]);
        Storage::disk('local')->put($attachment->file_path, 'file contents');

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('attachments.download', $attachment))
            ->assertOk()
            ->assertDownload('warranty.pdf');
    }

    public function test_guests_cannot_download_attachments(): void
    {
        $attachment = Attachment::factory()->create();

        $this->get(route('attachments.download', $attachment))
            ->assertRedirect(route('login'));
    }

    public function test_uploader_and_managers_can_delete_but_other_staff_cannot(): void
    {
        $asset = Asset::factory()->create();
        $uploader = User::factory()->staff()->create();
        $other = User::factory()->staff()->create();
        $manager = User::factory()->manager()->create();

        $attachment = Attachment::factory()->create([
            'attachable_type' => $asset->getMorphClass(),
            'attachable_id' => $asset->id,
            'uploaded_by' => $uploader->id,
        ]);

        $this->assertTrue($uploader->can('delete', $attachment));
        $this->assertTrue($manager->can('delete', $attachment));
        $this->assertFalse($other->can('delete', $attachment));
    }

    public function test_uploader_can_delete_via_the_panel_and_file_is_removed(): void
    {
        Storage::fake('local');

        $asset = Asset::factory()->create();
        $uploader = User::factory()->staff()->create();
        $attachment = Attachment::factory()->create([
            'attachable_type' => $asset->getMorphClass(),
            'attachable_id' => $asset->id,
            'uploaded_by' => $uploader->id,
        ]);
        Storage::disk('local')->put($attachment->file_path, 'contents');

        $this->actingAs($uploader);

        Volt::test('attachments.panel', ['attachable' => $asset])
            ->call('delete', $attachment->id);

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($attachment->file_path);
    }
}
