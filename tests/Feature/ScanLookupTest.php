<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ScanLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/scan')->assertRedirect(route('login'));
    }

    public function test_finds_an_asset_by_tag(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'AST-0042']);

        $this->actingAs(User::factory()->staff()->create());

        Volt::test('scan.index')
            ->set('code', 'AST-0042')
            ->call('find')
            ->assertRedirect(route('assets.show', $asset));
    }

    public function test_finds_an_asset_by_serial_number_case_insensitively(): void
    {
        $asset = Asset::factory()->create(['serial_number' => 'SN-ABC-123']);

        $this->actingAs(User::factory()->staff()->create());

        Volt::test('scan.index')
            ->set('code', 'sn-abc-123')
            ->call('find')
            ->assertRedirect(route('assets.show', $asset));
    }

    public function test_unknown_code_shows_an_error(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        Volt::test('scan.index')
            ->set('code', 'NOPE-999')
            ->call('find')
            ->assertHasErrors(['code']);
    }

    public function test_code_is_required(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        Volt::test('scan.index')
            ->set('code', '')
            ->call('find')
            ->assertHasErrors(['code' => 'required']);
    }
}
