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
        Schema::table('assignments', function (Blueprint $table) {
            // Vide = toute la classe (comportement actuel) ; renseignee =
            // attribution a cet apprenant seul. cascadeOnDelete et non
            // nullOnDelete : un devoir nominatif ne doit pas redevenir
            // visible par toute la classe si l'apprenant vise est supprime.
            $table->foreignId('student_id')
                ->nullable()
                ->after('course_class_id')
                ->constrained('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
        });
    }
};
