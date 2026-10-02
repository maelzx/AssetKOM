<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_categories(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get('/categories')
            ->assertOk()
            ->assertSeeLivewire('categories.index');
    }

    public function test_staff_cannot_view_categories(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/categories')->assertForbidden();
    }

    public function test_manager_can_create_category_with_generated_slug(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager);

        Volt::test('categories.index')
            ->set('name', 'Office Equipment')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Office Equipment',
            'slug' => 'office-equipment',
        ]);
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();

        $this->actingAs($manager);

        Volt::test('categories.index')
            ->call('edit', $category->id)
            ->set('parent_id', $category->id)
            ->call('save')
            ->assertHasErrors(['parent_id']);
    }

    public function test_category_cannot_be_nested_under_its_own_descendant(): void
    {
        $manager = User::factory()->manager()->create();
        $parent = Category::factory()->create();
        $child = Category::factory()->childOf($parent)->create();

        $this->actingAs($manager);

        Volt::test('categories.index')
            ->call('edit', $parent->id)
            ->set('parent_id', $child->id)
            ->call('save')
            ->assertHasErrors(['parent_id']);
    }

    public function test_manager_can_delete_category(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();

        $this->actingAs($manager);

        Volt::test('categories.index')->call('delete', $category->id);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_category_with_assets_cannot_be_deleted(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();
        Asset::factory()->create(['category_id' => $category->id]);

        $this->actingAs($manager);

        Volt::test('categories.index')
            ->call('delete', $category->id)
            ->assertHasErrors(['delete']);

        $this->assertNotSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_category_with_children_cannot_be_deleted(): void
    {
        $manager = User::factory()->manager()->create();
        $parent = Category::factory()->create();
        Category::factory()->childOf($parent)->create();

        $this->actingAs($manager);

        Volt::test('categories.index')
            ->call('delete', $parent->id)
            ->assertHasErrors(['delete']);

        $this->assertNotSoftDeleted('categories', ['id' => $parent->id]);
    }
}
