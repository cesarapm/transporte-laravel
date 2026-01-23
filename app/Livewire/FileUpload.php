<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\GuiaImport;
use Filament\Notifications\Notification;

class FileUpload extends Component
{
    public $file;

    public function save()
    {
        if ($this->file) {
            try {
                // Procesa el archivo
                Excel::import(new GuiaImport, $this->file);

                // Notificación de éxito
                Notification::make()
                    ->title('El archivo se ha importado correctamente.')
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                // Notificación de error
                Notification::make()
                    ->title('Error al importar el archivo: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }

    public function render()
    {
        return view('livewire.file-upload');
    }
}
