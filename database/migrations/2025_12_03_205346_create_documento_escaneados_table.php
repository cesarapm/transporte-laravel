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
        Schema::create('documento_escaneados', function (Blueprint $table) {
            $table->id();

            // Archivo original
            $table->string('archivo_original')->comment('Ruta del archivo de imagen');
            $table->string('nombre_archivo')->comment('Nombre original del archivo');
            $table->string('tipo_mime')->comment('Tipo MIME del archivo');
            $table->bigInteger('tamaño_archivo')->comment('Tamaño en bytes');

            // Información del documento
            $table->string('folio')->nullable()->comment('Número de folio del documento');
            $table->date('fecha_documento')->nullable()->comment('Fecha del documento');

            // Datos del remitente
            $table->string('remitente_nombre')->nullable();
            $table->string('remitente_telefono')->nullable();
            $table->text('remitente_direccion')->nullable();
            $table->string('remitente_colonia')->nullable();
            $table->string('remitente_ciudad')->nullable();
            $table->string('remitente_estado')->nullable();
            $table->string('remitente_cp')->nullable();
            $table->string('remitente_pais')->nullable()->default('US');

            // Datos del destinatario
            $table->string('destinatario_nombre')->nullable();
            $table->string('destinatario_telefono')->nullable();
            $table->text('destinatario_direccion')->nullable();
            $table->string('destinatario_colonia')->nullable();
            $table->string('destinatario_ciudad')->nullable();
            $table->string('destinatario_estado')->nullable();
            $table->string('destinatario_cp')->nullable();
            $table->string('destinatario_pais')->nullable()->default('MX');

            // Información del envío
            $table->integer('numero_cajas')->nullable();
            $table->string('tipo_contenido')->nullable()->comment('Ropa, Zapatos, Electrónicos, etc.');
            $table->decimal('peso', 8, 2)->nullable()->comment('Peso en libras');
            $table->decimal('valor_asegurado', 10, 2)->nullable();
            $table->decimal('valor_declarado', 10, 2)->nullable();
            $table->decimal('costo_flete', 10, 2)->nullable();
            $table->decimal('impuestos', 10, 2)->nullable();
            $table->decimal('seguro_extra', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();

            // Datos de procesamiento
            $table->json('texto_raw')->nullable()->comment('Texto completo extraído por OCR');
            $table->json('confianza_ocr')->nullable()->comment('Niveles de confianza del OCR');
            $table->enum('estado_procesamiento', ['pendiente', 'procesado', 'error', 'verificado'])->default('pendiente');
            $table->text('errores_procesamiento')->nullable();

            // Metadatos
            $table->json('metadatos_vision')->nullable()->comment('Respuesta completa de Google Vision');
            $table->boolean('requiere_revision')->default(false);
            $table->text('notas_revision')->nullable();

            $table->timestamps();

            // Índices
            $table->index('folio');
            $table->index('fecha_documento');
            $table->index('estado_procesamiento');
            $table->index('remitente_nombre');
            $table->index('destinatario_nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documento_escaneados');
    }
};
