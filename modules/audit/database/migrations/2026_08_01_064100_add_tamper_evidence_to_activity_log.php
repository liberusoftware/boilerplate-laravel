<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $t): void {
            $t->char('previous_hash', 64)->nullable();
            $t->char('record_hash', 64)->nullable()->unique();
            $t->string('tenant_ref')->nullable()->index();
            $t->string('correlation_id')->nullable()->index();
            $t->timestamp('retain_until')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', fn (Blueprint $t) => $t->dropColumn(['previous_hash', 'record_hash', 'tenant_ref', 'correlation_id', 'retain_until']));
    }
};
