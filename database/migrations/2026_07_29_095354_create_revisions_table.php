<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des modifications.
 *
 * Table generique plutot qu'une table par entite : les notes, les rendus et
 * plus tard la progression de niveau y consignent leurs changements sans
 * qu'il faille creer une structure a chaque fois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();

            // Objet concerne (App\Models\Grade, App\Models\Submission, ...)
            $table->morphs('revisable');

            // Auteur du changement. Nullable : une modification peut provenir
            // d'un traitement automatique, sans utilisateur connecte.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('action', 20);   // created | updated | deleted

            // {champ: {before: ..., after: ...}}
            $table->json('changes')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Lecture typique : l'historique d'un objet, du plus recent au plus ancien.
            $table->index(['revisable_type', 'revisable_id', 'created_at'], 'revisions_objet_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};
