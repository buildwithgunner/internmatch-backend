<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Academic Info
            $table->string('university');
            $table->string('faculty');
            $table->string('department');
            $table->string('level');
            $table->integer('graduation_year');
            
            // Profile Details
            $table->text('bio')->nullable();
            $table->text('skills')->nullable();
            $table->string('resume')->nullable();
            
            // Location
            $table->string('country');
            $table->string('state');
            $table->string('city')->nullable();
            
            // Links
            $table->string('portfolio_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('website_url')->nullable();

            // Internship Preferences
            $table->string('preferred_role')->nullable();
            $table->enum('internship_type', ['Remote', 'Onsite', 'Hybrid'])->default('Onsite');
            $table->enum('availability', ['Full-time', 'Part-time'])->default('Full-time');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
