<?php
// app/Exports/ReporteExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exportación genérica: (opcional) encabezado del reporte + encabezados de columna + filas
 * + (opcional) bloque de resumen al final.
 *
 * @param string     $titulo       Nombre de la hoja
 * @param array      $headings     Encabezados de columna
 * @param array      $rows         Filas (arrays de valores ya formateados)
 * @param array      $resumen      Filas [etiqueta, valor] que se agregan al final
 * @param array      $moneyCols    Índices (base 0) de columnas de datos con formato de dinero
 * @param array      $resumenMoney Índices (base 0) de filas del resumen que son dinero
 * @param array|null $encabezado   ['logo' => ruta absoluta|null, 'sucursal' => str, 'titulo' => str,
 *                                  'rango' => str|null, 'generado' => str]. Si es null no se dibuja.
 */
class ReporteExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle, WithCustomStartCell, WithDrawings
{
    private const AZUL_OSCURO = 'FF1F3864';
    private const AZUL_MEDIO = 'FF2F5597';
    private const AZUL_CLARO = 'FFDDEBF7';
    private const ZEBRA = 'FFF2F6FC';
    private const BORDE = 'FF9FB0C8';
    private const FORMATO_DINERO = '"₡"#,##0.00';

    private const ALTO_FILA_DATOS = 22;   // puntos
    private const ALTO_BARRA = 6;         // barra azul bajo el encabezado
    private const ALTO_ESPACIO = 12;      // espacio entre la barra y la tabla
    private const PX_POR_SANGRIA = 9;     // ancho aprox. de 1 nivel de sangría en Excel (ajustable)

    public function __construct(
        private string $titulo,
        private array $headings,
        private array $rows,
        private array $resumen = [],
        private array $moneyCols = [],
        private array $resumenMoney = [],
        private ?array $encabezado = null,
    ) {
    }

       /** Líneas de texto del encabezado (sin contar el logo). */
    private function lineasEncabezado(): array
    {
        if ($this->encabezado === null) {
            return [];
        }

        $lineas = [
            ['texto' => $this->encabezado['titulo'], 'size' => 26, 'bold' => true, 'color' => self::AZUL_OSCURO, 'alto' => 46],
        ];
        if (!empty($this->encabezado['rango'])) {
            $lineas[] = ['texto' => $this->encabezado['rango'], 'size' => 11, 'bold' => false, 'color' => 'FF333333'];
        }
        $lineas[] = ['texto' => $this->encabezado['generado'], 'size' => 10, 'bold' => false, 'color' => 'FF666666'];

        return $lineas;
    }

    private function altoLinea(array $linea): float
    {
        return $linea['alto'] ?? ($linea['size'] + 10);
    }

    /** Alto total (en puntos) del bloque de textos. */
    private function altoBloqueTextos(): float
    {
        $t = 0;
        foreach ($this->lineasEncabezado() as $l) {
            $t += $this->altoLinea($l);
        }
        return $t;
    }

    /** Fila donde van los encabezados de columna. */
    private function filaHeadings(): int
    {
        if ($this->encabezado === null) {
            return 1;
        }
        // líneas de texto + barra + espacio, y luego los encabezados de columna
        return count($this->lineasEncabezado()) + 2 + 1;
    }

    /** Alto del logo en píxeles y su ancho proporcional. */
    private function medidasLogo(): ?array
    {
        $logo = $this->encabezado['logo'] ?? null;
        if (!$logo || !is_file($logo)) {
            return null;
        }

        $alto = (int) round($this->altoBloqueTextos() * 1.333) - 12; // puntos → píxeles
        $ancho = $alto;
        $info = @getimagesize($logo);
        if ($info && $info[1] > 0) {
            $ancho = (int) round($alto * ($info[0] / $info[1]));
        }

        return ['alto' => $alto, 'ancho' => $ancho];
    }

    public function startCell(): string
    {
        return 'A' . $this->filaHeadings();
    }

