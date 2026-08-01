<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'timezone')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('timezone', 64)->nullable()->after('locale'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'timezone')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('timezone'));
        }
    }
};
