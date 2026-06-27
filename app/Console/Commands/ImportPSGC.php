<?php

namespace App\Console\Commands;

use App\Models\Psgc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportPSGC extends Command
{
    protected $signature = 'psgc:import';

    protected $description = 'Import PSGC Excel';

    public function handle()
    {
        $path = database_path('psgc/PSGC-1Q-2026-Publication-Datafile.xlsx');

        if (!file_exists($path)) {
            $this->error("Excel file not found:");
            $this->line($path);
            return Command::FAILURE;
        }

        $this->info('Opening Excel...');

        $spreadsheet = IOFactory::load($path);

        $sheet = $spreadsheet->getSheetByName('PSGC');

        if (!$sheet) {
            $this->error('Worksheet PSGC not found.');
            return Command::FAILURE;
        }

        $rows = $sheet->toArray();

        DB::beginTransaction();

        try {

            Psgc::truncate();

            $regionCode = null;
            $provinceCode = null;
            $cityCode = null;

            $bar = $this->output->createProgressBar(count($rows));

            foreach ($rows as $index => $row) { 

                // Skip header
                if ($index == 0) {
                    $bar->advance();
                    continue;
                }

                $psgc = trim($row[0] ?? '');
                $name = trim($row[1] ?? '');
                $correspondence = trim($row[2] ?? '');
                $level = trim($row[3] ?? '');

                if ($psgc == '' || $level == '') {
                    $bar->advance();
                    continue;
                }

                switch ($level) {

                    case 'Reg':
                        $regionCode = $psgc;
                        $provinceCode = null;
                        $cityCode = null;
                        break;

                    case 'Prov':
                        $provinceCode = $psgc;
                        $cityCode = null;
                        break;

                    case 'City':
                    case 'Mun':
                    case 'SubMun':
                        $cityCode = $psgc;
                        break;

                }

                Psgc::create([

                    'psgc_code' => $psgc,

                    'correspondence_code' => $correspondence,

                    'name' => $name,

                    'geographic_level' => $level,

                    'parent_code' => match($level) {
                        'Reg' => null,
                        'Prov' => $regionCode,
                        'City', 'Mun', 'SubMun' => $provinceCode ?: $regionCode,
                        'Bgy' => $cityCode,
                        default => null,
                    },

                    'region_code' => $regionCode,

                    'province_code' => $provinceCode,

                    'city_code' => $cityCode,

                    'city_class' => trim($row[5] ?? ''),

                    'income_classification' => trim($row[6] ?? ''),

                    'urban_rural' => trim($row[7] ?? ''),

                    'population' => is_numeric($row[8] ?? null)
                        ? (int)$row[8]
                        : null,

                    'status' => trim($row[10] ?? ''),

                ]);

                $bar->advance();
            }

            $bar->finish();

            DB::commit();

            $this->newLine(2);

            $this->info('Import completed successfully.');

        } catch (\Throwable $e) {

            DB::rollBack();

            throw $e;

        }   
    }
}