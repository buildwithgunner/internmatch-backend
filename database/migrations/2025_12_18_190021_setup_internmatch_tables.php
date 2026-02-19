<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    // 2. Create internships table
    Schema::create('internships', function (Blueprint $table) {
        $table->id();
        $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
        $table->string('title');
        $table->text('description');
        $table->string('location');
        $table->enum('type', ['Remote', 'Onsite', 'Hybrid']);
        $table->string('duration')->nullable();
        $table->string('stipend')->nullable();
        $table->boolean('paid')->default(false);
        $table->date('deadline')->nullable();
        $table->enum('status', ['active', 'paused', 'closed'])->default('active');
        $table->timestamps();
    });

    // 3. Create applications table
    Schema::create('applications', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('internship_id')->constrained()->onDelete('cascade');
        $table->text('cover_letter_text')->nullable();
        $table->string('portfolio_url')->nullable();
        $table->enum('status', ['pending', 'reviewed', 'interview', 'rejected', 'accepted', 'offered'])->default('pending');
        $table->timestamps();

        $table->unique(['student_id', 'internship_id']);
    });

    // 4. Create documents table
    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->enum('type', [
            'resume',
            'cover_letter',
            'student_id',
            'transcript',
            'primary_certificate',
            'secondary_certificate',
            'university_certificate',
            'certificate',
            'recommendation_letter',
            'passport_photo'
        ]);
        $table->string('file_path');
        $table->string('original_name')->nullable();
        $table->timestamps();

        $table->unique(['user_id', 'type']);
    });

    // 5. Create pivot table
    Schema::create('application_document', function (Blueprint $table) {
        $table->id();
        $table->foreignId('application_id')->constrained()->onDelete('cascade');
        $table->foreignId('document_id')->constrained()->onDelete('cascade');
        $table->timestamps();

        $table->unique(['application_id', 'document_id']);
    });
}

    public function down(): void
    {
        Schema::dropIfExists('application_document');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('internships');
    }
};
