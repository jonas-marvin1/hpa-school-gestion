<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable : les apprenants deja inscrits n'ont pas de niveau
            // renseigne, et les coachs ou gestionnaires n'en ont pas du tout.
            $table->foreignId('english_level_id')
                  ->nullable()
                  ->after('status')
                  ->constrained('english_levels')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['english_level_id']);
            $table->dropColumn('english_level_id');
        });
    }
};
