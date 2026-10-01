<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->index('warranty_expiry');
        });

        Schema::table('maintenances', function (Blueprint $table) {
            $table->index(['status', 'scheduled_at'], 'maintenances_status_scheduled_index');
        });

        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->index(['status', 'expected_return_at'], 'asset_assignments_status_expected_return_index');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['warranty_expiry']);
        });

        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropIndex('maintenances_status_scheduled_index');
        });

        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->dropIndex('asset_assignments_status_expected_return_index');
        });
    }
};
