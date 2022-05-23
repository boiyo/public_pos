<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockAdjustmentsImport implements ToCollection{
    public function collection(Collection $row)
    {
        return ([
           'A' => $row[0],
           'B' => $row[1]
        ]);
    }
}
