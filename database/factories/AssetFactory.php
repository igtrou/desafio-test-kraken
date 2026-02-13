<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Executa a rotina principal do metodo definition.
     */
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['stock', 'crypto', 'currency']);

        return [
            'symbol' => strtoupper($this->faker->lexify('????')),
            'name' => match ($type) {
                'crypto' => $this->faker->randomElement(['Bitcoin', 'Ethereum', 'Solana', 'Cardano']),
                'currency' => $this->faker->currencyCode(),
                default => $this->faker->company(),
            },
            'type' => $type,
        ];
    }
}
