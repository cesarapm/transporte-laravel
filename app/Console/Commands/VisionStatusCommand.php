<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VisionApiService;

class VisionStatusCommand extends Command
{
    protected $signature = 'vision:status';
    protected $description = 'Verificar el estado de la configuración de Google Cloud Vision API';

    public function handle()
    {
        $this->info('🔍 Verificando configuración de Google Cloud Vision API...');
        $this->newLine();

        // Verificar variables de entorno
        $apiKey = config('services.google_cloud.api_key');
        $projectId = config('services.google_cloud.project_id');

        $this->info('📋 CONFIGURACIÓN:');
        $this->line('  API Key: ' . ($apiKey ? '✅ Configurada (' . substr($apiKey, 0, 10) . '...)' : '❌ No configurada'));
        $this->line('  Project ID: ' . ($projectId ? '✅ ' . $projectId : '❌ No configurado'));
        $this->newLine();

        if (!$apiKey) {
            $this->error('❌ PROBLEMA: API Key no configurada en .env');
            $this->line('   Agrega: GOOGLE_CLOUD_API_KEY=tu_api_key');
            return Command::FAILURE;
        }

        // Probar conectividad básica
        $this->info('🌐 CONECTIVIDAD:');
        $testUrl = 'https://vision.googleapis.com/v1/images:annotate?key=' . $apiKey;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $testUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'requests' => [
                    [
                        'image' => ['content' => base64_encode('test')],
                        'features' => [['type' => 'TEXT_DETECTION']]
                    ]
                ]
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        switch ($httpCode) {
            case 200:
                $this->line('  Conexión: ✅ API accesible');
                break;
            case 400:
                $this->line('  Conexión: ⚠️  API accesible (error esperado por imagen inválida)');
                break;
            case 403:
                $decoded = json_decode($response, true);
                if (isset($decoded['error']['details'])) {
                    foreach ($decoded['error']['details'] as $detail) {
                        if ($detail['reason'] === 'BILLING_DISABLED') {
                            $this->line('  Conexión: ❌ Facturación no habilitada');
                            $this->newLine();
                            $this->error('🔥 PROBLEMA PRINCIPAL: Facturación deshabilitada');
                            $this->line('   1. Ve a: https://console.cloud.google.com/billing');
                            $this->line('   2. Vincula tu proyecto a una cuenta de facturación');
                            $this->line('   3. Google Cloud Vision tiene 1,000 solicitudes gratis/mes');
                            return Command::FAILURE;
                        }
                    }
                }
                $this->line('  Conexión: ❌ Acceso denegado (verificar API Key)');
                break;
            default:
                $this->line("  Conexión: ❌ Error HTTP $httpCode");
        }

        $this->newLine();

        // Probar VisionApiService
        $this->info('🤖 SERVICIO:');
        try {
            $visionService = new VisionApiService();

            if ($visionService->isConfigured()) {
                $this->line('  VisionApiService: ✅ Configurado y listo');
                $this->line('  Modo: 🌍 Google Cloud Vision API (REST)');
            } else {
                $this->line('  VisionApiService: ⚠️  En modo simulación');
                $this->line('  Modo: 🎭 Simulación (datos de prueba)');
            }
        } catch (\Exception $e) {
            $this->line('  VisionApiService: ❌ Error: ' . $e->getMessage());
        }

        $this->newLine();

        if ($httpCode === 403) {
            $this->warn('⚠️  RECOMENDACIÓN: Habilita la facturación para usar OCR real');
            $this->line('   Mientras tanto, el sistema funciona en modo simulación');
        } else {
            $this->info('✅ Todo configurado correctamente!');
        }

        return Command::SUCCESS;
    }
}
