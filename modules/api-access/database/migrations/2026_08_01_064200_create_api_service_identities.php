<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('api_service_identities', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('owner_ref')->index();
            $t->json('scopes');
            $t->string('status')->default('active')->index();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_service_identities');
    }
};
