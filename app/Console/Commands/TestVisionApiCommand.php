<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VisionApiService;

class TestVisionApiCommand extends Command
{
    protected $signature = 'vision:test';
    protected $description = 'Probar VisionApiService';

    public function handle()
    {
        $this->info('🧪 Probando VisionApiService...');

        $vision = app(VisionApiService::class);

        $this->info('Estado de configuración: ' . ($vision->isConfigured() ? 'CONFIGURADO' : 'NO CONFIGURADO (MODO SIMULACIÓN)'));

        // Probar con una imagen simulada
        $this->info('⏳ Procesando imagen de prueba...');
        $resultado = $vision->procesarDocumento('/ruta/imagen/simulada.jpg');

        $this->info('✅ Resultado del procesamiento:');
        $this->line("- Success: " . ($resultado['success'] ? 'true' : 'false'));
        $this->line("- Mode: " . $resultado['metadatos']['mode']);
        $this->line("- Folio generado: " . $resultado['datos']['folio']);
        $this->line("- Remitente: " . $resultado['datos']['remitente_nombre']);
        $this->line("- Destinatario: " . $resultado['datos']['destinatario_nombre']);
        $this->line("- Total: $" . $resultado['datos']['total']);
        $this->line("- Confianza: " . $resultado['confianza'] . "%");

        $this->info('🎯 Test completado exitosamente!');

        if ($resultado['metadatos']['mode'] === 'simulation') {
            $this->warn('💡 Para usar Google Cloud Vision real, configure las credenciales en el archivo .env:');
            $this->line('GOOGLE_CLOUD_KEY_FILE=/ruta/al/archivo/credenciales.json');
            $this->line('GOOGLE_CLOUD_PROJECT_ID=tu-proyecto-id');
        }

        return Command::SUCCESS;
    }
}
