<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VisionApiService;

class SaveOcrTestCommand extends Command
{
    protected $signature = 'save:test {imagen?}';
    protected $description = 'Procesar imagen con OCR y guardar en DocumentoEscaneado';

    public function handle()
    {
        $imagen = $this->argument('imagen') ?? '01KBK4YVXE5KC5WWHGWQ7PP6MF.jpg';
        $rutaImagen = "C:\\Users\\LENOVO USER\\Desktop\\Nuevos Proyectos\\Cajas\\Apirest\\storage\\app\\public\\documentos_escaneados\\{$imagen}";

        if (!file_exists($rutaImagen)) {
            $this->error("❌ Imagen no encontrada: {$rutaImagen}");
            return Command::FAILURE;
        }

        $this->info("🔍 Procesando y guardando: {$imagen}");

        try {
            $visionService = new VisionApiService();
            $resultado = $visionService->procesarDocumento($rutaImagen);

            if ($resultado['success']) {
                $this->info("✅ OCR exitoso!");
                $this->line("   Folio: " . ($resultado['datos']['folio'] ?: 'N/A'));
                $this->line("   Remitente: " . ($resultado['datos']['remitente_nombre'] ?: 'N/A'));
                $this->line("   Destinatario: " . ($resultado['datos']['destinatario_nombre'] ?: 'N/A'));

                // Guardar en base de datos
                $documento = $visionService->guardarDocumento($rutaImagen, $resultado);

                $this->newLine();
                $this->info("💾 ¡Guardado en base de datos!");
                $this->line("   ID: {$documento->id}");
                $this->line("   Estado: {$documento->estado_procesamiento}");
                $this->line("   Requiere revisión: " . ($documento->requiere_revision ? 'Sí' : 'No'));

                $this->newLine();
                $this->info("🌟 ¡Disponible en Filament Admin!");
                $this->line("   Accede a: http://127.0.0.1:8000/admin/documento-escaneados");

            } else {
                $this->error("❌ Error OCR: " . $resultado['error']);
            }

        } catch (\Exception $e) {
            $this->error("💥 Error: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
