<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('status')->default('active')->index();
            $t->foreignId('owner_id')->index();
            $t->timestamp('archived_at')->nullable();
            $t->timestamps();
        });
        Schema::table('teams', function (Blueprint $t): void {
            $t->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
            $t->string('status')->default('active')->index();
        });
        Schema::table('team_user', function (Blueprint $t): void {
            $t->string('status')->default('active')->index();
            $t->foreignId('invited_by')->nullable();
            $t->timestamp('effective_from')->nullable();
            $t->timestamp('effective_until')->nullable();
            $t->timestamp('terms_accepted_at')->nullable();
        });
        Schema::table('team_invitations', function (Blueprint $t): void {
            $t->string('token_hash', 64)->nullable()->unique();
            $t->foreignId('invited_by')->nullable();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('team_invitations', fn (Blueprint $t) => $t->dropColumn(['token_hash', 'invited_by', 'expires_at', 'accepted_at', 'revoked_at']));
        Schema::table('team_user', fn (Blueprint $t) => $t->dropColumn(['status', 'invited_by', 'effective_from', 'effective_until', 'terms_accepted_at']));
        Schema::table('teams', function (Blueprint $t): void {
            $t->dropConstrainedForeignId('organization_id');
            $t->dropColumn('status');
        });
        Schema::dropIfExists('organizations');
    }
};
