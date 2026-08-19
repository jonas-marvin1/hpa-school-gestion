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
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->string('intervention_type')->nullable()->after('end_time');
        });

        Schema::table('payment_rules', function (Blueprint $table) {
            $table->foreignId('course_class_id')->nullable()->constrained('course_classes')->nullOnDelete()->after('coach_id');
            $table->foreignId('class_session_id')->nullable()->constrained('class_sessions')->nullOnDelete()->after('course_class_id');
            $table->string('intervention_type')->nullable()->after('class_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_rules', function (Blueprint $table) {
            $table->dropForeign(['course_class_id']);
            $table->dropForeign(['class_session_id']);
            $table->dropColumn(['course_class_id', 'class_session_id', 'intervention_type']);
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropColumn('intervention_type');
        });
    }
};
