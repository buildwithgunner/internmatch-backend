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
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->index('preferred_role');
            $table->index('internship_type');
            $table->index('availability');
        });

        Schema::table('recruiters', function (Blueprint $table) {
            $table->index('sector');
            $table->index('name');
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->index('title');
            $table->index('location');
            $table->index('type');
        });
        
        Schema::table('companies', function (Blueprint $table) {
            $table->index('company_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropIndex(['preferred_role']);
            $table->dropIndex(['internship_type']);
            $table->dropIndex(['availability']);
        });

        Schema::table('recruiters', function (Blueprint $table) {
            $table->dropIndex(['sector']);
            $table->dropIndex(['name']);
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->dropIndex(['title']);
            $table->dropIndex(['location']);
            $table->dropIndex(['type']);
        });
        
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['company_name']);
        });
    }
};
