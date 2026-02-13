<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quotation>
 */
class QuotationFactory extends Factory
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
        return [
            'asset_id' => Asset::factory(),
            'price' => $this->faker->randomFloat(2, 1, 1000),
            'currency' => 'USD',
            'source' => 'factory',
            'status' => Quotation::STATUS_VALID,
            'invalid_reason' => null,
            'invalidated_at' => null,
            'quoted_at' => now(),
        ];
    }
}
