<?php
namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Imports\GuiaImport;
use App\Imports\EditarGuiaImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;

class GuiaUploader extends Component
{
    use WithFileUploads;

    public $file;
    public $file2;

    public function save()
    {
        if ($this->file) {
            try {
                $import = new GuiaImport();
                Excel::import($import, $this->file);

                if (!empty($import->errores)) {
                    $erroresHtml = '<ul>';
                    foreach ($import->errores as $error) {
                        $erroresHtml .= "<li><strong>- </strong> {$error}</li>";
                    }
                    $erroresHtml .= '</ul>';

                    Notification::make()
                        ->title('Errores en la importación')
                        ->danger()
                        ->body($erroresHtml)
                        ->send();
                } else {
                    Notification::make()
                        ->title('El archivo se ha importado correctamente.')
                        ->success()
                        ->send();
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error al importar el archivo: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }

            $this->dispatchBrowserEvent('import-finished');
        }
    }

    public function save2()
    {
        if ($this->file2) {
            try {
                $import = new EditarGuiaImport();
                Excel::import($import, $this->file2);

                Notification::make()
                    ->title('Archivo actualizado correctamente.')
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error al actualizar archivo: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }

            $this->dispatchBrowserEvent('import-finished');
        }
    }

    public function render()
    {
        return view('livewire.guia-uploader');
    }
}
