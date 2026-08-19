<?php

namespace Database\Factories;

use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use App\Models\Complaint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_record_id' => null,
            'public_reference' => fake()->unique()->numerify('CMP-####-#####'),
            'secret_link_key' => Str::random(64),
            'complainant_name' => fake()->name(),
            'complainant_email' => fake()->safeEmail(),
            'complainant_phone' => fake()->optional()->phoneNumber(),
            'license_number' => fake()->numerify('###-###'),
            'license_status_at_filing' => 'valid',
            'complaint_type' => fake()->randomElement(ComplaintType::cases()),
            'status' => ComplaintStatus::UnderReview,
            'details' => [],
            'filed_at' => now(),
        ];
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComplaintStatus::UnderReview,
        ]);
    }

    public function replySent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComplaintStatus::ReplySent,
        ]);
    }
}
