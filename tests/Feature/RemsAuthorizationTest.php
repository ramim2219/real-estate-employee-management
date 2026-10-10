<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RemsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_untrusted_user_cannot_create_departments(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user=User::factory()->create();
        $user->assignRole('employee');
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/departments',['name'=>'Finance','code'=>'FIN'])->assertForbidden();
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }
}
