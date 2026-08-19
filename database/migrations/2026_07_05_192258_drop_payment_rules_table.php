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
        Schema::dropIfExists('payment_rules');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('payment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('rate_per_session', 8, 2);
            $table->foreignId('course_class_id')->nullable()->constrained('course_classes')->nullOnDelete();
            $table->foreignId('class_session_id')->nullable()->constrained('class_sessions')->nullOnDelete();
            $table->string('intervention_type')->nullable();
            $table->timestamps();
        });
    }
};
