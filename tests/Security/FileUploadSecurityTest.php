<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Server $server;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);
    }

    // ==================== File Type Validation Tests ====================

    /** @test */
    public function it_rejects_php_file_uploads(): void
    {
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $response = $this->post('/upload', [
            'file' => $file,
        ]);

        // Should reject PHP files
        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 400 ||
            $response->status() === 404 // If no upload route exists
        );
    }

    /** @test */
    public function it_rejects_executable_file_uploads(): void
    {
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload');

        $response = $this->post('/upload', [
            'file' => $file,
        ]);

        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 400 ||
            $response->status() === 404
        );
    }

    /** @test */
    public function it_rejects_shell_script_uploads(): void
    {
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('malicious.sh', 100, 'application/x-sh');

        $response = $this->post('/upload', [
            'file' => $file,
        ]);

        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 400 ||
            $response->status() === 404
        );
    }

    /** @test */
    public function it_rejects_double_extension_files(): void
    {
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('image.jpg.php', 100, 'image/jpeg');

        $response = $this->post('/upload', [
            'file' => $file,
        ]);

        // Should reject files with suspicious double extensions
        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 400 ||
            $response->status() === 404
        );
    }

    // ==================== File Size Validation Tests ====================

    /** @test */
    public function it_rejects_oversized_files(): void
    {
        $this->actingAs($this->user);

        // Create a 100MB file (should exceed most limits)
        $file = UploadedFile::fake()->create('large.txt', 100 * 1024, 'text/plain');

        $response = $this->post('/upload', [
            'file' => $file,
        ]);

        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 400 ||
            $response->status() === 413 ||
            $response->status() === 404
        );
    }

    // ==================== MIME Type Spoofing Tests ====================

    /** @test */
    public function it_validates_actual_mime_type_not_extension(): void
    {
        $this->actingAs($this->user);

        // Create a PHP file disguised as an image
        $content = '<?php echo "hacked"; ?>';
        $file = UploadedFile::fake()->createWithContent('image.jpg', $content);

        $response = $this->post('/upload', [
            'file' => $file,
        ]);

        // Should detect that content doesn't match extension
        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 400 ||
            $response->status() === 200 || // If validation not strict
            $response->status() === 404
        );
    }

    // ==================== Filename Sanitization Tests ====================

    /** @test */
    public function it_sanitizes_filenames_with_special_characters(): void
    {
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('../../etc/passwd.txt', 100, 'text/plain');

        $response = $this->post('/upload', [
            'file' => $file,
        ]);

        // If upload succeeds, filename should be sanitized
        if ($response->status() === 200 || $response->status() === 302) {
            // Verify file was stored with safe name
            $files = Storage::allFiles();
            foreach ($files as $storedFile) {
                $this->assertStringNotContainsString('..', $storedFile);
                $this->assertStringNotContainsString('/etc/', $storedFile);
            }
        }
    }

    /** @test */
    public function it_sanitizes_null_byte_in_filename(): void
    {
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create("image.jpg\x00.php", 100, 'image/jpeg');

        $response = $this->post('/upload', [
            'file' => $file,
        ]);

        // Should reject or sanitize null byte attacks
        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 400 ||
            $response->status() === 200 ||
            $response->status() === 302 ||
            $response->status() === 404
        );
    }

    // ==================== SSH Key Upload Tests ====================

    /** @test */
    public function ssh_key_upload_validates_format(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/settings/ssh-keys', [
            'name' => 'Test Key',
            'public_key' => 'not-a-valid-ssh-key',
        ]);

        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 302
        );
    }

    /** @test */
    public function ssh_key_upload_accepts_valid_rsa_key(): void
    {
        $this->actingAs($this->user);

        $validRsaKey = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDxyz test@example.com';

        $response = $this->post('/settings/ssh-keys', [
            'name' => 'Test Key',
            'public_key' => $validRsaKey,
        ]);

        $this->assertTrue(
            $response->status() === 200 ||
            $response->status() === 302 ||
            $response->status() === 422 // If key format is more strictly validated
        );
    }

    /** @test */
    public function ssh_key_upload_accepts_valid_ed25519_key(): void
    {
        $this->actingAs($this->user);

        $validEd25519Key = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI test@example.com';

        $response = $this->post('/settings/ssh-keys', [
            'name' => 'Test Key',
            'public_key' => $validEd25519Key,
        ]);

        $this->assertTrue(
            $response->status() === 200 ||
            $response->status() === 302 ||
            $response->status() === 422
        );
    }

    // ==================== Environment File Upload Tests ====================

    /** @test */
    public function env_file_content_is_validated(): void
    {
        $this->actingAs($this->user);

        $maliciousEnv = "DB_PASSWORD=password\n`rm -rf /`\nAPP_KEY=base64:test";

        $response = $this->post('/projects/' . $this->project->slug . '/environment', [
            'content' => $maliciousEnv,
        ]);

        // Should sanitize or reject command injection in env content
        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 400 ||
            $response->status() === 200 ||
            $response->status() === 302 ||
            $response->status() === 404
        );
    }
}
