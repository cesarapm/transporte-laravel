<div>
    <!-- Migas de pan -->
    <x-filament::breadcrumbs :breadcrumbs="[
        '/admin/guias' => 'Guias',
        '' => 'List',
    ]" />

    <div class="flex justify-between mt-1">
        <div class="font-bold text-3xl">Guias</div>
        <div>
            {{ $data }}
        </div>
    </div>

    <!-- Formulario para importar guía interna -->
    <div wire:loading.class="cursor-wait">
        <form wire:submit.prevent="save" class="w-full max-w-lg flex mt-2">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="fileInput">
                    Subir Guia Interna
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:shadow-outline"
                    id="fileInput"
                    wire:model="file"
                    type="file">
            </div>
            <div class="flex items-center justify-between mt-3">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="bg-blue-500 mx-2 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    Cargar
                </button>
            </div>
        </form>

        @if ($file)
            <div class="mt-2 text-green-600 font-bold">
                Archivo cargado: {{ $file->getClientOriginalName() }}
            </div>
        @endif
    </div>

    <!-- Formulario para editar guía interna -->
    <div wire:loading.class="cursor-wait">
        <form wire:submit.prevent="save2" class="w-full max-w-lg flex mt-2">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="fileInput2">
                    Editar Guia Interna
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:shadow-outline"
                    id="fileInput2"
                    wire:model="file2"
                    type="file">
            </div>
            <div class="flex items-center justify-between mt-3">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="bg-blue-500 mx-2 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    Cargar
                </button>
            </div>
        </form>

        @if ($file2)
            <div class="mt-2 text-green-600 font-bold">
                Archivo cargado: {{ $file2->getClientOriginalName() }}
            </div>
        @endif
    </div>
</div>

<!-- Tailwind CSS (si no lo tienes en tu layout ya) -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">

<!-- Estilo para cursor de espera -->
<style>
    .cursor-wait {
        cursor: wait !important;
    }
</style>

<!-- JavaScript para aplicar cursor de espera en todo el documento -->
<script>
    document.addEventListener('livewire:load', function () {
        Livewire.hook('message.sent', () => {
            document.documentElement.classList.add('cursor-wait');
        });

        Livewire.hook('message.processed', () => {
            document.documentElement.classList.remove('cursor-wait');
        });
    });
</script>
