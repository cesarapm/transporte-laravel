<?php

namespace App\Filament\Resources\GuiaResource\Pages;

use App\Filament\Resources\GuiaResource;
use App\Imports\GuiaImport;
use App\Imports\EditarGuiaImport;
use App\Models\Guia;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;

use Illuminate\Database\QueryException;

class ListGuias extends ListRecords
{
    protected static string $resource = GuiaResource::class;

    public $file;
    public $file2;


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    public function getHeader(): ?View
    {
        $data = Actions\CreateAction::make();
        // return view('Filament.custom.upload-file',compact('data'));
        return view('Filament.custom.upload-file', [
            'data' => Actions\CreateAction::make(),
            // 'file' => $this->file
            'file' => $this->file,
            'file2' => $this->file2, // Pasar también file2 a la vista
        ]);
    }


    public function save()
    {
        if ($this->file) {
            try {
                $import = new GuiaImport();
                Excel::import($import, $this->file);

                // Si hubo errores en la importación, mostrar en notificación
                if (!empty($import->errores)) {
                    // Construir la lista de errores como un HTML
                    $erroresHtml = '<ul>';
                    foreach ($import->errores as $error) {
                        // Puedes agregar un texto de guía o diferenciación antes de cada error
                        $erroresHtml .= "<li><strong>- </strong> {$error}</li>";
                    }
                    $erroresHtml .= '</ul>';

                    Notification::make()
                        ->title('Errores en la importación')
                        ->danger()
                        ->body($erroresHtml) // Usar el HTML con la lista de errores
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
        }
    }


    public function save2()
    {
        if ($this->file2) {
            try {
                Excel::import(new EditarGuiaImport, $this->file2);

                Notification::make()
                    ->title('Las guías se han editado correctamente.')
                    ->success()
                    ->send();
            } 
            
            
            // catch (QueryException $e) {
            //     if ($e->getCode() == 23000) {
            //         Notification::make()
            //             ->title('Error: La guía ya existe en la base de datos.')
            //             ->danger()
            //             ->send();
            //     } else {
            //         Notification::make()
            //             ->title('Error al editar las guías: ' . $e->getMessage())
            //             ->danger()
            //             ->send();
            //     }
            // }


         catch (QueryException $e) {
                if ($e->getCode() == 23000) {
                    Notification::make()
                        ->title('Error: La guía ya existe en la base de datos.')
                        ->danger()
                        ->persistent() // ← Esto mantiene visible la notificación
                        ->send();
                } else {
                    Notification::make()
                        ->title('Error al editar las guías:')
                        ->body($e->getMessage()) // Opcional: muestra el mensaje en el cuerpo
                        ->danger()
                        ->persistent() // ← Esto también
                        ->send();
                }
            }
        }
    }
}
