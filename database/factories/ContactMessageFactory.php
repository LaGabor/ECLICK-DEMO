<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'subject' => fake()->sentence(rand(3, 6)),
            'message' => fake()->paragraphs(rand(2, 4), true),
            'admin_reply' => null,
            'replied_at' => null,
            'replied_by' => null,
        ];
    }

    public function answered(?User $replier = null): static
    {
        return $this->state(fn (array $_attributes): array => [
            'admin_reply' => fake()->paragraphs(rand(1, 3), true),
            'replied_at' => fake()->dateTimeBetween('-3 weeks', '-1 day'),
            'replied_by' => $replier?->getKey(),
        ]);
    }

    public function unanswered(): static
    {
        return $this->state(fn (array $_attributes): array => [
            'admin_reply' => null,
            'replied_at' => null,
            'replied_by' => null,
        ]);
    }
}
