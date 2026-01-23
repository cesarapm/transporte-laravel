# Módulo de Escaneo de Guías con Mindee

## 📋 Descripción

Este módulo permite escanear guías de envío automáticamente usando la API de Mindee. Extrae información como remitente, destinatario, costos, items y más.

## 🚀 Instalación

### 1. Ejecutar la Migración

```bash
php artisan migrate
```

### 2. Configurar API Key de Mindee

Agregar en tu archivo `.env`:

```env
MINDEE_API_KEY=tu_api_key_aqui
```

Para obtener tu API Key:
1. Ve a https://app.mindee.com/
2. Crea una cuenta o inicia sesión
3. Ve a "API Keys" en el menú
4. Copia tu API Key

### 3. Configurar Almacenamiento

Asegúrate de tener el enlace simbólico de storage:

```bash
php artisan storage:link
```

## 📊 Características

### ✨ Funcionalidades Principales

- **Escaneo Individual**: Sube una guía y escanéala con un clic
- **Escaneo Múltiple**: Sube varias guías a la vez y escanéalas en lote
- **Extracción Automática**: Detecta automáticamente todos los campos del documento
- **Validación de Confianza**: Marca guías con baja confianza para revisión manual
- **Gestión Completa**: Crea, edita, visualiza y elimina guías
- **Estadísticas en Tiempo Real**: Dashboard con métricas de procesamiento

### 📦 Campos Extraídos

#### Transportista
- Nombre del transportista
- Dirección
- Número de manifiesto
- Número de factura/folio
- Fecha de envío
- Número de rastreo

#### Remitente
- Nombre
- Dirección
- Ciudad
- Colonia/Suburbio
- Código postal
- Teléfono

#### Destinatario
- Nombre
- Dirección
- Colonia
- Ciudad
- Estado
- Código postal
- País

#### Envío
- Total de paquetes
- Número de cajas
- Peso total y unidad
- Costo de flete
- Valor asegurado
- Categorías de items
- Detalle de items

## 🎯 Uso

### Escanear una Guía Individual

1. Ve a **Escaneo de Documentos** > **Guías Mindee**
2. Haz clic en **Nueva Guía**
3. Sube la imagen de la guía
4. Guarda el registro
5. Haz clic en el botón **Escanear** en la tabla
6. ¡Listo! Los datos se extraerán automáticamente

### Subir Múltiples Guías

1. En la lista de guías, haz clic en **Subir Múltiples**
2. Selecciona varias imágenes (hasta 20)
3. Haz clic en **Guardar**
4. Selecciona las guías creadas
5. Usa la acción masiva **Escanear Seleccionados**

### Re-escanear una Guía

1. Abre la guía en modo edición
2. Haz clic en **Re-escanear** en la parte superior
3. Confirma la acción
4. Los datos se actualizarán con nueva información

## 📊 Estados de Procesamiento

- **Pendiente** 🕐: Guía subida pero no escaneada
- **Procesado** ✅: Escaneada exitosamente
- **Error** ❌: Error durante el escaneo
- **Verificado** 🛡️: Revisada y aprobada manualmente

## ⚠️ Revisión Manual

Las guías se marcan para revisión manual cuando:
- La confianza promedio es menor a 85%
- Hay errores en el escaneo
- El usuario marca la casilla manualmente

## 🔧 Configuración Avanzada

### Cambiar el Endpoint de Mindee

Edita el archivo `app/Services/MindeeApiService.php`:

```php
protected $endpoint = 'mindee/bill_of_lading/v1/predict';
```

Puedes usar otros endpoints de Mindee según tu API.

### Ajustar el Umbral de Confianza

En `app/Filament/Resources/GuiaMindeeResource.php`, busca:

```php
'requiere_revision' => $resultado['confianza'] < 0.85,
```

Cambia `0.85` al valor deseado (0.0 a 1.0).

## 📁 Archivos Creados

```
app/
├── Services/
│   └── MindeeApiService.php          # Servicio de integración con Mindee
├── Models/
│   └── GuiaMindee.php                 # Modelo de datos
└── Filament/
    └── Resources/
        ├── GuiaMindeeResource.php     # Recurso principal de Filament
        └── GuiaMindeeResource/
            ├── Pages/
            │   ├── ListGuiasMindee.php
            │   ├── CreateGuiaMindee.php
            │   ├── EditGuiaMindee.php
            │   └── ViewGuiaMindee.php
            └── Widgets/
                └── GuiasMindeeStatsWidget.php

database/
└── migrations/
    └── 2025_01_19_000000_create_guias_mindee_table.php

config/
└── services.php                       # Configuración actualizada
```

## 🐛 Solución de Problemas

### Error: "API de Mindee no configurada"

- Verifica que tengas `MINDEE_API_KEY` en tu archivo `.env`
- Ejecuta `php artisan config:clear`

### Error: "Archivo no encontrado"

- Ejecuta `php artisan storage:link`
- Verifica permisos de la carpeta `storage/app/public`

### La confianza es muy baja

- Asegúrate de que la imagen esté clara y legible
- Intenta con una imagen de mejor calidad
- Verifica que el documento sea compatible

## 📝 Notas

- Las imágenes se guardan en `storage/app/public/guias_mindee/`
- El tamaño máximo por archivo es 10MB
- Formatos soportados: JPEG, PNG, JPG, PDF
- La respuesta completa de Mindee se guarda en `datos_json`

## 🔐 Seguridad

- Nunca compartas tu API Key
- Agrega `.env` a tu `.gitignore`
- Limita el acceso al módulo según roles de usuario

## 📞 Soporte

Para más información sobre la API de Mindee:
- Documentación: https://developers.mindee.com/
- Dashboard: https://app.mindee.com/

---

✅ **Módulo creado exitosamente** por GitHub Copilot
