<?php

namespace Database\Factories;

use App\Enums\PageType;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Page> */
class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return ['title' => ucfirst($title), 'slug' => Str::slug($title), 'type' => PageType::Page];
    }

    public function home(): static
    {
        return $this->state(['type' => PageType::Home, 'slug' => 'beranda-'.Str::random(4)]);
    }
}
