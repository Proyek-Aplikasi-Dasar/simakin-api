<?php

namespace Tests\Feature\Template;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExcelTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['nama' => 'admin']);
        Role::firstOrCreate(['id' => 2], ['nama' => 'guru']);
        Role::firstOrCreate(['id' => 3], ['nama' => 'siswa']);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role_id' => 1, 'username' => 'admin_test']);
    }

    protected function guruUser(): User
    {
        return User::factory()->create(['role_id' => 2, 'username' => 'guru_test']);
    }

    protected function siswaUser(): User
    {
        return User::factory()->create(['role_id' => 3, 'username' => 'siswa_test']);
    }

    public function test_guest_cannot_download_template(): void
    {
        $this->getJson('/api/templates/siswa')->assertStatus(401);
    }

    public function test_non_admin_cannot_download_template(): void
    {
        $this->actingAs($this->guruUser(), 'sanctum')->getJson('/api/templates/siswa')->assertStatus(403);
        $this->actingAs($this->siswaUser(), 'sanctum')->getJson('/api/templates/siswa')->assertStatus(403);
    }

    public function test_admin_can_download_siswa_template(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')->get('/api/templates/siswa');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
    }

    public function test_admin_can_download_guru_template(): void
    {
        $this->actingAs($this->admin(), 'sanctum')->get('/api/templates/guru')->assertStatus(200);
    }

    public function test_admin_can_download_perusahaan_template(): void
    {
        $this->actingAs($this->admin(), 'sanctum')->get('/api/templates/perusahaan')->assertStatus(200);
    }

    public function test_admin_can_download_kompetensi_template(): void
    {
        $this->actingAs($this->admin(), 'sanctum')->get('/api/templates/kompetensi')->assertStatus(200);
    }

    public function test_invalid_template_type_returns_404(): void
    {
        $this->actingAs($this->admin(), 'sanctum')->getJson('/api/templates/unknown')->assertStatus(404);
    }
}
