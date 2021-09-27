<?php
namespace App\Http\Controllers;

ini_set('max_execution_time','300');

use App\cashflowCbnPlacement;
use App\variables;
use Illuminate\Http\Request;
use App\Imports\UsersImport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use DateTime;
use Session;
  
class PCBN extends Controller
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function importExportView()
    {
       return view('import');
    }
   
    /**
    * @return \Illuminate\Support\Collection
    */
    public function ExportDateFormat($date)
    {
        return date('d-M-y', strtotime($date));
    }
    public function ExportDateFormatMatured($date, $end_date, $check)
    {
        if ($end_date <= $check) {
            $date = $end_date;
            return date('d-M-y', strtotime($date));
        }
        return "";
    }

    public function GetTenor($start_date, $end_date, $reporting_date)
    {
        //dd(DateTime::createFormFormat("Y-m-d", $start_date)->format("Y"));
        $start_year = date('Y', strtotime($start_date));
        $last_year = date("Y") - 1;

        if ($start_date == $reporting_date) {
            return 1;
        } elseif ($reporting_date < $end_date && $start_year != $last_year) {
            $diff = abs(strtotime($reporting_date) - strtotime($start_date));
            $days = $diff/(60*60*24);
            return $days;
        } elseif ($reporting_date < $end_date && $start_year == $last_year) {
            $diff = abs(strtotime($reporting_date) - strtotime("first day of january this year"));
            $days = $diff/(60*60*24);
        }  elseif ($reporting_date > $end_date && $start_year == $last_year) {
            $diff = abs(strtotime($end_date) - strtotime("first day of january this year"));
            $days = $diff/(60*60*24);
            return $days;
        } 
        else {
            $diff = abs(strtotime($end_date) - strtotime($start_date));
            $days = $diff/(60*60*24);
            return $days;
        }
        
    }

    

    public function GetInterest($principal, $tenor, $rate, $end_date)
    {
        $no_of_days = $this->GetNumDays(date('Y', strtotime($end_date))); //GET NUMBER OF DAYS IN A YEAR, LEAP OR NOT
       
        $interest = $principal*($rate/100)*($tenor/$no_of_days);
        return $interest;
    }

    public function GetNumDays($year)
    {
        $days = 0;
        for ($month=1; $month <=12; $month++) { 
            $days = $days + cal_days_in_month(CAL_GREGORIAN, $month, $year);
        }
         return $days;
    }
    
    public function GetStatus($end_date, $reporting_date)
    {
        if ($end_date <= $reporting_date) {
            $status = "Matured";
        }   else {
            $status = "Active";
        }
        return $status;
    }

    public function export(Request $request) 
    {
        //$reporting_date = date('Y-m-d', strtotime($request->reporting_date));

        $variables = new variables();
        $reporting_date = $variables->orderBy('created_at', 'desc')->pluck('reporting_date')->first();
        $first_day_of_the_year = date('Y-m-d', strtotime('first day of January this year'));
           
        //$no_of_days_in_the_year = $this->GetNumDays(date('Y', strtotime($request->reporting_date)));
        
        $excel = new \PHPExcel();
        $excel->setActiveSheetIndex(0);

        $table = new cashflowCBNPlacement();
        $data= $table->where('end_date','>=',$first_day_of_the_year)->where('start_date','<=',$reporting_date)->orderBy('start_date', 'asc')->get();
        $i = 13;
        foreach ($data as $data) {
            $excel->getActiveSheet()
            ->setCellvalue('A'.$i, $data->cpty)
            ->setCellvalue('B'.$i, $data->trade_id)
            ->setCellvalue('C'.$i, doubleval($data->rate))
            ->setCellvalue('D'.$i, $this->ExportDateFormat($data->start_date))
            ->setCellvalue('E'.$i, $this->ExportDateFormat($data->end_date))
            ->setCellvalue('F'.$i, $this->ExportDateFormatMatured($data->maturity_date, $data->end_date, $reporting_date))
            ->setCellvalue('G'.$i, $this->GetTenor($data->start_date, $data->end_date, $reporting_date))
            ->setCellvalue('H'.$i, $data->open_nominal)
            //->setCellvalue('J'.$i, '=I'.$i.'*(H'.$i.'/'.$no_of_days_in_the_year.')*(D'.$i.'/100)')
            //->setCellvalue('K'.$i, '=I'.$i.'*(H'.$i.'/'.$no_of_days_in_the_year.')*(D'.$i.'/100)')
            ->setCellvalue('I'.$i, $this->GetInterest($data->open_nominal, $this->GetTenor($data->start_date, $data->end_date, $reporting_date), doubleval($data->rate), $data->end_date))
            ->setCellvalue('J'.$i, $this->GetInterest($data->open_nominal, $this->GetTenor($data->start_date, $data->end_date, $reporting_date), doubleval($data->rate), $data->end_date))
            ->setCellvalue('K'.$i, $this->GetStatus($data->end_date, $reporting_date));
            $i++;
        }
        $sum_cell = $i + 2;

        
        //FOOTER SUMS
        $excel->getActiveSheet()
            ->setCellvalue('H'.$sum_cell, '=SUMIF(K13:K'.$i.', "Active", H13:H'.$i.')')
            ->setCellvalue('I'.$sum_cell, '=SUMIF(K13:K'.$i.', "Active", I13:I'.$i.')')
            ->setCellvalue('J'.$sum_cell, '=SUM(J13:J'.$i.')');
       
        //THE HEADER STYLES & TEXTS
        $row = $excel->getActiveSheet()->getHighestRow();
        $row+=7;
        $excel->getActiveSheet()
            ->getStyle('A1:K'.$row)->applyFromArray(
                array(
                    'fill'=>array(
                        'type'=>\PHPExcel_Style_Fill::FILL_SOLID,
                        'color'=>array('rgb'=>'B9CEA4')
                    )
                )
            );


        $excel->getActiveSheet()
            ->getStyle('A1:Z10000')->applyFromArray(
                array(
                    'fill'=>array(
                        'type'=>\PHPExcel_Style_Fill::FILL_SOLID,
                        'color'=>array('rgb'=>'b2beb5')
                    )
                )
            );
        
            $excel->getActiveSheet()->getColumnDimension('A')->setWidth('15');
            $excel->getActiveSheet()->getColumnDimension('B')->setWidth('10');
            $excel->getActiveSheet()->getColumnDimension('C')->setWidth('5');
            $excel->getActiveSheet()->getColumnDimension('D')->setWidth('15');
            $excel->getActiveSheet()->getColumnDimension('E')->setWidth('15');
            $excel->getActiveSheet()->getColumnDimension('F')->setWidth('15');
            $excel->getActiveSheet()->getColumnDimension('G')->setWidth('8');
            $excel->getActiveSheet()->getColumnDimension('H')->setWidth('18');
            $excel->getActiveSheet()->getColumnDimension('I')->setWidth('17');       
            $excel->getActiveSheet()->getColumnDimension('J')->setWidth('18');       
            $excel->getActiveSheet()->getColumnDimension('K')->setWidth('15');   

            $excel->getActiveSheet()->mergeCells('A6:C6');
           
            $excel->getActiveSheet()
            ->setCellvalue('A6', 'PLACEMENT WITH CBN')
            ->setCellvalue('A7', 'Reporting Date')
            ->setCellvalue('B7', $reporting_date)
            ->setCellvalue('A8', '366/365 app.')
            ->setCellvalue('B8', '01-Jan-'.date("y"))
            ->setCellvalue('A12', 'CPTY')
            ->setCellvalue('B12', 'Trade ID')
            ->setCellvalue('C12', 'Rate')
            ->setCellvalue('D12', 'Start Date')
            ->setCellvalue('E12', 'End Date')
            ->setCellvalue('F12', 'Maturity Date')
            ->setCellvalue('G12', 'Tenor')
            ->setCellvalue('H12', 'Open Nominal')
            ->setCellvalue('I12', 'Accrued Interest')
            ->setCellvalue('J12', 'Interest Income')
            ->setCellvalue('K12', 'Status');

            $excel->getActiveSheet()
                ->setCellvalue('H11', '1022')
                ->setCellvalue('I11', '2182')
                ->setCellvalue('J11', '8297');
                
            


            $excel->getActiveSheet()
                ->getStyle('A1:K12')->applyFromArray(
                    array(
                        'font'=>array(
                            'bold'=>true,
                            'size'=>11,
                        )
                    )
                );

            $excel->getActiveSheet()
            ->getStyle('C11:K11')->applyFromArray(
                array(
                    'font'=>array(
                        'size'=>10,
                    )
                )
            );

            $excel->getActiveSheet()
            ->getStyle('A13:K10000')->applyFromArray(
                array(
                    'font'=>array(
                        'size'=>10,
                    )
                )
            );
            $excel->getActiveSheet()->getStyle('A12:K10000')->getAlignment()->setHorizontal('right');
          
           
            $excel->getActiveSheet()
                ->getStyle('H'.$sum_cell.':J'.$sum_cell)->applyFromArray(
                    array(
                        'font'=>array(
                            'bold'=>true,
                        )
                    )
                );
            $excel->getActiveSheet()
                ->getStyle('H13:J'.$sum_cell)->getNumberFormat()->setFormatCode('#,##0.00');
        
            $excel->getActiveSheet()->freezePane('L13');
        //DESIGNING
        $objDrawing = new \PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('GTBank Logo');
        $objDrawing->setDescription('GTBank Logo');
        $objDrawing->setPath('images/logo.png');
        $objDrawing->setCoordinates('A1');                      
        //setOffsetX works properly
        $objDrawing->setOffsetX(0); 
        $objDrawing->setOffsetY(0);                
        //set width, height
        $objDrawing->setWidth(120); 
        $objDrawing->setHeight(80); 
        $objDrawing->setWorksheet($excel->getActiveSheet()); 
        

        
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename= "Pacement with CBN"'.$reporting_date.'".xlsx"');
            header('Cache-Control: max-age=0');
            //ob_end_clean();
            $file = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $file->setPreCalculateFormulas(true);
            $file->save('php://output');
    
    }
   







    /**
    * @return \Illuminate\Support\Collection
    */
    public function DateFormat($date)
    {
        $formatted_date = \PHPExcel_Style_NumberFormat::toFormattedString($date,'YYYY-MM-DD');
        return $formatted_date;
    }
    public function import(Request $request) 
    {
        
        $first_day_of_the_year = date('Y-m-d', strtotime('first day of January this year'));
        $excel = new \PHPExcel();
        $excel = \PHPExcel_IOFactory::load($request->file('file'));
        
        $excel->setActiveSheetIndex(0);
        $excel = $excel->getActiveSheet();           
        $column_identifier = array("B", "D", "F", "G", "H", "I", "J", "W");
       
        if($excel->getCell('B10')->getValue() =="CBN"){
            DB::table('cashflow_cbn_placements')->delete();
        
            $row = $excel->getHighestRow();
            
            for ($i=10; $i <= $row; $i++) { 
                $table = new cashflowCbnPlacement();
                if ($this->DateFormat($excel->getCell($column_identifier[4].$i)->getValue()) >= $first_day_of_the_year) {
                    $table->cpty = $excel->getCell($column_identifier[0].$i)->getValue();
                    $table->trade_id = $excel->getCell($column_identifier[1].$i)->getValue();
                    $table->maturity_date = $this->DateFormat($excel->getCell($column_identifier[2].$i)->getValue());
                    $table->start_date =$this->DateFormat($excel->getCell($column_identifier[3].$i)->getValue());
                    $table->end_date = $this->DateFormat($excel->getCell($column_identifier[4].$i)->getValue());
                    $table->rate = $excel->getCell($column_identifier[5].$i)->getValue();
                    $table->open_nominal = $excel->getCell($column_identifier[6].$i)->getValue();
                    $table->cashflow_days = $excel->getCell($column_identifier[7].$i)->getValue();
                    $table->save();
                }
            }  
            Session::flash('success','Upload was Successfull');           
            return back();
        }   else{
            Session::flash('error','Please Select the Right Cashflow File as Indicated by the Label');
            return back();
        }
        
    }

    
}