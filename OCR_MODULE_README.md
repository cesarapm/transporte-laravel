# Módulo de OCR con Google Cloud Vision API

## Descripción
Este módulo permite el escaneo automático de documentos de envío utilizando Google Cloud Vision API para extraer información estructurada como datos del remitente, destinatario, costos de envío, etc.

## Características
- ✅ Procesamiento automático de imágenes con OCR
- ✅ Extracción de datos estructurados de formularios de envío
- ✅ Interfaz administrativa completa con Filament
- ✅ Validación de confianza y marcado para revisión manual
- ✅ API REST para integración externa
- ✅ Reprocesamiento de documentos
- ✅ Sistema de verificación manual

## Estructura del Módulo

### Modelos
- `DocumentoEscaneado`: Almacena documentos y datos extraídos

### Servicios  
- `VisionApiService`: Procesamiento OCR con Google Cloud Vision

### Controladores
- `DocumentoEscaneadoController`: API REST para gestión de documentos

### Recursos Filament
- `DocumentoEscaneadoResource`: Interfaz administrativa completa

## Configuración

### 1. Google Cloud Setup
```bash
# 1. Crea un proyecto en Google Cloud Console
# 2. Habilita Cloud Vision API
# 3. Crea una Service Account
# 4. Descarga las credenciales JSON
```

### 2. Variables de entorno
```env
GOOGLE_APPLICATION_CREDENTIALS=path/to/credentials.json
GOOGLE_CLOUD_PROJECT_ID=tu-project-id
```

### 3. Migraciones
```bash
php artisan migrate
```

## Uso

### Desde la Interfaz Filament
1. Ve a "Documentos Escaneados" en el panel admin
2. Haz clic en "Nuevo Documento"
3. Sube una imagen del documento
4. El sistema procesará automáticamente con OCR
5. Revisa y corrige los datos extraídos si es necesario

### Desde la API
```php
// Subir documento
POST /api/documentos/upload
Content-Type: multipart/form-data
archivo: [imagen_file]

// Ver documento
GET /api/documentos/{id}

// Reprocesar
POST /api/documentos/{id}/reprocesar
```

### Comando de prueba
```bash
php artisan ocr:test ruta/a/imagen.jpg
```

## Campos Extraídos

### Información del Documento
- Folio
- Fecha del documento

### Datos del Remitente
- Nombre
- Teléfono
- Dirección completa (calle, colonia, ciudad, estado, CP, país)

### Datos del Destinatario  
- Nombre
- Teléfono
- Dirección completa (calle, colonia, ciudad, estado, CP, país)

### Información del Envío
- Número de cajas
- Tipo de contenido
- Peso
- Valor asegurado
- Costo del flete
- Impuestos
- Total

## Estados del Documento

- **Pendiente**: Archivo subido, esperando procesamiento
- **Procesado**: OCR completado exitosamente
- **Error**: Falló el procesamiento
- **Verificado**: Revisado y confirmado manualmente

## Funciones Especiales

### Reprocesamiento Automático
- Botón "Reprocesar" en la interfaz
- Útil si el OCR inicial falló o tuvo baja confianza

### Verificación Manual
- Marca documentos como verificados después de revisión
- Badge en navegación muestra documentos pendientes de revisión

### Sistema de Confianza
- Documentos con confianza < 80% se marcan para revisión
- Indicadores visuales de estado en la tabla

## API Endpoints

```
GET    /api/documentos              # Lista todos los documentos
POST   /api/documentos/upload       # Sube nuevo documento
GET    /api/documentos/{id}         # Ver documento específico  
PUT    /api/documentos/{id}         # Actualizar documento
DELETE /api/documentos/{id}         # Eliminar documento
POST   /api/documentos/{id}/reprocesar  # Reprocesar OCR
POST   /api/documentos/{id}/verificar   # Marcar como verificado
```

## Estructura de Respuesta API

```json
{
  "success": true,
  "data": {
    "id": 1,
    "archivo_original": "documentos_escaneados/documento.jpg",
    "estado_procesamiento": "procesado",
    "folio": "ABC123",
    "remitente_nombre": "John Doe",
    "destinatario_nombre": "Jane Smith",
    "total": 150.00,
    "requiere_revision": false,
    "created_at": "2024-12-03T20:53:46.000000Z"
  }
}
```

## Troubleshooting

### Error de credenciales Google Cloud
- Verifica que el archivo de credenciales existe
- Confirma que las variables de entorno están configuradas
- Asegúrate de que la API esté habilitada

### OCR con baja confianza
- Mejora la calidad de la imagen
- Asegúrate de que el texto esté claramente visible
- Revisa manualmente y verifica el documento

### Archivos no encontrados
- Verifica que el storage/public está configurado
- Ejecuta `php artisan storage:link` si es necesario

## Próximas Mejoras

- [ ] Soporte para múltiples idiomas
- [ ] Detección automática de tipo de documento
- [ ] Machine Learning para mejorar patrones de extracción
- [ ] Exportación masiva de datos
- [ ] Integración con servicios de mensajería
