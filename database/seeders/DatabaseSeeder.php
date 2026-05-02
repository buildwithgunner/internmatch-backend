<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Companies
        $companies = \App\Models\Company::factory(5)->create();

        // 2. Create Recruiters for each company
        foreach ($companies as $company) {
            \App\Models\Recruiter::factory(2)->create([
                'company_id' => $company->id,
                'company_name' => $company->company_name,
            ]);
        }

        // 3. Create Students with diverse fields of study
        $faculties = [
            'Engineering' => ['Software Engineering', 'Electrical Engineering', 'Mechanical Engineering'],
            'Science' => ['Computer Science', 'Mathematics', 'Physics'],
            'Business' => ['Accounting', 'Marketing', 'Finance'],
            'Arts' => ['Graphic Design', 'Visual Arts'],
        ];

        foreach ($faculties as $faculty => $departments) {
            foreach ($departments as $department) {
                // Create 3 students for each department
                \App\Models\User::factory(3)->create(['role' => 'student'])->each(function ($user) use ($faculty, $department) {
                    \App\Models\StudentProfile::factory()->create([
                        'user_id' => $user->id,
                        'faculty' => $faculty,
                        'department' => $department,
                    ]);
                });
            }
        }

        // 4. Create an Admin user
        $this->call(AdminSeeder::class);
        
        // 5. Create a test student
        $student = \App\Models\User::factory()->create([
            'name' => 'John Doe',
            'email' => 'student@internmatch.com',
            'role' => 'student',
        ]);
        
        \App\Models\StudentProfile::factory()->create([
            'user_id' => $student->id,
            'faculty' => 'Engineering',
            'department' => 'Software Engineering',
        ]);
    }
}
