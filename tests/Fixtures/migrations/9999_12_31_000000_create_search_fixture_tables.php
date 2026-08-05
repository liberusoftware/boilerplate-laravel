<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tables for the two searchable fixture models. Registered from
 * {@see TestCase::refreshApplication()}, so they exist only in the test
 * database and never in an application's own migration path.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('search_fixture_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('search_fixture_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['public', 'private', 'restricted'])->default('public');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_fixture_groups');
        Schema::dropIfExists('search_fixture_posts');
    }
};
