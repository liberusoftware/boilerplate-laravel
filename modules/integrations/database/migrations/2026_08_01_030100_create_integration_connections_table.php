<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('integration_connections', function (Blueprint $t): void {
            $t->id();
            $t->string('scope_type');
            $t->string('scope_id');
            $t->string('provider');
            $t->text('credentials');
            $t->json('capabilities');
            $t->string('status')->default('pending');
            $t->timestamp('last_tested_at')->nullable();
            $t->timestamp('last_synced_at')->nullable();
            $t->text('last_error')->nullable();
            $t->timestamps();
            $t->unique(['scope_type', 'scope_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_connections');
    }
};
