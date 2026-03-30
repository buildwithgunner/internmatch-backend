<?php

namespace Database\Factories;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    protected $model = StudentProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faculties = [
            'Engineering' => ['Software Engineering', 'Electrical Engineering', 'Mechanical Engineering', 'Civil Engineering'],
            'Science' => ['Computer Science', 'Mathematics', 'Physics', 'Biology'],
            'Arts' => ['Graphic Design', 'Visual Arts', 'Fine Arts', 'Music'],
            'Business' => ['Accounting', 'Marketing', 'Finance', 'Human Resources'],
        ];

        $faculty = $this->faker->randomElement(array_keys($faculties));
        $department = $this->faker->randomElement($faculties[$faculty]);

        return [
            'user_id' => User::factory(),
            'university' => $this->faker->company() . ' University',
            'faculty' => $faculty,
            'department' => $department,
            'level' => $this->faker->randomElement(['100', '200', '300', '400', '500']),
            'graduation_year' => $this->faker->numberBetween(2024, 2028),
            'bio' => $this->faker->sentence(),
            'skills' => implode(', ', $this->faker->words(5)),
            'country' => $this->faker->country(),
            'state' => $this->faker->state(),
            'city' => $this->faker->city(),
            'preferred_role' => $this->faker->jobTitle(),
            'internship_type' => $this->faker->randomElement(['Remote', 'Onsite', 'Hybrid']),
            'availability' => $this->faker->randomElement(['Full-time', 'Part-time']),
        ];
    }
}
