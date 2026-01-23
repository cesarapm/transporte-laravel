<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\DocumentoEscaneado;
use App\Services\VisionApiService;
use Carbon\Carbon;

class DocumentoEscaneadoController extends Controller
{
    protected $visionService;

    public function __construct(VisionApiService $visionService)
    {
        $this->visionService = $visionService;
    }

    /**
     * Listar documentos escaneados
     */
    public function index(Request $request)
    {
        $query = DocumentoEscaneado::query();

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado_procesamiento', $request->estado);
        }

        if ($request->has('requiere_revision')) {
            $query->where('requiere_revision', $request->boolean('requiere_revision'));
        }

        if ($request->has('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $documentos = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($documentos);
    }

    /**
     * Subir y procesar documento
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $archivo = $request->file('archivo');

            // Generar nombre único para el archivo
            $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
            $rutaArchivo = $archivo->storeAs('documentos_escaneados', $nombreArchivo, 'public');

            // Crear registro inicial en base de datos
            $documento = DocumentoEscaneado::create([
                'archivo_original' => $rutaArchivo,
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'tipo_mime' => $archivo->getMimeType(),
                'tamaño_archivo' => $archivo->getSize(),
                'estado_procesamiento' => 'pendiente'
            ]);

            // Procesar con Vision API
            $rutaCompleta = storage_path('app/public/' . $rutaArchivo);
            $resultado = $this->visionService->procesarDocumento($rutaCompleta);

            if ($resultado['success']) {
                // Actualizar documento con datos extraídos
                $datosExtraidos = $resultado['datos_extraidos'];

                $documento->update([
                    'folio' => $datosExtraidos['folio'],
                    'fecha_documento' => $datosExtraidos['fecha'],

                    // Remitente
                    'remitente_nombre' => $datosExtraidos['remitente']['nombre'],
                    'remitente_telefono' => $datosExtraidos['remitente']['telefono'],
                    'remitente_direccion' => $datosExtraidos['remitente']['direccion'],
                    'remitente_colonia' => $datosExtraidos['remitente']['colonia'],
                    'remitente_ciudad' => $datosExtraidos['remitente']['ciudad'],
                    'remitente_estado' => $datosExtraidos['remitente']['estado'],
                    'remitente_cp' => $datosExtraidos['remitente']['cp'],
                    'remitente_pais' => $datosExtraidos['remitente']['pais'],

                    // Destinatario
                    'destinatario_nombre' => $datosExtraidos['destinatario']['nombre'],
                    'destinatario_telefono' => $datosExtraidos['destinatario']['telefono'],
                    'destinatario_direccion' => $datosExtraidos['destinatario']['direccion'],
                    'destinatario_colonia' => $datosExtraidos['destinatario']['colonia'],
                    'destinatario_ciudad' => $datosExtraidos['destinatario']['ciudad'],
                    'destinatario_estado' => $datosExtraidos['destinatario']['estado'],
                    'destinatario_cp' => $datosExtraidos['destinatario']['cp'],
                    'destinatario_pais' => $datosExtraidos['destinatario']['pais'],

                    // Envío
                    'numero_cajas' => $datosExtraidos['envio']['numero_cajas'],
                    'tipo_contenido' => $datosExtraidos['envio']['tipo'],
                    'peso' => $datosExtraidos['envio']['peso'],
                    'valor_asegurado' => $datosExtraidos['envio']['valor_asegurado'],
                    'costo_flete' => $datosExtraidos['envio']['costo_flete'],
                    'impuestos' => $datosExtraidos['envio']['impuestos'],
                    'seguro_extra' => $datosExtraidos['envio']['seguro_extra'],
                    'total' => $datosExtraidos['envio']['total'],

                    // Metadatos del procesamiento
                    'texto_raw' => ['texto_completo' => $resultado['texto_completo']],
                    'confianza_ocr' => ['confianza_promedio' => $resultado['confianza']],
                    'metadatos_vision' => $resultado['metadatos_vision'],
                    'estado_procesamiento' => 'procesado',
                    'requiere_revision' => $resultado['confianza'] < 80 // Si confianza es baja
                ]);

                Log::info('Documento procesado exitosamente', [
                    'documento_id' => $documento->id,
                    'confianza' => $resultado['confianza']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Documento procesado exitosamente',
                    'documento' => $documento->fresh(),
                    'confianza' => $resultado['confianza']
                ]);

            } else {
                // Error en el procesamiento
                $documento->update([
                    'estado_procesamiento' => 'error',
                    'errores_procesamiento' => $resultado['error'],
                    'requiere_revision' => true
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error procesando el documento: ' . $resultado['error'],
                    'documento' => $documento->fresh()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error subiendo documento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver documento específico
     */
    public function show($id)
    {
        $documento = DocumentoEscaneado::find($id);

        if (!$documento) {
            return response()->json([
                'success' => false,
                'message' => 'Documento no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'documento' => $documento
        ]);
    }

    /**
     * Actualizar documento (para correcciones manuales)
     */
    public function update(Request $request, $id)
    {
        $documento = DocumentoEscaneado::find($id);

        if (!$documento) {
            return response()->json([
                'success' => false,
                'message' => 'Documento no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'folio' => 'nullable|string|max:50',
            'fecha_documento' => 'nullable|date',
            'remitente_nombre' => 'nullable|string|max:255',
            'destinatario_nombre' => 'nullable|string|max:255',
            'estado_procesamiento' => 'in:pendiente,procesado,error,verificado',
            'notas_revision' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $documento->update($request->only([
            'folio', 'fecha_documento', 'remitente_nombre', 'remitente_telefono',
            'remitente_direccion', 'remitente_colonia', 'remitente_ciudad',
            'remitente_estado', 'remitente_cp', 'remitente_pais',
            'destinatario_nombre', 'destinatario_telefono', 'destinatario_direccion',
            'destinatario_colonia', 'destinatario_ciudad', 'destinatario_estado',
            'destinatario_cp', 'destinatario_pais', 'numero_cajas', 'tipo_contenido',
            'peso', 'valor_asegurado', 'valor_declarado', 'costo_flete',
            'impuestos', 'seguro_extra', 'total', 'estado_procesamiento',
            'notas_revision'
        ]));

        // Si se marca como verificado, quitar flag de revisión
        if ($request->estado_procesamiento === 'verificado') {
            $documento->update(['requiere_revision' => false]);
        }

        Log::info('Documento actualizado', ['documento_id' => $documento->id]);

        return response()->json([
            'success' => true,
            'message' => 'Documento actualizado exitosamente',
            'documento' => $documento->fresh()
        ]);
    }

    /**
     * Eliminar documento
     */
    public function destroy($id)
    {
        $documento = DocumentoEscaneado::find($id);

        if (!$documento) {
            return response()->json([
                'success' => false,
                'message' => 'Documento no encontrado'
            ], 404);
        }

        // Eliminar archivo físico
        if (Storage::disk('public')->exists($documento->archivo_original)) {
            Storage::disk('public')->delete($documento->archivo_original);
        }

        // Eliminar registro
        $documento->delete();

        Log::info('Documento eliminado', ['documento_id' => $id]);

        return response()->json([
            'success' => true,
            'message' => 'Documento eliminado exitosamente'
        ]);
    }

    /**
     * Reprocesar documento con Vision API
     */
    public function reprocesar($id)
    {
        $documento = DocumentoEscaneado::find($id);

        if (!$documento) {
            return response()->json([
                'success' => false,
                'message' => 'Documento no encontrado'
            ], 404);
        }

        try {
            $rutaCompleta = storage_path('app/public/' . $documento->archivo_original);

            if (!file_exists($rutaCompleta)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado en el servidor'
                ], 404);
            }

            // Marcar como procesando
            $documento->update(['estado_procesamiento' => 'pendiente']);

            // Reprocesar
            $resultado = $this->visionService->procesarDocumento($rutaCompleta);

            if ($resultado['success']) {
                $datosExtraidos = $resultado['datos_extraidos'];

                $documento->update([
                    'folio' => $datosExtraidos['folio'],
                    'fecha_documento' => $datosExtraidos['fecha'],
                    // ... actualizar todos los campos como en store()
                    'estado_procesamiento' => 'procesado',
                    'requiere_revision' => $resultado['confianza'] < 80
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Documento reprocesado exitosamente',
                    'documento' => $documento->fresh()
                ]);
            } else {
                $documento->update([
                    'estado_procesamiento' => 'error',
                    'errores_procesamiento' => $resultado['error']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error reprocesando: ' . $resultado['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error reprocesando documento', [
                'documento_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }
}
