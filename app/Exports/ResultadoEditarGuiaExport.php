<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ResultadoEditarGuiaExport implements FromCollection, WithHeadings
{
    protected $resultados;

    public function __construct(array $resultados)
    {
        $this->resultados = $resultados;
    }

    public function collection()
    {
        return collect($this->resultados);
    }

    public function headings(): array
    {
        return ['id', 'paqueteria', 'rastreo', 'estatus', 'mensaje'];
    }
}
