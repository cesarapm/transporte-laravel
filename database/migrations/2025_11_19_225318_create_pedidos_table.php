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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->decimal('carga', 10, 2);
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent')->nullable();
            $table->enum('estatus', ['pendiente', 'pagado', 'cancelado', 'fallido'])->default('pendiente');

            // Datos del remitente
            $table->string('remitente_nombre');
            $table->string('remitente_celular');
            $table->text('remitente_direccion');
            $table->string('remitente_estado');
            $table->string('remitente_ciudad');
            $table->string('remitente_codigo_postal');
            $table->string('remitente_pais');

            // Datos del destinatario
            $table->string('destinatario_nombre');
            $table->string('destinatario_celular');
            $table->text('destinatario_direccion');
            $table->string('destinatario_estado');
            $table->string('destinatario_ciudad');
            $table->string('destinatario_codigo_postal');
            $table->string('destinatario_pais');

            // Datos del paquete
            $table->decimal('paquete_alto', 8, 2);
            $table->decimal('paquete_ancho', 8, 2);
            $table->decimal('paquete_largo', 8, 2);
            $table->decimal('paquete_peso', 8, 2);
            $table->decimal('paquete_volumen_calculado', 10, 2);

            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamp('fecha_cotizacion')->nullable();

            $table->timestamps();

            $table->index('stripe_session_id');
            $table->index('estatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
