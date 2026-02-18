<?php

echo "=== Test de Conectividad TrackingMore ===\n\n";

// Test 1: cURL habilitado
echo "1. cURL habilitado: ";
if (function_exists('curl_version')) {
    echo "✅ SI\n";
    $v = curl_version();
    echo "   Versión: {$v['version']}\n";
    echo "   SSL: {$v['ssl_version']}\n";
} else {
    echo "❌ NO\n";
    die("ERROR: cURL no está habilitado en PHP\n");
}

echo "\n2. Probando conexión directa con cURL:\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.trackingmore.com/v4/carriers");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Temporalmente desactivar verificación SSL
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errno = curl_errno($ch);

curl_close($ch);

if ($errno) {
    echo "   ❌ Error cURL #{$errno}: {$error}\n";
} else {
    echo "   ✅ Conexión exitosa\n";
    echo "   HTTP Code: {$httpCode}\n";
    echo "   Respuesta: " . substr($response, 0, 100) . "...\n";
}

echo "\n3. Probando con file_get_contents:\n";

$context = stream_context_create([
    'http' => [
        'timeout' => 30,
        'ignore_errors' => true,
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
]);

$result = @file_get_contents("https://api.trackingmore.com/v4/carriers", false, $context);

if ($result === false) {
    $error = error_get_last();
    echo "   ❌ Error: " . ($error['message'] ?? 'Desconocido') . "\n";
} else {
    echo "   ✅ Conexión exitosa\n";
    echo "   Respuesta: " . substr($result, 0, 100) . "...\n";
}

echo "\n=== Fin del Test ===\n";
