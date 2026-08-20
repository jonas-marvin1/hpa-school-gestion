<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue une correction d'erreur de saisie d'une evolution normale.
 *
 * Colonne generique sur le journal partage, pas specifique au niveau
 * d'anglais : n'importe quel modele utilisant RecordsRevisions pourra s'en
 * servir. Le journal reste append-only, cette colonne ne fait qu'annoter une
 * ligne au moment de sa creation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revisions', function (Blueprint $table) {
            $table->boolean('is_correction')->default(false)->after('changes');
        });
    }

    public function down(): void
    {
        Schema::table('revisions', function (Blueprint $table) {
            $table->dropColumn('is_correction');
        });
    }
};
