<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VisionApiService;

class QuickTestCommand extends Command
{
    protected $signature = 'quick:test';
    protected $description = 'Prueba rápida del OCR con imagen conocida';

    public function handle()
    {
        $rutaImagen = 'C:\Users\LENOVO USER\Desktop\Nuevos Proyectos\Cajas\Apirest\storage\app\public\documentos_escaneados\01KBKXDDRR5A7TWN1TEBGRC2PD.jpg';

        if (!file_exists($rutaImagen)) {
            $this->error("Imagen no encontrada en: {$rutaImagen}");
            return Command::FAILURE;
        }

        $this->info("🔍 Procesando imagen con patrones mejorados...");

        try {
            $visionService = new VisionApiService();
            $resultado = $visionService->procesarDocumento($rutaImagen);

            if ($resultado['success']) {
                $this->info("✅ ¡Éxito!");
                $this->newLine();

                $datos = $resultado['datos'];

                $this->line("📄 INFORMACIÓN DETECTADA:");
                $this->line("  Folio: " . ($datos['folio'] ?: '❌'));
                $this->line("  Fecha: " . ($datos['fecha_documento'] ?: '❌'));
                $this->newLine();

                $this->line("👤 REMITENTE:");
                $this->line("  Nombre: " . ($datos['remitente_nombre'] ?: '❌'));
                $this->line("  Teléfono: " . ($datos['remitente_telefono'] ?: '❌'));
                $this->line("  Dirección: " . ($datos['remitente_direccion'] ?: '❌'));
                $this->line("  C.P.: " . ($datos['remitente_cp'] ?: '❌'));
                $this->newLine();

                $this->line("🎯 DESTINATARIO:");
                $this->line("  Nombre: " . ($datos['destinatario_nombre'] ?: '❌'));
                $this->line("  Teléfono: " . ($datos['destinatario_telefono'] ?: '❌'));
                $this->line("  Dirección: " . ($datos['destinatario_direccion'] ?: '❌'));
                $this->line("  Colonia: " . ($datos['destinatario_colonia'] ?: '❌'));
                $this->line("  Ciudad: " . ($datos['destinatario_ciudad'] ?: '❌'));
                $this->line("  C.P.: " . ($datos['destinatario_cp'] ?: '❌'));
                $this->newLine();

                $this->line("📦 ENVÍO:");
                $this->line("  Cajas: " . ($datos['numero_cajas'] ?: '❌'));
                $this->line("  Tipo: " . ($datos['tipo_contenido'] ?: '❌'));
                $this->line("  Peso: " . ($datos['peso'] ?: '❌'));
                $this->line("  Valor: $" . ($datos['valor_asegurado'] ?: '❌'));
                $this->line("  Flete: $" . ($datos['costo_flete'] ?: '❌'));
                $this->line("  Total: $" . ($datos['total'] ?: '❌'));

                $this->newLine();
                $this->info("🤖 Modo: " . ($resultado['metadatos']['mode'] ?? 'desconocido'));
                $this->info("📊 Confianza: {$resultado['confianza']}%");

            } else {
                $this->error("❌ Error: " . $resultado['error']);
            }

        } catch (\Exception $e) {
            $this->error("💥 Excepción: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
