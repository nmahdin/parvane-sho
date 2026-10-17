<?php

namespace Tests\Feature;

use App\Models\Challenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $challenge = Challenge::create([
            'title' => 'چالش نمونه',
            'slug' => 'sample',
            'tagline' => 'توضیح نمونه',
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('چالش نمونه')
            ->assertSee($challenge->tagline);
    }

    public function test_challenges_index_only_lists_published_challenges(): void
    {
        Challenge::create(['title' => 'چالش منتشرشده', 'slug' => 'published', 'is_published' => true]);
        Challenge::create(['title' => 'چالش پنهان', 'slug' => 'hidden', 'is_published' => false]);

        $this->get(route('challenges.index'))
            ->assertOk()
            ->assertSee('چالش منتشرشده')
            ->assertDontSee('چالش پنهان');
    }

    public function test_unpublished_challenge_page_is_not_public(): void
    {
        $challenge = Challenge::create(['title' => 'پنهان', 'slug' => 'not-published', 'is_published' => false]);

        $this->get(route('challenges.show', $challenge))->assertNotFound();
    }

    public function test_persian_date_and_number_helpers_are_available(): void
    {
        $this->assertSame('۱۴۰۵', fa_num(1405));
        $this->assertSame('۲۰ مهر ۱۴۰۵', fa_date('2026-10-12'));
        $this->assertSame('1405', fa_digits_to_latin('۱۴۰۵'));
    }

    public function test_challenge_page_shows_days_and_tasks(): void
    {
        $challenge = Challenge::create([
            'title' => 'چالش پنج روزه',
            'slug' => 'five-days',
            'is_published' => true,
        ]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);

        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('کار روز اول')
            ->assertSee('روز ۱');
    }
}
