<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class GuiasExport implements FromCollection, WithHeadings
{
    protected $registros;

    public function __construct(Collection $registros)
    {
        $this->registros = $registros;
    }

    public function collection()
    {
        return $this->registros;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Telefono de Remitente',
            'Guía Interna',
            'Remesa',
            'Folio',
            'Paqueteria',
            'Rastreo'
        ];
    }
}
