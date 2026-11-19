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
        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider_generation_id')->nullable()->index();
            $table->text('prompt');
            $table->string('size', 50);
            $table->string('quality', 20);
            $table->json('reference_images')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('estimated_provider_cost', 10, 6)->default(0);
            $table->decimal('actual_provider_cost', 10, 6)->nullable();
            $table->decimal('estimated_user_cost', 10, 6)->default(0);
            $table->decimal('actual_user_cost', 10, 6)->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
