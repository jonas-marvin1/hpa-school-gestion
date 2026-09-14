<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cette table sert a la fois de surcharge de date limite et
     * d'historique : aucune contrainte d'unicite sur (assignment_id,
     * student_id), un meme apprenant peut etre prolonge plusieurs fois et on
     * garde chaque ligne. C'est la plus recente qui fait foi (voir
     * Assignment::dateLimitePour()). student_id nul = prolongation
     * collective (elle mais aussi la due_date du devoir, cf. le service
     * d'octroi) ; renseigne = prolongation individuelle.
     */
    public function up(): void
    {
        Schema::create('assignment_deadline_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->dateTime('previous_due_date');
            $table->dateTime('new_due_date');
            $table->text('motif');
            $table->foreignId('extended_by')->constrained('users');
            $table->timestamps();

            $table->index(['assignment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_deadline_extensions');
    }
};
