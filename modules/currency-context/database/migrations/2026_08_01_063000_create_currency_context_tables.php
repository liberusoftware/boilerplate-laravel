<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('currency_preferences', function (Blueprint $t): void {
            $t->id();
            $t->string('scope_type');
            $t->string('scope_id');
            $t->char('currency', 3);
            $t->timestamps();
            $t->unique(['scope_type', 'scope_id']);
        });
        Schema::create('exchange_rate_snapshots', function (Blueprint $t): void {
            $t->id();
            $t->char('base_currency', 3);
            $t->char('quote_currency', 3);
            $t->decimal('rate', 30, 12);
            $t->string('source');
            $t->string('rate_type');
            $t->timestamp('effective_at')->index();
            $t->unsignedTinyInteger('precision');
            $t->boolean('inverted')->default(false);
            $t->timestamps();
            $t->index(['base_currency', 'quote_currency', 'effective_at'], 'rate_pair_effective_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_snapshots');
        Schema::dropIfExists('currency_preferences');
    }
};
