<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estacion_id')->constrained('estaciones')->onDelete('cascade');
            $table->float('distancia_cm');
            $table->float('porcentaje');
            $table->float('litros');
            $table->string('estado');
            $table->string('ip_origen')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
};