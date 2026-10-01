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
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->morphs('assignable');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->date('expected_return_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('condition_out')->nullable();
            $table->string('condition_in')->nullable();
            $table->text('checkout_notes')->nullable();
            $table->text('checkin_notes')->nullable();
            $table->string('status')->default('active')->index();
            // 1 while active, NULL once returned: the unique index allows only
            // one active assignment per asset while permitting unlimited history.
            $table->tinyInteger('active')->nullable();
            $table->timestamps();

            $table->unique(['asset_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
