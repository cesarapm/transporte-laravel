<?php

/**
 * Script de prueba para verificar la integración con Mindee
 *
 * Ejecutar con: php test_mindee.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\MindeeApiService;
use Illuminate\Support\Facades\Log;

echo "=== Prueba de Mindee API ===\n\n";

// 1. Verificar configuración
$service = new MindeeApiService();
$estado = $service->verificarEstado();

echo "Estado de configuración:\n";
echo "- Configurado: " . ($estado['configurado'] ? 'SÍ' : 'NO') . "\n";
echo "- API Key presente: " . ($estado['api_key_presente'] ? 'SÍ' : 'NO') . "\n\n";

if (!$estado['configurado']) {
    echo "❌ ERROR: Mindee no está configurado correctamente\n";
    echo "Verifica que el API Key esté correctamente en MindeeApiService.php\n";
    exit(1);
}

echo "✅ Mindee está configurado correctamente\n\n";

// 2. Buscar una imagen de prueba
$rutaPrueba = 'storage/app/public/guias_mindee';
if (!is_dir($rutaPrueba)) {
    echo "⚠️  No hay carpeta de guias_mindee. Crea una guía primero desde Filament.\n";
    exit(0);
}

$archivos = glob($rutaPrueba . '/*.{jpg,jpeg,png,pdf}', GLOB_BRACE);

if (empty($archivos)) {
    echo "⚠️  No hay archivos en $rutaPrueba\n";
    echo "Sube una guía desde Filament primero.\n";
    exit(0);
}

$archivoTest = $archivos[0];
echo "📄 Usando archivo: " . basename($archivoTest) . "\n\n";

// 3. Procesar documento
echo "🔄 Procesando documento...\n\n";

try {
    $resultado = $service->procesarDocumento($archivoTest);

    if ($resultado['success']) {
        echo "✅ ¡ÉXITO! Documento procesado correctamente\n\n";

        // MOSTRAR TODO EL JSON COMPLETO
        echo "==========================================\n";
        echo "JSON COMPLETO DE RESPUESTA:\n";
        echo "==========================================\n";
        echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

        echo "==========================================\n";
        echo "DATOS ESTRUCTURADOS:\n";
        echo "==========================================\n";
        echo json_encode($resultado['datos'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

        echo "Confianza: " . ($resultado['confianza'] * 100) . "%\n\n";

        echo "=== INFORMACIÓN COMPLETA EXTRAÍDA ===\n\n";

        $datos = $resultado['datos'];

        echo "📦 TRANSPORTISTA:\n";
        echo "  - Nombre: " . ($datos['carrier_name'] ?? 'N/A') . "\n";
        echo "  - Dirección: " . ($datos['carrier_address'] ?? 'N/A') . "\n";
        echo "  - Folio/Manifiesto: " . ($datos['manifest_number'] ?? 'N/A') . "\n";
        echo "  - Factura: " . ($datos['folio_invoice_number'] ?? 'N/A') . "\n";
        echo "  - Fecha: " . ($datos['ship_date'] ?? 'N/A') . "\n";
        echo "  - Tracking: " . ($datos['tracking_number'] ?? 'N/A') . "\n\n";

        echo "👤 REMITENTE:\n";
        echo "  - Nombre: " . ($datos['shipper_name'] ?? 'N/A') . "\n";
        echo "  - Teléfono: " . ($datos['ship_phone_number'] ?? 'N/A') . "\n";
        echo "  - Dirección: " . ($datos['shipper_address'] ?? 'N/A') . "\n";
        echo "  - Colonia: " . ($datos['shipper_suburb'] ?? 'N/A') . "\n";
        echo "  - Ciudad: " . ($datos['shipper_city'] ?? 'N/A') . "\n";
        echo "  - CP: " . ($datos['shipper_zip_code'] ?? 'N/A') . "\n\n";

        echo "📍 DESTINATARIO:\n";
        echo "  - Nombre: " . ($datos['consignee_name'] ?? 'N/A') . "\n";
        echo "  - Dirección: " . ($datos['consignee_address'] ?? 'N/A') . "\n";
        echo "  - Colonia: " . ($datos['consignee_colonia'] ?? 'N/A') . "\n";
        echo "  - Ciudad: " . ($datos['consignee_city'] ?? 'N/A') . "\n";
        echo "  - Estado: " . ($datos['consignee_state'] ?? 'N/A') . "\n";
        echo "  - CP: " . ($datos['consignee_zip_code'] ?? 'N/A') . "\n";
        echo "  - País: " . ($datos['consignee_country'] ?? 'N/A') . "\n\n";

        echo "📦 INFORMACIÓN DEL ENVÍO:\n";
        echo "  - Paquetes: " . ($datos['total_packages'] ?? $datos['shipper_box_count'] ?? 'N/A') . "\n";
        echo "  - Peso: " . ($datos['total_weight'] ?? 'N/A') . " " . ($datos['weight_unit'] ?? '') . "\n";
        echo "  - Costo Flete: $" . ($datos['shipper_freight_cost'] ?? 'N/A') . "\n";
        echo "  - Valor Asegurado: $" . ($datos['shipper_insured_value'] ?? 'N/A') . "\n\n";

        if (!empty($datos['item_categories'])) {
            echo "📋 CATEGORÍAS: " . implode(', ', $datos['item_categories']) . "\n\n";
        }

        if (!empty($datos['shipment_line_items'])) {
            echo "📝 ITEMS DEL ENVÍO:\n";
            foreach ($datos['shipment_line_items'] as $index => $item) {
                echo "  " . ($index + 1) . ". " . ($item['description'] ?? 'N/A');
                if ($item['quantity']) echo " (Cant: " . $item['quantity'] . ")";
                if ($item['weight']) echo " (Peso: " . $item['weight'] . ")";
                echo "\n";
            }
            echo "\n";
        }

        echo "✅ TODOS LOS DATOS SE EXTRAJERON CORRECTAMENTE!\n";
        echo "\nAhora prueba desde Filament para guardar en la base de datos.\n";
    } else {
        echo "❌ ERROR al procesar:\n";
        echo $resultado['error'] . "\n";
    }

} catch (\Exception $e) {
    echo "❌ EXCEPCIÓN:\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== Revisa los logs en storage/logs/laravel.log para más detalles ===\n";
