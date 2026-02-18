<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['public', 'private', 'restricted'])->default('public');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for search performance
            $table->index('name');
            $table->index('type');
            $table->index('owner_id');
            $table->index('is_active');
            $table->fullText(['name', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
