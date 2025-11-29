<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->softDeletes()->after('confirmation_token');
            $table->string('source')->nullable()->comment('signup_form, csv_import, manual, api, etc.')->after('confirmation_token');
            $table->timestamp('confirmed_at')->nullable()->after('confirmed');
            $table->string('status')->default('subscribed')->comment('subscribed, unsubscribed, bounced, invalid')->after('confirmed_at');
            $table->json('custom_fields')->nullable()->comment('custom metadata fields')->after('status');
            $table->integer('engagement_score')->default(0)->after('custom_fields');
            $table->timestamp('last_engaged_at')->nullable()->after('engagement_score');
            $table->unsignedInteger('bounce_count')->default(0)->after('last_engaged_at');
            $table->string('bounce_type')->nullable()->comment('soft, hard')->after('bounce_count');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('source', 'confirmed_at', 'status', 'custom_fields', 'engagement_score', 'last_engaged_at', 'bounce_count', 'bounce_type');
        });
    }
};
