<?php
namespace App\Http\Excel;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;

class export implements FromCollection,WithHeadings, WithEvents
{
    protected $data;
    protected $headings;
    protected $columnWidth = []; //設定列寬       key：列  value:寬
    protected $rowHeight = [];  //設定行高       key：行  value:高
    protected $mergeCells = []; //合併單元格      key：第一個單元格  value:第二個單元格
    protected $font = [];       //設定字型       key：A1:K8  value:11
    protected $bold = [];       //設定粗體       key：A1:K8  value:true
    protected $background = []; //設定背景顏色    key：A1:K8  value:#F0F0F0F
    protected $vertical = [];   //設定定位       key：A1:K8  value:center
    //設定頁面屬性時如果無效   更改excel格式嘗試即可
    //建構函式傳值
    public function __construct($data, $headings)
    {
        $this->data = $data;
        $this->headings = $headings;
        $this->createData();
    }
    public function headings(): array
    {
        return $this->headings;
    }
    //陣列轉集合
    public function collection()
    {
        return new Collection($this->data);
    }
    //業務程式碼
    public function createData()
    {
        $this->data = collect($this->data)->toArray();
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class  => function(AfterSheet $event) {
                //設定列寬
                foreach ($this->columnWidth as $column => $width) {
                    $event->sheet->getDelegate()
                        ->getColumnDimension($column)
                        ->setWidth($width);
                }
                //設定行高，$i為資料行數
                foreach ($this->rowHeight as $row => $height) {
                    $event->sheet->getDelegate()
                        ->getRowDimension($row)
                        ->setRowHeight($height);
                }
                //設定區域單元格垂直居中
                foreach ($this->vertical as $region => $position) {
                    $event->sheet->getDelegate()
                        ->getStyle($region)
                        ->getAlignment()
                        ->setVertical($position);
                }
		//設定區域單元格水平置中
                foreach ($this->horizontal as $region => $position) {
                    $event->sheet->getDelegate()
                        ->getStyle($region)
                        ->getAlignment()
                        ->setHorizontal($position);
                }
                //設定區域單元格字型
                foreach ($this->font as $region => $value) {
                    $event->sheet->getDelegate()
                        ->getStyle($region)
                        ->getFont()
                        ->setSize($value);
                }
                //設定區域單元格字型粗體
                foreach ($this->bold as $region => $bool) {
                    $event->sheet->getDelegate()
                        ->getStyle($region)
                        ->getFont()
                        ->setBold($bool);
                }
                //設定區域單元格背景顏色
                foreach ($this->background as $region => $item) {
                    $event->sheet->getDelegate()->getStyle($region)->applyFromArray([
                        'fill' => [
                            'fillType' => 'linear', //線性填充，類似漸變
                            'startColor' => [
                                'rgb' => $item //初始顏色
                            ],
                            //結束顏色，如果需要單一背景色，請和初始顏色保持一致
                            'endColor' => [
                                'argb' => $item
                            ]
                        ]
                    ]);
                }
                //合併單元格
                foreach ($this->mergeCells as $start => $end) {
                    $event->sheet->getDelegate()->mergeCells($start.':'.$end);
                }
            }
        ];
    }
    public function setColumnWidth (array $columnwidth)
    {
        $this->columnWidth = array_change_key_case($columnwidth, CASE_UPPER);
    }
    public function setRowHeight (array $rowHeight)
    {
        $this->rowHeight = $rowHeight;
    }
    public function setFont (array $fount)
    {
        $this->font = array_change_key_case($fount, CASE_UPPER);
    }
    public function setBold (array $bold)
    {
        $this->bold = array_change_key_case($bold, CASE_UPPER);
    }
    public function setBackground (array $background)
    {
        $this->background = array_change_key_case($background, CASE_UPPER);
    }
    public function setMergeCells (array $mergeCells)
    {
        $this->mergeCells = array_change_key_case($mergeCells, CASE_UPPER);
    }
    public function setFontSize (array $fontSize)
    {
        $this->fontSize = array_change_key_case($fontSize, CASE_UPPER);
    }
    public function setBorders (array $borders)
    {
        $this->borders = array_change_key_case($borders, CASE_UPPER);
    }
    public function setVertical (array $vertical)
    {
	$this->vertical = array_change_key_case($vertical, CASE_UPPER);
    }
    public function setHorizontal (array $horizontal)
    {
        $this->horizontal = array_change_key_case($horizontal, CASE_UPPER);
    }
}
