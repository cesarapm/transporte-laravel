<div class="space-y-6">
    <!-- Información Principal -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Servicio</h3>
            <p class="text-gray-700 dark:text-gray-300">{{ $metadata['servicio'] ?? 'No especificado' }}</p>
        </div>

        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Tipo de Envío</h3>
            <p class="text-gray-700 dark:text-gray-300">{{ $metadata['tipo_envio'] ?? 'No especificado' }}</p>
        </div>
    </div>

    <!-- Información Técnica -->
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Información Técnica</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Cotización</p>
                <p class="text-gray-700 dark:text-gray-300">
                    @if(isset($metadata['fecha_cotizacion']))
                        {{ \Carbon\Carbon::parse($metadata['fecha_cotizacion'])->format('d/m/Y H:i:s') }}
                    @else
                        No especificada
                    @endif
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Timestamp</p>
                <p class="text-gray-700 dark:text-gray-300">{{ $metadata['timestamp'] ?? 'No especificado' }}</p>
            </div>
        </div>
    </div>

    <!-- User Agent -->
    @if(isset($metadata['user_agent']))
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Información del Navegador</h3>
        <div class="bg-white dark:bg-gray-900 p-3 rounded border">
            <code class="text-sm text-gray-600 dark:text-gray-400 break-all">{{ $metadata['user_agent'] }}</code>
        </div>
    </div>
    @endif

    <!-- JSON Completo -->
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">JSON Completo</h3>
        <div class="bg-white dark:bg-gray-900 p-3 rounded border">
            <pre class="text-sm text-gray-600 dark:text-gray-400 overflow-x-auto">{{ json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
</div>
