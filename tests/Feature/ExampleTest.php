<?php

namespace Tests\Feature;

use App\Models\Challenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * صفحه اصلی بدون هیچ چالشی هم باید سالم بالا بیاید.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertOk();
    }

    /**
     * و با یک چالش منتشرشده، محتوای آن در صفحه اصلی دیده شود.
     */
    public function test_the_home_page_shows_the_active_challenge(): void
    {
        Challenge::create([
            'title' => 'چالش فعال نمونه',
            'slug' => 'active-sample',
            'tagline' => 'توضیح چالش فعال',
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('چالش فعال نمونه');
    }
}
