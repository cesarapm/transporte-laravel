<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VisionApiService;

class TestDestinatarioCommand extends Command
{
    protected $signature = 'test:destinatario';
    protected $description = 'Probar específicamente la detección del destinatario';

    public function handle()
    {
        $rutaImagen = "C:\\Users\\LENOVO USER\\Desktop\\Nuevos Proyectos\\Cajas\\Apirest\\storage\\app\\public\\documentos_escaneados\\01KBK4YVXE5KC5WWHGWQ7PP6MF.jpg";

        if (!file_exists($rutaImagen)) {
            $this->error("❌ Imagen no encontrada");
            return Command::FAILURE;
        }

        $this->info("🔍 Probando detección del destinatario...");

        try {
            $visionService = new VisionApiService();
            $resultado = $visionService->procesarDocumento($rutaImagen);

            if ($resultado['success']) {
                $datos = $resultado['datos'];

                $this->newLine();
                $this->info("📋 DATOS DEL DESTINATARIO:");
                $this->line("  Nombre: " . ($datos['destinatario_nombre'] ?: '❌ NO DETECTADO'));
                $this->line("  Teléfono: " . ($datos['destinatario_telefono'] ?: '❌ NO DETECTADO'));
                $this->line("  Dirección: " . ($datos['destinatario_direccion'] ?: '❌ NO DETECTADO'));
                $this->line("  Colonia: " . ($datos['destinatario_colonia'] ?: '❌ NO DETECTADO'));
                $this->line("  Ciudad: " . ($datos['destinatario_ciudad'] ?: '❌ NO DETECTADO'));

                $this->newLine();
                $this->info("📄 TEXTO COMPLETO (primeras 500 chars):");
                $this->line(substr($resultado['texto_completo'], 0, 500) . "...");

                // Buscar manualmente "CELIA ANDREA NIÑO" en el texto
                if (strpos($resultado['texto_completo'], 'CELIA ANDREA') !== false) {
                    $this->newLine();

                    $this->warn("⚠️ 'CELIA ANDREA' SÍ está en el texto, pero no se detectó correctamente");
                }

            } else {
                $this->error("❌ Error: " . $resultado['error']);
            }

        } catch (\Exception $e) {
            $this->error("💥 Error: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
