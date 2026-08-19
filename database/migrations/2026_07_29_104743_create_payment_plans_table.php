<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan de paiement d'une formation pour un apprenant.
 *
 * Porte ce qui est global au plan : le cout total et l'avance versee a
 * l'inscription. Les echeances, elles, restent dans `student_payments`,
 * une ligne par echeance. Le solde restant se deduit de ces deux tables
 * plutot que d'etre stocke, pour ne jamais pouvoir diverger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('total_amount', 12, 2);                 // cout total de la formation
            $table->decimal('advance_amount', 12, 2)->default(0);   // avance versee a l'inscription

            $table->string('currency', 8)->default('FCFA');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};
