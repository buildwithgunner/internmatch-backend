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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('country')->nullable()->after('address');
        });

        Schema::table('recruiters', function (Blueprint $table) {
            $table->string('country')->nullable()->after('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('country');
        });

        Schema::table('recruiters', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