    public function drawings()
    {
        $m = $this->medidasLogo();
        if ($m === null) {
            return [];
        }

        $d = new Drawing();
        $d->setName('Logo');
        $d->setPath($this->encabezado['logo']);
        $d->setHeight($m['alto']);
        $d->setCoordinates('A1');
        $d->setOffsetX(8);
        $d->setOffsetY(6);

        return $d;
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
        $h = $this->filaHeadings();            // fila de encabezados de columna
        $primeraDato = $h + 1;
        $ultimaFila = count($this->rows) + $h; // encabezado + filas de datos

        $sheet->setShowGridlines(false);
        $sheet->getDefaultRowDimension()->setRowHeight(self::ALTO_FILA_DATOS);

        // ---------- Encabezado del reporte (logo a la izquierda, textos a la derecha) ----------
        if ($this->encabezado !== null) {
            $m = $this->medidasLogo();
            $sangria = $m ? (int) ceil(($m['ancho'] + 24) / self::PX_POR_SANGRIA) : 0;

            $fila = 1;
            foreach ($this->lineasEncabezado() as $l) {
                $sheet->setCellValue("A{$fila}", $l['texto']);
                if ($ultCol !== 'A') {
                    $sheet->mergeCells("A{$fila}:{$ultCol}{$fila}");
                }
                $sheet->getStyle("A{$fila}")->applyFromArray([
                    'font' => ['bold' => $l['bold'], 'size' => $l['size'], 'color' => ['argb' => $l['color']]],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'indent' => $sangria,
                    ],
                ]);
                $sheet->getRowDimension($fila)->setRowHeight($this->altoLinea($l));
                $fila++;
            }

            // Barra azul de lado a lado
            $sheet->getStyle("A{$fila}:{$ultCol}{$fila}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::AZUL_OSCURO]],
            ]);
            $sheet->getRowDimension($fila)->setRowHeight(self::ALTO_BARRA);

            // Espacio antes de la tabla
            $sheet->getRowDimension($fila + 1)->setRowHeight(self::ALTO_ESPACIO);
        }

        // ---------- Encabezados de columna ----------
        $sheet->getStyle("A{$h}:{$ultCol}{$h}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::AZUL_OSCURO]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::AZUL_OSCURO]],
                'vertical' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']],
            ],
        ]);
        $sheet->getRowDimension($h)->setRowHeight(32);
        $sheet->freezePane("A{$primeraDato}");

        // ---------- Datos ----------
        if ($ultimaFila >= $primeraDato) {
            $rango = "A{$primeraDato}:{$ultCol}{$ultimaFila}";

            $sheet->getStyle($rango)->applyFromArray([
                'font' => ['size' => 11],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::BORDE]],
                    'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::AZUL_OSCURO]],
                ],
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
                $col = $sheet->getStyle("{$letra}{$primeraDato}:{$letra}{$ultimaFila}");
                $col->getNumberFormat()->setFormatCode(self::FORMATO_DINERO);
                $col->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            $sheet->setAutoFilter("A{$h}:{$ultCol}{$ultimaFila}");
        }

        // ---------- Bloque de resumen ----------
        if (!empty($this->resumen)) {
            $filaTitulo = $ultimaFila + 2;
            $primera = $filaTitulo + 1;
            $ultima = $filaTitulo + count($this->resumen);

            $sheet->getStyle("A{$filaTitulo}:B{$filaTitulo}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::AZUL_MEDIO]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($filaTitulo)->setRowHeight(26);

            $sheet->getStyle("A{$primera}:B{$ultima}")->applyFromArray([
                'font' => ['size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::AZUL_CLARO]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::BORDE]]],
            ]);
            $sheet->getStyle("A{$filaTitulo}:B{$ultima}")->applyFromArray([
                'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::AZUL_OSCURO]]],
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
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($h, $h);

        return [];
    }
}