<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'username' => User::generateUniqueUsername($name),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'type' => User::TYPE_USER,
        ];
    }

    /**
     * Set the user type to staff.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => User::TYPE_STAFF,
        ]);
    }

    /**
     * Set the user type to contributor.
     */
    public function contributor(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => User::TYPE_CONTRIBUTOR,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Configure the user with the Admin role.
     */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Admin');
        });
    }

    /**
     * Configure the user with the Developer role.
     */
    public function developer(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Developer');
        });
    }

    /**
     * Configure the user with the Editor role.
     */
    public function editor(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Editor');
        });
    }

    /**
     * Configure the user with the Writer role.
     */
    public function writer(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Writer');
        });
    }

    /**
     * Configure the user with the Photographer role.
     */
    public function photographer(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Photographer');
        });
    }

    /**
     * Configure the user with the User role (no CMS access).
     */
    public function regularUser(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('User');
        });
    }
}
