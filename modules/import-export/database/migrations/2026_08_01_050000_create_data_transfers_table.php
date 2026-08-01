<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('data_transfers', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('actor_ref')->index();
            $t->string('direction');
            $t->string('schema');
            $t->string('schema_version');
            $t->json('mapping')->nullable();
            $t->boolean('dry_run')->default(true);
            $t->string('status')->index();
            $t->unsignedBigInteger('total')->default(0);
            $t->unsignedBigInteger('processed')->default(0);
            $t->unsignedBigInteger('failed')->default(0);
            $t->text('source_path')->nullable();
            $t->text('result_path')->nullable();
            $t->text('error_report_path')->nullable();
            $t->timestamp('expires_at')->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_transfers');
    }
};
