    <div x-data="{ loading: false }"
     x-init="$watch('loading', value => document.body.style.cursor = value ? 'wait' : 'default')"
     x-on:import-finished.window="loading = false"
>
    <!-- Formulario de carga -->
    <form wire:submit.prevent="save" @submit="loading = true" class="w-full max-w-lg flex mt-2">
        <div class="w-full">
            <label class="block text-sm font-bold mb-1 text-gray-700">Subir Guía Interna</label>
            <input type="file" wire:model="file" class="w-full border rounded py-2 px-3" />
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 mt-2 rounded hover:bg-blue-700">
                Cargar
            </button>
            @if ($file)
                <p class="mt-2 text-green-600 font-semibold">Archivo cargado: {{ $file->getClientOriginalName() }}</p>
            @endif
        </div>
    </form>

    <!-- Formulario de edición -->
    <form wire:submit.prevent="save2" @submit="loading = true" class="w-full max-w-lg flex mt-6">
        <div class="w-full">
            <label class="block text-sm font-bold mb-1 text-gray-700">Editar Guía Interna</label>
            <input type="file" wire:model="file2" class="w-full border rounded py-2 px-3" />
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 mt-2 rounded hover:bg-blue-700">
                Cargar
            </button>
            @if ($file2)
                <p class="mt-2 text-green-600 font-semibold">Archivo cargado: {{ $file2->getClientOriginalName() }}</p>
            @endif
        </div>
    </form>
</div>
