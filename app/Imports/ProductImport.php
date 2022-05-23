<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToCollection{
//ToModel,WithHeadingRow{
    //public function model(array $row)
    public function collection(Collection $row)
    {
        return ([
		'A' => $row[0],
		'B' => $row[1],
		'C' => $row[2],
		'D' => $row[3],
		'E' => $row[4],
		'F' => $row[5],
		'G' => $row[6],
		'H' => $row[7],
		'I' => $row[8],
		'J' => $row[9],
		'K' => $row[10],
		'L' => $row[11],
		'M' => $row[12],
		'N' => $row[13],
		'O' => $row[14],
		'P' => $row[15],
		'Q' => $row[16],
		'R' => $row[17],
		'S' => $row[18],
		'T' => $row[19],
		'U' => $row[20],
		'V' => $row[21],
		'W' => $row[22]
        ]);
    }
}
