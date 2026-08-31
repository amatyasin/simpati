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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active', 'inactive', 'locked'])->default('pending')->after('remember_token');
            $table->string('phone', 20)->nullable()->after('status');
            $table->string('avatar')->nullable()->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('avatar');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->unsignedInteger('failed_login_count')->default(0)->after('last_login_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'phone', 'avatar', 'last_login_at', 'last_login_ip', 'failed_login_count']);
        });
    }
};
