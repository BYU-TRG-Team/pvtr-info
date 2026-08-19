<?php

namespace Database\Factories;

use App\Enums\ComplaintMessageAuthorType;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplaintMessage>
 */
class ComplaintMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'user_id' => null,
            'author_type' => ComplaintMessageAuthorType::Complainant,
            'body' => fake()->paragraph(),
        ];
    }

    public function complainant(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'author_type' => ComplaintMessageAuthorType::Complainant,
        ]);
    }

    public function admin(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'author_type' => ComplaintMessageAuthorType::Admin,
        ]);
    }
}
