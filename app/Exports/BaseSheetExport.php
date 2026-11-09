<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Format laporan Excel standar: 2 baris judul, header berwarna, border tabel.
 * Turunan cukup isi title(), headings() dan rows(). Jumlah kolom dihitung
 * otomatis dari headings(), jadi tidak ada range kolom yang hardcode.
 */
abstract class BaseSheetExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    abstract protected function title(): string;

    abstract protected function rows(): Collection;

    /** Kolom (huruf) yang berisi angka rupiah, contoh ['F', 'G']. */
    protected function currencyColumns(): array
    {
        return [];
    }

    public function collection()
    {
        return $this->rows();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(count($this->headings()));

                $sheet->insertNewRowBefore(1, 3);
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A1', strtoupper($this->title()));
                $sheet->setCellValue('A2', strtoupper(config('store.name')) . ' - PER TANGGAL ' . strtoupper(now()->translatedFormat('d M Y')));
                $sheet->getStyle('A1:A2')->getFont()->setBold(true);
                $sheet->getStyle('A1')->getFont()->setSize(14);
                $sheet->getStyle("A1:{$lastCol}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("A4:{$lastCol}4")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E94560']],
                ]);

                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A4:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);

                foreach ($this->currencyColumns() as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$lastRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
                }
            },
        ];
    }
}
