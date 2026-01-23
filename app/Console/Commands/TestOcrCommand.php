<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VisionApiService;

class TestOcrCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ocr:test {imagen? : Nombre de la imagen para procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el servicio de OCR con Google Cloud Vision API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $imagen = $this->argument('imagen');

        if (!$imagen) {
            // Mostrar imágenes disponibles
            $archivos = \Illuminate\Support\Facades\Storage::files('public/documentos_escaneados');

            if (empty($archivos)) {
                $this->error('No hay imágenes en storage/app/public/documentos_escaneados');
                return Command::FAILURE;
            }

            $this->info('📁 Imágenes disponibles:');
            foreach ($archivos as $i => $archivo) {
                $nombre = basename($archivo);
                $this->line("  " . ($i + 1) . ". {$nombre}");
            }

            $seleccion = $this->ask('Selecciona una imagen (número)');
            $index = (int)$seleccion - 1;

            if (!isset($archivos[$index])) {
                $this->error('Selección inválida');
                return Command::FAILURE;
            }

            $imagen = basename($archivos[$index]);
        }

        $rutaImagen = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'documentos_escaneados' . DIRECTORY_SEPARATOR . $imagen);

        if (!file_exists($rutaImagen)) {
            $this->error("La imagen no existe en la ruta: {$rutaImagen}");
            return Command::FAILURE;
        }

        $this->info("📸 Procesando imagen: {$imagen}");
        $this->newLine();

        try {
            $visionService = app(VisionApiService::class);
            $resultado = $visionService->procesarDocumento($rutaImagen);

            if ($resultado['success']) {
                $this->info("✅ Procesamiento exitoso!");
                $this->info("🔍 Confianza general: {$resultado['confianza']}%");
                $this->info("🤖 Modo: " . ($resultado['metadatos']['mode'] ?? 'desconocido'));
                $this->newLine();

                $this->info("📋 Datos extraídos:");
                $this->table(
                    ['Campo', 'Valor'],
                    collect($resultado['datos'])->map(function ($valor, $campo) {
                        return [$campo, $valor ?: 'No detectado'];
                    })->toArray()
                );

                $this->newLine();
                $this->info("📄 Texto completo extraído:");
                $this->line($resultado['texto_completo']);

                if ($resultado['confianza'] < 80) {
                    $this->warn("⚠️  La confianza es baja, se recomienda revisión manual.");
                }

                if (isset($resultado['metadatos']['mode']) && $resultado['metadatos']['mode'] === 'google_vision_rest_api') {
                    $this->info("🌟 ¡API de Google Cloud Vision funcionando correctamente!");
                }

            } else {
                $this->error("❌ Error en el procesamiento:");
                $this->error($resultado['error']);
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("❌ Excepción durante el procesamiento:");
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
