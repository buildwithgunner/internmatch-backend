<?php

namespace Database\Factories;

use App\Models\Recruiter;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recruiter>
 */
class RecruiterFactory extends Factory
{
    protected $model = Recruiter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'phone' => $this->faker->phoneNumber(),
            'company_id' => Company::factory(),
            'company_name' => function (array $attributes) {
                return Company::find($attributes['company_id'])->company_name;
            },
            'sector' => $this->faker->jobTitle(),
            'is_verified' => true,
            'email_verified_at' => now(),
            'trust_score' => $this->faker->numberBetween(60, 100),
        ];
    }
}
