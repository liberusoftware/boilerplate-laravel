<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('two_factor_trusted_devices', function (Blueprint $t): void {
            $t->id();
            $t->string('actor_id')->index();
            $t->string('selector', 32)->unique();
            $t->string('secret_hash', 64);
            $t->string('label')->nullable();
            $t->timestamp('last_used_at');
            $t->timestamp('expires_at')->index();
            $t->timestamp('revoked_at')->nullable()->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_trusted_devices');
    }
};
