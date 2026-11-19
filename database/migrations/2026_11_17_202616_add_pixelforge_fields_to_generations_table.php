<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->timestamp('image_url_expires_at')->nullable()->after('image_url');
            $table->string('error_code', 100)->nullable()->after('failure_reason');
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn(['image_url_expires_at', 'error_code']);
        });
    }
};
