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
        Schema::create('session_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_class_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->integer('month');
            $table->unsignedSmallInteger('quota');
            $table->timestamps();

            // Une seule ligne possible par classe et par mois : permet un
            // upsert direct depuis le formulaire de saisie, sans doublon.
            $table->unique(['course_class_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_quotas');
    }
};
