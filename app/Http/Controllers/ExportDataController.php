<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportDataController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $vehicles = $user->vehicles()
            ->with(['rotations.placements.tire'])
            ->orderBy('last_selected_at', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($vehicles as $index => $vehicle) {
            $sheet = $spreadsheet->createSheet($index);
            $name = $vehicle->nickname ?: $vehicle->yearMakeModel;
            $sheet->setTitle(mb_substr(preg_replace('/[\/\\\\?\*\[\]:]+/', '-', $name), 0, 31));
            $this->writeVehicleSheet($sheet, $vehicle);
        }

        if ($spreadsheet->getSheetCount() === 0) {
            $sheet = $spreadsheet->createSheet(0);
            $sheet->setTitle('No Data');
            $sheet->setCellValue('A1', 'No vehicles found.');
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'treadmark-export-'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function writeVehicleSheet(Worksheet $sheet, mixed $vehicle): void
    {
        $sheet->setCellValue('A1', $vehicle->yearMakeModel);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->setCellValue('A2', $vehicle->nickname ? 'Nickname: '.$vehicle->nickname : '');
        $sheet->setCellValue('A3', 'Tire count: '.$vehicle->tire_count);

        $row = 5;

        $headers = [
            'A' => 'Date',
            'B' => 'Odometer',
            'C' => 'Type',
            'D' => 'Rotation Note',
            'E' => 'Tire Label',
            'F' => 'From Position',
            'G' => 'To Position',
            'H' => 'Tread Center (32nds)',
            'I' => 'Tread Inner (32nds)',
            'J' => 'Tread Outer (32nds)',
            'K' => 'Tire Note',
            'L' => 'Feathering',
            'M' => 'Cupping',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col.$row, $label);
        }

        $sheet->getStyle('A'.$row.':M'.$row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F1410']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row++;

        foreach ($vehicle->rotations->sortBy('odometer') as $rotation) {
            $type = match (true) {
                $rotation->is_setup => 'Setup',
                $rotation->is_swap => 'Swap',
                default => 'Rotation',
            };

            foreach ($rotation->placements as $placement) {
                $sheet->setCellValue('A'.$row, $rotation->rotated_on?->format('Y-m-d') ?? '');
                $sheet->setCellValue('B'.$row, $rotation->odometer);
                $sheet->setCellValue('C'.$row, $type);
                $sheet->setCellValue('D'.$row, $rotation->note ?? '');
                $sheet->setCellValue('E'.$row, $placement->tire?->label ?? '');
                $sheet->setCellValue('F'.$row, $placement->from_position?->value ?? '');
                $sheet->setCellValue('G'.$row, $placement->to_position?->value ?? '');
                $sheet->setCellValue('H'.$row, $placement->tread_center);
                $sheet->setCellValue('I'.$row, $placement->tread_inner ?? '');
                $sheet->setCellValue('J'.$row, $placement->tread_outer ?? '');
                $sheet->setCellValue('K'.$row, $placement->note ?? '');
                $sheet->setCellValue('L'.$row, $placement->is_feathering ? 'Yes' : '');
                $sheet->setCellValue('M'.$row, $placement->is_cupped ? 'Yes' : '');

                if ($row % 2 === 0) {
                    $sheet->getStyle('A'.$row.':M'.$row)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F2']],
                    ]);
                }

                $row++;
            }
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
