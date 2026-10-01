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
            $table->boolean('notify_warranty_expiry')->default(true);
            $table->boolean('notify_maintenance_due')->default(true);
            $table->boolean('notify_overdue_assignments')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_warranty_expiry',
                'notify_maintenance_due',
                'notify_overdue_assignments',
            ]);
        });
    }
};
