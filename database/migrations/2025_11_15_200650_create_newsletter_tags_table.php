<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('subscriber_count')->default(0);
            $table->timestamps();
        });

        Schema::create('newsletter_subscriber_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_subscriber_id')->constrained('newsletter_subscribers')->onDelete('cascade');
            $table->foreignId('newsletter_tag_id')->constrained('newsletter_tags')->onDelete('cascade');
            $table->timestamp('added_at')->useCurrent();
            $table->unique(['newsletter_subscriber_id', 'newsletter_tag_id'], 'nst_subscriber_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscriber_tag');
        Schema::dropIfExists('newsletter_tags');
    }
};
