<?php

namespace Database\Factories;

use App\Models\AiInstruction;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiInstructionFactory extends Factory
{
    protected $model = AiInstruction::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'content' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['system', 'topic', 'chat']),
            'is_active' => true,
        ];
    }
}
