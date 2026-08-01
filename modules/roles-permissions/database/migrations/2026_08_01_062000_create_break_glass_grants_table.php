<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('authorization_break_glass', function (Blueprint $t): void {
            $t->id();
            $t->string('actor_id')->index();
            $t->string('permission')->index();
            $t->text('reason');
            $t->timestamp('expires_at')->index();
            $t->timestamp('revoked_at')->nullable();
            $t->string('revoked_by')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_break_glass');
    }
};
