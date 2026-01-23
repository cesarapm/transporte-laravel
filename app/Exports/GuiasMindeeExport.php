<?php

namespace App\Exports;

use App\Models\GuiaMindee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GuiasMindeeExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return GuiaMindee::orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Estado',
            'Fecha Creación',
            'Fecha Envío',
            'Nombre Archivo',
            'Confianza %',
            'Requiere Revisión',

            // Transportista
            'Transportista',
            'Dirección Transportista',
            'Número Manifiesto',
            'Folio/Factura',
            'Número Rastreo',

            // Remitente
            'Remitente Nombre',
            'Remitente Teléfono',
            'Remitente Dirección',
            'Remitente Colonia',
            'Remitente Ciudad',
            'Remitente Estado',
            'Remitente CP',
            'Remitente País',

            // Destinatario
            'Destinatario Nombre',
            'Destinatario Teléfono',
            'Destinatario Dirección',
            'Destinatario Colonia',
            'Destinatario Ciudad',
            'Destinatario Estado',
            'Destinatario CP',
            'Destinatario País',

            // Envío
            'Total Paquetes',
            'Número Cajas',
            'Peso Total',
            'Unidad Peso',
            'Costo Flete',
            'Valor Asegurado',

            // Items
            'Categorías Items',

            // Error
            'Mensaje Error',
        ];
    }

    public function map($guia): array
    {
        return [
            $guia->id,
            $guia->estado_procesamiento,
            $guia->created_at?->format('d/m/Y H:i'),
            $guia->ship_date?->format('d/m/Y'),
            $guia->nombre_archivo,
            $guia->confianza_promedio,
            $guia->requiere_revision ? 'Sí' : 'No',

            // Transportista
            $guia->carrier_name,
            $guia->carrier_address,
            $guia->manifest_number,
            $guia->folio_invoice_number,
            $guia->tracking_number,

            // Remitente
            $guia->shipper_name,
            $guia->ship_phone_number,
            $guia->shipper_address,
            $guia->shipper_suburb,
            $guia->shipper_city,
            $guia->shipper_state,
            $guia->shipper_zip_code,
            $guia->shipper_country,

            // Destinatario
            $guia->consignee_name,
            $guia->consignee_phone_number,
            $guia->consignee_address,
            $guia->consignee_colonia,
            $guia->consignee_city,
            $guia->consignee_state,
            $guia->consignee_zip_code,
            $guia->consignee_country,

            // Envío
            $guia->total_packages,
            $guia->shipper_box_count,
            $guia->total_weight,
            $guia->weight_unit,
            $guia->shipper_freight_cost,
            $guia->shipper_insured_value,

            // Items
            is_array($guia->item_categories) ? implode(', ', $guia->item_categories) : $guia->item_categories,

            // Error
            $guia->error_mensaje,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
