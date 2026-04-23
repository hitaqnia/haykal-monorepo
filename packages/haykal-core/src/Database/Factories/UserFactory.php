<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Database\Factories;

use HiTaqnia\Haykal\Core\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'huwiya_id' => (string) Str::ulid(),
            'name' => fake()->name(),
            'phone' => '+964'.fake()->numerify('7#########'),
            'email' => fake()->unique()->safeEmail(),
            'locale' => fake()->randomElement(['en', 'ar', '']),
            'zoneinfo' => fake()->randomElement(['Asia/Baghdad', 'UTC', '']),
            'theme' => fake()->randomElement(['light', 'dark', '']),
        ];
    }

    public function withoutHuwiya(): static
    {
        return $this->state(fn () => ['huwiya_id' => null]);
    }
}
