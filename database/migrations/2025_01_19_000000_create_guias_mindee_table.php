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
        Schema::create('guias_mindee', function (Blueprint $table) {
            $table->id();

            // Información del archivo
            $table->string('archivo_original')->nullable();
            $table->string('nombre_archivo')->nullable();
            $table->string('tipo_mime')->nullable();
            $table->bigInteger('tamaño_archivo')->nullable();

            // Información del transportista
            $table->string('carrier_name')->nullable();
            $table->text('carrier_address')->nullable();
            $table->string('manifest_number')->nullable();
            $table->string('folio_invoice_number')->nullable();
            $table->date('ship_date')->nullable();

            // Remitente
            $table->string('shipper_name')->nullable();
            $table->text('shipper_address')->nullable();
            $table->string('shipper_city')->nullable();
            $table->string('shipper_suburb')->nullable();
            $table->string('shipper_zip_code')->nullable();
            $table->string('ship_phone_number')->nullable();

            // Destinatario
            $table->string('consignee_name')->nullable();
            $table->text('consignee_address')->nullable();
            $table->string('consignee_colonia')->nullable();
            $table->string('consignee_city')->nullable();
            $table->string('consignee_state')->nullable();
            $table->string('consignee_zip_code')->nullable();
            $table->string('consignee_country')->nullable();

            // Información del envío
            $table->integer('total_packages')->nullable();
            $table->integer('shipper_box_count')->nullable();
            $table->decimal('total_weight', 10, 2)->nullable();
            $table->string('weight_unit')->nullable();

            // Costos
            $table->decimal('shipper_freight_cost', 10, 2)->nullable();
            $table->decimal('shipper_insured_value', 10, 2)->nullable();

            // Items (JSON)
            $table->json('item_categories')->nullable();
            $table->json('shipment_line_items')->nullable();

            // Tracking
            $table->string('tracking_number')->nullable();

            // Firma
            $table->text('signature')->nullable();
            $table->text('agent_signature')->nullable();

            // Procesamiento
            $table->longText('texto_raw')->nullable();
            $table->json('datos_json')->nullable();
            $table->decimal('confianza_promedio', 5, 2)->nullable();
            $table->enum('estado_procesamiento', ['pendiente', 'procesado', 'error', 'verificado'])->default('pendiente');
            $table->text('error_mensaje')->nullable();
            $table->boolean('requiere_revision')->default(false);
            $table->timestamp('fecha_procesamiento')->nullable();
            $table->foreignId('procesado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Índices
            $table->index('manifest_number');
            $table->index('folio_invoice_number');
            $table->index('tracking_number');
            $table->index('estado_procesamiento');
            $table->index('ship_date');
            $table->index('consignee_name');
            $table->index('shipper_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guias_mindee');
    }
};
