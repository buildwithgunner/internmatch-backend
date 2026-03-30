<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'email' => $this->faker->unique()->companyEmail(),
            'password' => Hash::make('password'),
            'description' => $this->faker->paragraph(),
            'website' => $this->faker->url(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'country' => $this->faker->country(),
            'is_verified' => true,
            'email_verified_at' => now(),
        ];
    }
}
