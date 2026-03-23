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
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index('deleted_at');
        });

        Schema::table('recruiters', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('is_verified');
            $table->index('deleted_at');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->index('is_verified');
            $table->index('deleted_at');
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->index('recruiter_id');
            $table->index('status');
            $table->index('category');
            $table->index('target_faculty');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->index('student_id');
            $table->index('internship_id');
            $table->index('status');
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('university');
            $table->index('faculty');
            $table->index('department');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_id', 'notifiable_type']);
            $table->index('read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('recruiters', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['is_verified']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['is_verified']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->dropIndex(['recruiter_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['category']);
            $table->dropIndex(['target_faculty']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['student_id']);
            $table->dropIndex(['internship_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['university']);
            $table->dropIndex(['faculty']);
            $table->dropIndex(['department']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['notifiable_id', 'notifiable_type']);
            $table->dropIndex(['read_at']);
        });
    }
};
