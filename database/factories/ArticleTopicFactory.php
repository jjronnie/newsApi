<?php

namespace Database\Factories;

use App\Models\ArticleTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleTopicFactory extends Factory
{
    protected $model = ArticleTopic::class;

    public function definition(): array
    {
        return [
            'topic_title' => $this->faker->sentence(),
            'focus_keyword' => $this->faker->word(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'used', 'rejected']),
        ];
    }
}
