<?php

namespace Database\Factories;

use App\Models\SipTrunk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SipTrunk>
 */
class SipTrunkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SipTrunk::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company() . ' SIP Trunk',
            'telnyx_connection_id' => 'conn_' . $this->faker->unique()->regexify('[A-Za-z0-9]{20}'),
            'sip_uri' => $this->faker->optional()->url(),
            'webhook_url' => $this->faker->optional()->url(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending', 'failed']),
            'connection_type' => $this->faker->randomElement(['sip', 'credential', 'webhook']),
            'credentials' => $this->faker->optional()->randomElement([
                ['username' => 'sipuser', 'password' => 'sippass'],
                null
            ]),
            'settings' => $this->faker->optional()->randomElement([
                ['max_calls' => 10, 'codec' => 'PCMU'],
                null
            ]),
            'metadata' => [
                'created_by' => 'factory',
                'test_data' => true
            ],
            'activated_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_health_check' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the SIP trunk is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }

    /**
     * Indicate that the SIP trunk is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'activated_at' => null,
        ]);
    }

    /**
     * Indicate that the SIP trunk is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'activated_at' => null,
        ]);
    }

    /**
     * Indicate that the SIP trunk is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'activated_at' => null,
        ]);
    }
}
