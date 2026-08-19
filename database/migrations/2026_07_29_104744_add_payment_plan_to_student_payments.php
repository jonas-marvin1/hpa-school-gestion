<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_payments', function (Blueprint $table) {
            // Nullable : les echeances deja saisies ne sont rattachees a aucun
            // plan, et doivent continuer de fonctionner telles quelles.
            $table->foreignId('payment_plan_id')
                  ->nullable()
                  ->after('program_id')
                  ->constrained('payment_plans')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_payments', function (Blueprint $table) {
            $table->dropForeign(['payment_plan_id']);
            $table->dropColumn('payment_plan_id');
        });
    }
};
