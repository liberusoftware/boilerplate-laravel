<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('scoped_settings', function (Blueprint $t): void {
            $t->id();
            $t->string('scope_type');
            $t->string('scope_id');
            $t->string('key');
            $t->longText('value');
            $t->boolean('secret')->default(false);
            $t->timestamps();
            $t->unique(['scope_type', 'scope_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoped_settings');
    }
};
