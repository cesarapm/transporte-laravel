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
        Schema::create('guias', function (Blueprint $table) {
            $table->id();
            $table->string('remesa');
            $table->string('tel_remite');
            $table->string('folio');
            $table->string('guia_interna')->nullable()->unique(); // Permitir valores nulos
            $table->date('fecha')->nullable(); // Separa fecha en un campo DATE
            $table->time('hora')->nullable();  // Agrega hora en un campo TIME
            $table->string('estatus')->default('TIE'); // Estado del pedido
            $table->boolean('activo')->default(true); // Activo/Inactivo
            $table->string('paqueteria')->nullable(); // Nombre de la paquetería
            $table->integer('npaquetes'); // Número de paquetes
            $table->string('rastreo')->nullable(); // Número de rastreo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guias');
    }
};
