<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('criteria')->comment('Dynamic segment criteria: engagement, location, signup_date, tags, etc.');
            $table->integer('subscriber_count')->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->boolean('is_dynamic')->default(true)->comment('Dynamic segments auto-update, static segments are manually managed');
            $table->timestamps();
        });

        Schema::create('newsletter_segment_subscriber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_segment_id')->constrained('newsletter_segments')->onDelete('cascade');
            $table->foreignId('newsletter_subscriber_id')->constrained('newsletter_subscribers')->onDelete('cascade');
            $table->timestamp('added_at')->useCurrent();
            $table->unique(['newsletter_segment_id', 'newsletter_subscriber_id'], 'nss_segment_subscriber_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_segment_subscriber');
        Schema::dropIfExists('newsletter_segments');
    }
};
