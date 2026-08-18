<?php

namespace Tests\Feature\Storage;

use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\InventoryCategory;
use App\Models\User;
use App\Services\FileStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'permission' => 'write']);
    }

    public function test_put_and_delete_on_configurable_category_disk(): void
    {
        Storage::fake('inventory');
        $storage = new FileStorage();

        $path = $storage->put('inventory', UploadedFile::fake()->image('apar.jpg'));

        Storage::disk('inventory')->assertExists($path);   // Q-022: stored on the configurable disk

        $storage->delete('inventory', $path);
        Storage::disk('inventory')->assertMissing($path);
    }

    public function test_unknown_category_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new FileStorage())->put('evil', UploadedFile::fake()->image('x.jpg'));
    }

    public function test_centralized_upload_validation_rejects_non_image(): void
    {
        Storage::fake('inventory');
        $category = InventoryCategory::create(['name' => 'FS', 'code' => 'FS']);
        $itemType = AssetItemType::create(['inventory_category_id' => $category->id, 'name' => 'APAR', 'code' => 'APAR', 'checklist_frequency' => 'monthly']);

        // Q-026: non-image upload is rejected by the centralized validation.
        $this->actingAs($this->admin())->post(route('compliance.inventory.store'), [
            'inventory_category_id' => $category->id,
            'asset_item_type_id' => $itemType->id,
            'status' => 'good',
            'qty' => 1,
            'photo' => UploadedFile::fake()->create('malware.php', 100, 'application/x-php'),
        ])->assertSessionHasErrors('photo');
    }

    public function test_file_is_served_only_to_authenticated_users(): void
    {
        Storage::fake('inventory');
        $path = (new FileStorage())->put('inventory', UploadedFile::fake()->image('a.jpg'));

        // guest → redirected to login (not served)
        $this->get(route('files.show', ['category' => 'inventory', 'path' => $path]))->assertRedirect(route('login'));

        // authed → served
        $this->actingAs($this->admin())->get(route('files.show', ['category' => 'inventory', 'path' => $path]))->assertOk();
    }

    public function test_file_serving_guards_unknown_category_and_traversal(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->get(route('files.show', ['category' => 'evil', 'path' => 'x.jpg']))->assertNotFound();
        $this->actingAs($user)->get(route('files.show', ['category' => 'inventory', 'path' => '../secret.jpg']))->assertNotFound();
        $this->actingAs($user)->get(route('files.show', ['category' => 'inventory', 'path' => 'missing.jpg']))->assertNotFound();
    }
}
