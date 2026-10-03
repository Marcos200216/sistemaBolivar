<?php
// app/Exports/ReporteExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exportación genérica: encabezados + filas + (opcional) bloque de resumen al final.
 *
 * @param string $titulo       Nombre de la hoja
 * @param array  $headings     Encabezados de columna
 * @param array  $rows         Filas (arrays de valores ya formateados)
 * @param array  $resumen      Filas [etiqueta, valor] que se agregan al final
 * @param array  $moneyCols    Índices (base 0) de columnas de datos con formato de dinero
 * @param array  $resumenMoney Índices (base 0) de filas del resumen que son dinero
 */
class ReporteExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    private const AZUL_OSCURO = 'FF1F3864';
    private const AZUL_MEDIO = 'FF2F5597';
    private const AZUL_CLARO = 'FFDDEBF7';
    private const ZEBRA = 'FFF2F6FC';
    private const BORDE = 'FFD0D7E2';
    private const FORMATO_DINERO = '"₡"#,##0.00';

    public function __construct(
        private string $titulo,
        private array $headings,
        private array $rows,
        private array $resumen = [],
        private array $moneyCols = [],
        private array $resumenMoney = [],
    ) {
    }

    public function title(): string
    {
        return $this->titulo;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        $out = $this->rows;

        if (!empty($this->resumen)) {
            $out[] = [''];          // una celda vacía para que la fila no se pierda
            $out[] = ['RESUMEN'];
            foreach ($this->resumen as $fila) {
                $out[] = $fila;
            }
        }

        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $ultCol = Coordinate::stringFromColumnIndex(max(1, count($this->headings)));
        $ultimaFila = count($this->rows) + 1; // encabezado + filas de datos

        $sheet->setShowGridlines(false);

        // ---------- Encabezado ----------
        $sheet->getStyle("A1:{$ultCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::AZUL_OSCURO]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::AZUL_OSCURO]]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->freezePane('A2');

        // ---------- Datos ----------
        if ($ultimaFila >= 2) {
            $rango = "A2:{$ultCol}{$ultimaFila}";

            $sheet->getStyle($rango)->applyFromArray([
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::BORDE]]],
            ]);

            // Filas alternadas (formato condicional: liviano incluso con decenas de miles de filas)
            $zebra = new Conditional();
            $zebra->setConditionType(Conditional::CONDITION_EXPRESSION)
                ->addCondition('MOD(ROW(),2)=0');
            $zebra->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)
                ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::ZEBRA))
                ->setEndColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::ZEBRA));
            $sheet->getStyle($rango)->setConditionalStyles([$zebra]);

            foreach ($this->moneyCols as $i) {
                $letra = Coordinate::stringFromColumnIndex($i + 1);
                $col = $sheet->getStyle("{$letra}2:{$letra}{$ultimaFila}");
                $col->getNumberFormat()->setFormatCode(self::FORMATO_DINERO);
                $col->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            $sheet->setAutoFilter("A1:{$ultCol}{$ultimaFila}");
        }

        // ---------- Bloque de resumen ----------
        if (!empty($this->resumen)) {
            $filaTitulo = $ultimaFila + 2;
            $primera = $filaTitulo + 1;
            $ultima = $filaTitulo + count($this->resumen);

            $sheet->getStyle("A{$filaTitulo}:B{$filaTitulo}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::AZUL_MEDIO]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $sheet->getStyle("A{$primera}:B{$ultima}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::AZUL_CLARO]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::BORDE]]],
            ]);
            $sheet->getStyle("A{$primera}:A{$ultima}")->getFont()->setBold(true);
            $sheet->getStyle("B{$primera}:B{$ultima}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            foreach (array_keys($this->resumen) as $i) {
                $celda = 'B' . ($primera + $i);
                $sheet->getStyle($celda)->getNumberFormat()->setFormatCode(
                    in_array($i, $this->resumenMoney, true) ? self::FORMATO_DINERO : '#,##0'
                );
            }
        }

        // ---------- Impresión ----------
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

        return [];
    }
}