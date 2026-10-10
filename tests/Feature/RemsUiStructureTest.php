<?php

namespace Tests\Feature;

use Tests\TestCase;

class RemsUiStructureTest extends TestCase
{
    public function test_web_and_api_routes_are_registered(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('login'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('rems.dashboard'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('rems.resources.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('rems.workflow'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('rems.documents.download'));
    }

    public function test_every_configured_resource_has_valid_model_controller_and_fields(): void
    {
        $resources = config('rems_ui.resources', []);
        $this->assertCount(15, $resources);
        foreach ($resources as $slug => $definition) {
            $this->assertTrue(class_exists($definition['model']), $slug.' model missing');
            $this->assertTrue(class_exists($definition['controller']), $slug.' controller missing');
            $this->assertNotEmpty($definition['columns']);
            $this->assertNotEmpty($definition['fields']);
        }
    }

    public function test_home_route_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
