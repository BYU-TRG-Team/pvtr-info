<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAuthAndImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_imports(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login');
    }

    public function test_admin_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.imports.index'));

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')
            ->assertRedirect(route('verification.index'));

        $this->assertGuest();
    }

    public function test_authenticated_user_can_upload_license_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.imports.store'), [
                'license_file' => $this->uploadedTxtFile(),
            ])
            ->assertRedirect(route('admin.imports.index'));

        $this->assertDatabaseHas('import_batches', [
            'original_filename' => 'licenses.txt',
            'imported_rows' => 1,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('license_records', [
            'license_number' => '100-001',
            'license_prefix' => '01126',
            'entity_name' => 'Example Person',
            'is_current' => true,
        ]);
    }

    public function test_upload_requires_supported_file_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('admin.imports.index'))
            ->post(route('admin.imports.store'), [
                'license_file' => UploadedFile::fake()->create('licenses.pdf', 1, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.imports.index'))
            ->assertSessionHasErrors('license_file');
    }

    public function test_upload_with_unrecognized_headers_returns_an_error_without_writing_to_the_database(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('admin.imports.index'))
            ->post(route('admin.imports.store'), [
                'license_file' => $this->uploadedTxtFile([
                    'License Number',
                    'License prefix',
                    'Entity name',
                    'Entity type',
                    'License status',
                    'Email',
                    'Expiration date',
                ]),
            ]);

        $response
            ->assertRedirect(route('admin.imports.index'))
            ->assertSessionHasErrors([
                'license_file' => 'The uploaded file is missing the expected license headers.',
            ]);

        $this->assertDatabaseCount('import_batches', 0);
        $this->assertDatabaseCount('license_records', 0);
        Storage::disk('local')->assertDirectoryEmpty('imports');
    }

    /**
     * @param  array<int, string>|null  $header
     */
    private function uploadedTxtFile(?array $header = null): UploadedFile
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('license_upload_', true).'.txt';
        $contents = implode("\n", [
            implode("\t", $header ?? ['License #', 'License prefix', 'Entity name', 'Entity type', 'License status', 'Email', 'Expiration date']),
            implode("\t", ['100-001', '01126', 'Example Person', 'Individual', 'Active', 'person@example.com', '5/31/2029']),
        ]);

        file_put_contents($path, $contents);

        return new UploadedFile($path, 'licenses.txt', 'text/plain', null, true);
    }
}
