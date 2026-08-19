<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Echelle de niveau d'anglais de l'apprenant.
 *
 * A ne pas confondre avec la table `levels`, qui decoupe un programme en
 * paliers pedagogiques. Ici il s'agit du niveau CECRL de la personne, qui
 * la suit d'un programme a l'autre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('english_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();               // A1, A2-, A2+, ...
            $table->string('label');                            // libelle affiche
            $table->unsignedTinyInteger('position')->unique();  // ordre de progression
            $table->timestamps();
        });

        // L'echelle est fixe et structurante : elle est posee ici plutot que
        // dans un seeder, pour qu'aucun environnement ne puisse s'en passer.
        $maintenant = now();
        $echelle = [
            ['A1',  'A1 — Débutant',                 1],
            ['A2-', 'A2- — Élémentaire',             2],
            ['A2+', 'A2+ — Élémentaire avancé',      3],
            ['B1',  'B1 — Intermédiaire',            4],
            ['B2-', 'B2- — Intermédiaire supérieur', 5],
            ['B2+', 'B2+ — Avancé',                  6],
            ['C1',  'C1 — Autonome',                 7],
            ['C2-', 'C2- — Maîtrise',                8],
            ['C2+', 'C2+ — Maîtrise complète',       9],
        ];

        DB::table('english_levels')->insert(array_map(
            fn ($n) => [
                'code'       => $n[0],
                'label'      => $n[1],
                'position'   => $n[2],
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ],
            $echelle
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('english_levels');
    }
};
