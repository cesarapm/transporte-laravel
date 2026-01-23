<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;
use Mindee\ClientV2;
use Mindee\Input\InferenceParameters;
use Mindee\Input\PathInput;

class MindeeApiService
{
    protected $apiKey;
    protected $isConfigured;
    protected $client;
    protected $modelId;

    public function __construct()
    {
        try {
            // Leer credenciales desde .env
            $this->apiKey = config('services.mindee.api_key') ?? env('MINDEE_API_KEY');
            $this->modelId = config('services.mindee.model_id') ?? env('MINDEE_MODEL_ID');

            if (!$this->apiKey || !$this->modelId) {
                $this->isConfigured = false;
                Log::warning('Mindee API no configurada. Verifica API Key y Model ID en .env');
                return;
            }

            // Inicializar cliente V2 de Mindee
            $this->client = new ClientV2($this->apiKey);
            $this->isConfigured = true;
            // Log::info('Mindee API configurada correctamente con SDK V2.');

        } catch (\Exception $e) {
            $this->isConfigured = false;
            // Log::warning('Mindee API no disponible: ' . $e->getMessage());
        }
    }

    /**
     * Procesar documento de envío con Mindee
     */
    public function procesarDocumento($rutaArchivo)
    {
        if (!$this->isConfigured) {
            return $this->simularProcesamiento();
        }

        try {
            return $this->procesarConMindeeAPI($rutaArchivo);
        } catch (\Exception $e) {
            Log::error('Error en Mindee API: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Procesar múltiples documentos
     */
    public function procesarMultiplesDocumentos(array $rutasArchivos)
    {
        $resultados = [];

        foreach ($rutasArchivos as $index => $rutaArchivo) {
            try {
                $resultado = $this->procesarDocumento($rutaArchivo);
                $resultados[] = [
                    'archivo' => basename($rutaArchivo),
                    'index' => $index,
                    'resultado' => $resultado
                ];
            } catch (\Exception $e) {
                Log::error("Error procesando archivo {$rutaArchivo}: " . $e->getMessage());
                $resultados[] = [
                    'archivo' => basename($rutaArchivo),
                    'index' => $index,
                    'resultado' => [
                        'success' => false,
                        'error' => $e->getMessage()
                    ]
                ];
            }
        }

        return $resultados;
    }

    protected function procesarConMindeeAPI($rutaArchivo)
    {
        Log::info('Procesando documento con Mindee API (SDK V2)', ['archivo' => $rutaArchivo]);

        try {
            // Configurar parámetros de inferencia
            $inferenceParams = new InferenceParameters(
                $this->modelId,
                rag: null,
                rawText: null,  // Solo disponible en plan pagado
                polygon: null,  // Solo disponible en plan pagado
                confidence: null  // Solo disponible en plan pagado
            );

            // Cargar el archivo
            $inputSource = new PathInput($rutaArchivo);

            // Enviar para procesamiento con polling
            $response = $this->client->enqueueAndGetInference(
                $inputSource,
                $inferenceParams
            );

            Log::info('Respuesta recibida de Mindee');
            Log::info('Inferencia', ['summary' => strval($response->inference)]);

            // Procesar y estructurar la respuesta
            return $this->estructurarRespuesta($response);

        } catch (\Exception $e) {
            Log::error('Error en Mindee SDK', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error en Mindee API: ' . $e->getMessage());
        }
    }

    protected function estructurarRespuesta($response)
    {
        try {
            // Acceder a los campos del resultado usando la estructura correcta de V2
            $inference = $response->inference ?? null;
            if (!$inference) {
                throw new Exception('No se recibió inference en la respuesta');
            }

            $result = $inference->result ?? null;
            if (!$result) {
                throw new Exception('No se recibió result en la respuesta');
            }

            $fields = $result->fields ?? [];

            // LOGS COMENTADOS PARA AHORRAR MEMORIA
            // Log::info('===== DUMP COMPLETO DE LA RESPUESTA DE MINDEE =====');
            // Log::info('Result completo', ['result' => json_encode($result, JSON_PRETTY_PRINT)]);
            // Log::info('Fields tipo', ['tipo' => gettype($fields)]);

            // Convertir a JSON y luego a array para ver la estructura completa
            $fieldsJson = json_encode($fields);
            // Log::info('Fields JSON completo', ['json' => $fieldsJson]);
            $fieldsArray = json_decode($fieldsJson, true);
            // Log::info('Fields como array', ['array' => print_r($fieldsArray, true)]);

            // Log completo para debugging
            // Log::info('Estructura de fields', [
            //     'tipo' => gettype($fields),
            //     'es_objeto' => is_object($fields),
            //     'fieldsArray_count' => count($fieldsArray ?? [])
            // ]);

            // Log cada campo individualmente
            // if (is_array($fieldsArray)) {
            //     foreach ($fieldsArray as $key => $value) {
            //         Log::info("Campo individual: {$key}", [
            //             'tipo' => gettype($value),
            //             'tiene_value' => isset($value['value']),
            //             'value' => isset($value['value']) ? $value['value'] : 'N/A'
            //         ]);
            //     }
            // }
            $textoCompleto = '';
            if (isset($result->rawText)) {
                $textoCompleto = $result->rawText;
            }

            // Obtener texto completo
            $textoCompleto = '';
            if (isset($document->ocr) && method_exists($document->ocr, '__toString')) {
                $textoCompleto = (string) $document->ocr;
            }

            // Estructurar datos procesados usando el array de fields
            $datosEstructurados = [
                // Información del transportista
                'carrier_name' => $this->getFieldValueFromObject($fieldsArray, 'carrier_name'),
                'carrier_address' => $this->getFieldValueFromObject($fieldsArray, 'carrier_address'),
                'manifest_number' => $this->getFieldValueFromObject($fieldsArray, 'manifest_number'),
                'folio_invoice_number' => $this->getFieldValueFromObject($fieldsArray, 'folio_invoice_number'),
                'ship_date' => $this->getFieldValueFromObject($fieldsArray, 'ship_date'),

                // Remitente
                'shipper_name' => $this->getFieldValueFromObject($fieldsArray, 'shipper_name'),
                'shipper_address' => $this->getFieldValueFromObject($fieldsArray, 'shipper_address'),
                'shipper_city' => $this->getFieldValueFromObject($fieldsArray, 'shipper_city'),
                'shipper_suburb' => $this->getFieldValueFromObject($fieldsArray, 'shipper_suburb'),
                'shipper_zip_code' => $this->getFieldValueFromObject($fieldsArray, 'shipper_zip_code'),
                'shipper_state' => $this->getFieldValueFromObject($fieldsArray, 'shipper_state'),
                'shipper_country' => $this->getFieldValueFromObject($fieldsArray, 'shipper_country'), //falto
                'ship_phone_number' => $this->getFieldValueFromObject($fieldsArray, 'ship_phone_number'),

                // Destinatario
                'consignee_name' => $this->getFieldValueFromObject($fieldsArray, 'consignee_name'),
                'consignee_address' => $this->getFieldValueFromObject($fieldsArray, 'consignee_address'),
                'consignee_colonia' => $this->getFieldValueFromObject($fieldsArray, 'consignee_colonia'),
                'consignee_city' => $this->getFieldValueFromObject($fieldsArray, 'consignee_city'),
                'consignee_state' => $this->getFieldValueFromObject($fieldsArray, 'consignee_state'),
                'consignee_zip_code' => $this->getFieldValueFromObject($fieldsArray, 'consignee_zip_code'),
                'consignee_country' => $this->getFieldValueFromObject($fieldsArray, 'consignee_country'),
                'consignee_phone_number' => $this->getFieldValueFromObject($fieldsArray, 'consignee_phone_number'),

                // Información del envío
                'total_packages' => $this->getFieldValueFromObject($fieldsArray, 'total_packages'),
                'shipper_box_count' => $this->getFieldValueFromObject($fieldsArray, 'shipper_box_count'),
                'total_weight' => $this->getFieldValueFromObject($fieldsArray, 'total_weight'),
                'weight_unit' => $this->getFieldValueFromObject($fieldsArray, 'weight_unit'),

                // Costos
                'shipper_freight_cost' => $this->getFieldValueFromObject($fieldsArray, 'shipper_freight_cost'),
                'shipper_insured_value' => $this->getFieldValueFromObject($fieldsArray, 'shipper_insured_value'),

                // Items
                'item_categories' => $this->getItemCategoriesFromObject($fieldsArray),
                'shipment_line_items' => $this->getShipmentLineItemsFromObject($fieldsArray),

                // Tracking
                'tracking_number' => $this->getFieldValueFromObject($fieldsArray, 'tracking_number'),

                // Firma y otros
                'signature' => $this->getFieldValueFromObject($fieldsArray, 'signature'),
                'agent_signature' => $this->getFieldValueFromObject($fieldsArray, 'agent_signature'),
            ];

            // Calcular confianza
            $confianza = $this->calcularConfianzaPromedioFromObject($fieldsArray);

            Log::info('===== DATOS ESTRUCTURADOS FINALES =====', [
                'datos' => $datosEstructurados,
                'confianza' => $confianza
            ]);

            return [
                'success' => true,
                'datos' => $datosEstructurados,
                'texto_completo' => trim($textoCompleto),
                'respuesta_completa' => json_decode(json_encode($response), true), // Convertir a array
                'confianza' => $confianza
            ];

        } catch (\Exception $e) {
            Log::error('Error estructurando respuesta de Mindee: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function getFieldValueFromObject($fields, $fieldName)
    {
        if (isset($fields[$fieldName])) {
            $field = $fields[$fieldName];

            // Si es un array (resultado de json_decode), buscar 'value'
            if (is_array($field)) {
                return $field['value'] ?? null;
            }

            // Si es un objeto con propiedad value
            if (is_object($field) && property_exists($field, 'value')) {
                return $field->value;
            }

            // Si es un string o número directamente
            if (is_string($field) || is_numeric($field)) {
                return $field;
            }
        }

        return null;
    }

    protected function getItemCategoriesFromObject($fields)
    {
        if (isset($fields['item_categories'])) {
            $field = $fields['item_categories'];

            // Si es un array (json_decode result)
            if (is_array($field)) {
                if (isset($field['items']) && is_array($field['items'])) {
                    return array_map(function($item) {
                        return is_array($item) ? ($item['value'] ?? null) : (is_object($item) && property_exists($item, 'value') ? $item->value : $item);
                    }, $field['items']);
                } elseif (isset($field['values']) && is_array($field['values'])) {
                    return array_map(function($item) {
                        return is_array($item) ? ($item['value'] ?? null) : (is_object($item) && property_exists($item, 'value') ? $item->value : $item);
                    }, $field['values']);
                }
            }

            // Si es un objeto
            if (is_object($field)) {
                if (property_exists($field, 'items') && is_array($field->items)) {
                    return array_map(function($item) {
                        if (is_object($item) && property_exists($item, 'value')) {
                            return $item->value;
                        }
                        return $item;
                    }, $field->items);
                } elseif (property_exists($field, 'values') && is_array($field->values)) {
                    return array_map(function($item) {
                        if (is_object($item) && property_exists($item, 'value')) {
                            return $item->value;
                        }
                        return $item;
                    }, $field->values);
                }
            }
        }

        return [];
    }

    protected function getShipmentLineItemsFromObject($fields)
    {
        if (isset($fields['shipment_line_items'])) {
            $field = $fields['shipment_line_items'];

            // Si es un array (json_decode result)
            if (is_array($field) && isset($field['items']) && is_array($field['items'])) {
                $items = [];
                foreach ($field['items'] as $item) {
                    if (is_array($item) && isset($item['fields'])) {
                        $itemFields = $item['fields'];
                        $items[] = [
                            'description' => isset($itemFields['item_description']['value'])
                                ? $itemFields['item_description']['value']
                                : null,
                            'quantity' => isset($itemFields['quantity']['value'])
                                ? $itemFields['quantity']['value']
                                : null,
                            'weight' => isset($itemFields['unit_weight']['value'])
                                ? $itemFields['unit_weight']['value']
                                : null,
                            'harmonized_code' => isset($itemFields['harmonized_code']['value'])
                                ? $itemFields['harmonized_code']['value']
                                : null,
                        ];
                    }
                }
                return $items;
            }

            // Si tiene items como objeto
            if (is_object($field) && property_exists($field, 'items') && is_array($field->items)) {
                $items = [];
                foreach ($field->items as $item) {
                    if (is_object($item) && property_exists($item, 'fields')) {
                        $itemFields = $item->fields;
                        $items[] = [
                            'description' => isset($itemFields->item_description) && property_exists($itemFields->item_description, 'value')
                                ? $itemFields->item_description->value
                                : null,
                            'quantity' => isset($itemFields->quantity) && property_exists($itemFields->quantity, 'value')
                                ? $itemFields->quantity->value
                                : null,
                            'weight' => isset($itemFields->unit_weight) && property_exists($itemFields->unit_weight, 'value')
                                ? $itemFields->unit_weight->value
                                : null,
                            'harmonized_code' => isset($itemFields->harmonized_code) && property_exists($itemFields->harmonized_code, 'value')
                                ? $itemFields->harmonized_code->value
                                : null,
                        ];
                    }
                }
                return $items;
            }

            // Si tiene values
            if (is_object($field) && property_exists($field, 'values') && is_array($field->values)) {
                $items = [];
                foreach ($field->values as $item) {
                    if (is_object($item)) {
                        $items[] = [
                            'description' => $item->item_description ?? null,
                            'quantity' => $item->quantity ?? null,
                            'weight' => $item->unit_weight ?? null,
                            'harmonized_code' => $item->harmonized_code ?? null,
                        ];
                    }
                }
                return $items;
            }
        }

        return [];
    }

    protected function calcularConfianzaPromedioFromObject($fields)
    {
        $confidences = [];

        foreach ($fields as $field) {
            if (is_object($field)) {
                try {
                    // Intentar acceder a confidence de manera segura
                    if (isset($field->confidence) && $field->confidence !== null) {
                        $confidences[] = $field->confidence;
                    }
                } catch (\Error $e) {
                    // Ignorar si confidence no está inicializado
                    continue;
                }
            }
        }

        // Si no hay confidencias disponibles, retornar 0.95 como valor por defecto
        return !empty($confidences) ? array_sum($confidences) / count($confidences) : 0.95;
    }

    protected function simularProcesamiento()
    {
        return [
            'success' => false,
            'error' => 'API de Mindee no configurada',
            'datos' => null,
            'texto_completo' => '',
            'confianza' => 0
        ];
    }

    /**
     * Verificar estado de la API
     */
    public function verificarEstado()
    {
        return [
            'configurado' => $this->isConfigured,
            'api_key_presente' => !empty($this->apiKey),
        ];
    }
}
