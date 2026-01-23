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
        Schema::table('guias_mindee', function (Blueprint $table) {
            // Agregar país y estado del remitente después de shipper_zip_code
            $table->string('shipper_state')->nullable()->after('shipper_zip_code');
            $table->string('shipper_country')->nullable()->after('shipper_state');

            // Agregar teléfono del destinatario después de consignee_country
            $table->string('consignee_phone_number')->nullable()->after('consignee_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guias_mindee', function (Blueprint $table) {
            $table->dropColumn(['shipper_state', 'shipper_country', 'consignee_phone_number']);
        });
    }
};
