<?php
namespace App\Http\Controllers;

ini_set('max_execution_time','300');

use App\cashflowSubsidiaryPlacementsGbp;
use App\cashflowSubsidiaryPlacementsUsd;
use App\InterestIncomeSub;
use Session;
use App\variables;
use Illuminate\Http\Request;
use App\Imports\UsersImport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use DateTime;
  
class PSUB extends Controller
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

    public function GetIntTenor($start_date, $reporting_date, $end_date)
    {
        $start_year = date('Y', strtotime($start_date));
        $last_year = date("Y") - 1;
        $first_day_of_the_year = date('Y-m-d', strtotime('first day of January this year'));
        if ($end_date <= $reporting_date) {
            if ($start_year == $last_year) {
                $diff = abs(strtotime($end_date)) - strtotime('first day of January this year');
                $int_tenor = $diff/(60*60*24);
            }else{
                $diff = abs(strtotime($end_date)) - abs(strtotime($start_date));
                $int_tenor = $diff/(60*60*24);
            }
        } else{
            if ($start_year == $last_year) {
                $diff = abs(strtotime($reporting_date)) - strtotime('first day of January this year');
                $int_tenor = ($diff/(60*60*24))+1;
            } else{
                $diff = abs(strtotime($reporting_date)) - abs(strtotime($start_date));
                $int_tenor = ($diff/(60*60*24)) +1;
            }
        }
        return $int_tenor;
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

        $table = new cashflowSubsidiaryPlacementsGbp();
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
            ->setCellvalue('J'.$i, $this->GetIntTenor($data->start_date, $reporting_date, $data->end_date))
            ->setCellvalue('K'.$i, $this->GetInterest($data->open_nominal, $this->GetIntTenor($data->start_date, $reporting_date, $data->end_date), doubleval($data->rate), $data->end_date))
            ->setCellvalue('L'.$i, $this->GetStatus($data->end_date, $reporting_date));
            $i++;
        }
        $sum_cell = $i + 2;

        
        //FOOTER SUMS
        $excel->getActiveSheet()
            ->setCellvalue('H'.$sum_cell, '=SUMIF(L13:L'.$i.', "Active", H13:H'.$i.')')
            ->setCellvalue('I'.$sum_cell, '=SUMIF(L13:L'.$i.', "Active", I13:I'.$i.')')
            ->setCellvalue('K'.$sum_cell, '=SUM(K13:K'.$i.')');
        
        $gbp = $excel->getActiveSheet()->getCell('K'.$sum_cell)->getCalculatedValue();
    
       
        //THE HEADER STYLES & TEXTS

        $excel->getActiveSheet()
            ->getStyle('A1:L10000')->applyFromArray(
                array(
                    'fill'=>array(
                        'type'=>\PHPExcel_Style_Fill::FILL_SOLID,
                        'color'=>array('rgb'=>'B9CEA4')
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
            $excel->getActiveSheet()->getColumnDimension('K')->setWidth('18');       
            $excel->getActiveSheet()->getColumnDimension('L')->setWidth('15');   

            $excel->getActiveSheet()->mergeCells('A6:C6');
           
            $excel->getActiveSheet()
            ->setCellvalue('A6', 'PLACEMENT WITH SUBSIDIARIES')
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
            ->setCellvalue('J12', 'Int. Income Tenor')
            ->setCellvalue('K12', 'Interest Income')
            ->setCellvalue('L12', 'Status');

            $excel->getActiveSheet()
                ->setCellvalue('H11', '1025FCY')
                ->setCellvalue('I11', '2185FCY');         
                
            $excel->getActiveSheet()
                ->getStyle('A1:L12')->applyFromArray(
                    array(
                        'font'=>array(
                            'bold'=>true,
                            'size'=>11,
                        )
                    )
                );

            $excel->getActiveSheet()
            ->getStyle('C11:L11')->applyFromArray(
                array(
                    'font'=>array(
                        'size'=>10,
                    )
                )
            );

            $excel->getActiveSheet()
            ->getStyle('A13:L10000')->applyFromArray(
                array(
                    'font'=>array(
                        'size'=>10,
                    )
                )
            );
            $excel->getActiveSheet()->getStyle('A11:L10000')->getAlignment()->setHorizontal('right');
          
           
            $excel->getActiveSheet()
                ->getStyle('H'.$sum_cell.':L'.$sum_cell)->applyFromArray(
                    array(
                        'font'=>array(
                            'bold'=>true,
                        )
                    )
                );
            $excel->getActiveSheet()->getStyle('H13:I'.$sum_cell)->getNumberFormat()->setFormatCode('#,##0.00');
            $excel->getActiveSheet()->getStyle('K13:K'.$sum_cell)->getNumberFormat()->setFormatCode('#,##0.00');
        
            $excel->getActiveSheet()->freezePane('M13');
            $excel->getActiveSheet()->setTitle('GBP');
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
        

        //SHEET 2 STARTS HERE
        $excel->createSheet();       
        $excel->setActiveSheetIndex(1);

        $table = new cashflowSubsidiaryPlacementsUsd();
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
            ->setCellvalue('J'.$i, $this->GetIntTenor($data->start_date, $reporting_date, $data->end_date))
            ->setCellvalue('K'.$i, $this->GetInterest($data->open_nominal, $this->GetIntTenor($data->start_date, $reporting_date, $data->end_date), doubleval($data->rate), $data->end_date))
            ->setCellvalue('L'.$i, $this->GetStatus($data->end_date, $reporting_date));
            $i++;
        }
        $sum_cell = $i + 2;

        
        //FOOTER SUMS
        $excel->getActiveSheet()
            ->setCellvalue('H'.$sum_cell, '=SUMIF(L13:L'.$i.', "Active", H13:H'.$i.')')
            ->setCellvalue('I'.$sum_cell, '=SUMIF(L13:L'.$i.', "Active", I13:I'.$i.')')
            ->setCellvalue('K'.$sum_cell, '=SUM(K13:K'.$i.')');
            
        $usd = $excel->getActiveSheet()->getCell('K'.$sum_cell)->getCalculatedValue();

        $table = new InterestIncomeSub();
        $date_search = $table->where('reporting_date', '=', $reporting_date)->delete();
        $table->gbp = $gbp;
        $table->usd = $usd;
        $table->class = "Subsidiary Placement";
        $table->reporting_date = $reporting_date;
        $table->save();


        //THE HEADER STYLES & TEXTS

        $excel->getActiveSheet()
            ->getStyle('A1:L10000')->applyFromArray(
                array(
                    'fill'=>array(
                        'type'=>\PHPExcel_Style_Fill::FILL_SOLID,
                        'color'=>array('rgb'=>'B9CEA4')
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
            $excel->getActiveSheet()->getColumnDimension('K')->setWidth('18');       
            $excel->getActiveSheet()->getColumnDimension('L')->setWidth('15');   

            $excel->getActiveSheet()->mergeCells('A6:C6');
           
            $excel->getActiveSheet()
            ->setCellvalue('A6', 'PLACEMENT WITH SUBSIDIARIES')
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
            ->setCellvalue('J12', 'Int. Income Tenor')
            ->setCellvalue('K12', 'Interest Income')
            ->setCellvalue('L12', 'Status');

            $excel->getActiveSheet()
                ->setCellvalue('H11', '1025FCY')
                ->setCellvalue('I11', '2185FCY');         
                
            $excel->getActiveSheet()
                ->getStyle('A1:L12')->applyFromArray(
                    array(
                        'font'=>array(
                            'bold'=>true,
                            'size'=>11,
                        )
                    )
                );

            $excel->getActiveSheet()
            ->getStyle('C11:L11')->applyFromArray(
                array(
                    'font'=>array(
                        'size'=>10,
                    )
                )
            );

            $excel->getActiveSheet()
            ->getStyle('A13:L10000')->applyFromArray(
                array(
                    'font'=>array(
                        'size'=>10,
                    )
                )
            );
            $excel->getActiveSheet()->getStyle('A11:L10000')->getAlignment()->setHorizontal('right');
          
           
            $excel->getActiveSheet()
                ->getStyle('H'.$sum_cell.':L'.$sum_cell)->applyFromArray(
                    array(
                        'font'=>array(
                            'bold'=>true,
                        )
                    )
                );
            $excel->getActiveSheet()->getStyle('H13:I'.$sum_cell)->getNumberFormat()->setFormatCode('#,##0.00');
            $excel->getActiveSheet()->getStyle('K13:K'.$sum_cell)->getNumberFormat()->setFormatCode('#,##0.00');
        
            $excel->getActiveSheet()->freezePane('M13');
            $excel->getActiveSheet()->setTitle('USD');

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



        //SHEET 3 FOR INCOME PROOF STARTS HERE
        
        $excel->createSheet();       
        $excel->setActiveSheetIndex(2);

        $table = new cashflowSubsidiaryPlacementsUsd();
        $data= $table->where('end_date','>=',$first_day_of_the_year)->where('start_date','<=',$reporting_date)->orderBy('start_date', 'asc')->get();
        $i = 13;

        $month_index = date("m",strtotime($reporting_date)) -1;
        $months = array('January', 'February','March','April','May','June','July','August','September','October','November','December');
        $current_month = $months[$month_index];
        //$last_day_of_current_month = date('Y-m-d', strtotime('last day of '.$current_month));
        $previous_month = $months[$month_index-1];
        $last_day_of_previous_month = date('Y-m-d', strtotime('last day of '.$previous_month));
        $current = abs(strtotime($reporting_date));
        $previous = abs(strtotime('last day of '.$previous_month));
        $diff =  $current - $previous;
        $num_of_days_MTD = ($diff/(60*60*24));
        
        
        //CREATE ROOM FOR PUBLIC HOLIDAYS FOR CHECK, SO THAT INTERST IS NOT RECOGNIZED, IT SHOULD BE EDITABLE FROM FRONT-END
        $range = range($previous, $current,86400);
       
        foreach ($range as $key => $value) {
            dd(date('D', $value));       
        }
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
            ->setCellvalue('J'.$i, $this->GetIntTenor($data->start_date, $reporting_date, $data->end_date))
            ->setCellvalue('K'.$i, $this->GetInterest($data->open_nominal, $this->GetIntTenor($data->start_date, $reporting_date, $data->end_date), doubleval($data->rate), $data->end_date))
            ->setCellvalue('L'.$i, $this->GetStatus($data->end_date, $reporting_date));
            $i++;
        }
        $sum_cell = $i + 2;

        
        //FOOTER SUMS
        $excel->getActiveSheet()
            ->setCellvalue('H'.$sum_cell, '=SUMIF(L13:L'.$i.', "Active", H13:H'.$i.')')
            ->setCellvalue('I'.$sum_cell, '=SUMIF(L13:L'.$i.', "Active", I13:I'.$i.')')
            ->setCellvalue('K'.$sum_cell, '=SUM(K13:K'.$i.')');
            
        $usd = $excel->getActiveSheet()->getCell('K'.$sum_cell)->getCalculatedValue();

        $table = new InterestIncomeSub();
        $date_search = $table->where('reporting_date', '=', $reporting_date)->delete();
        $table->gbp = $gbp;
        $table->usd = $usd;
        $table->class = "Subsidiary Placement";
        $table->reporting_date = $reporting_date;
        $table->save();


        //THE HEADER STYLES & TEXTS

        $excel->getActiveSheet()
            ->getStyle('A1:L10000')->applyFromArray(
                array(
                    'fill'=>array(
                        'type'=>\PHPExcel_Style_Fill::FILL_SOLID,
                        'color'=>array('rgb'=>'B9CEA4')
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
            $excel->getActiveSheet()->getColumnDimension('K')->setWidth('18');       
            $excel->getActiveSheet()->getColumnDimension('L')->setWidth('15');   

            $excel->getActiveSheet()->mergeCells('A6:C6');
           
            $excel->getActiveSheet()
            ->setCellvalue('A6', 'PLACEMENT WITH SUBSIDIARIES')
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
            ->setCellvalue('J12', 'Int. Income Tenor')
            ->setCellvalue('K12', 'Interest Income')
            ->setCellvalue('L12', 'Status');

            $excel->getActiveSheet()
                ->setCellvalue('H11', '1025FCY')
                ->setCellvalue('I11', '2185FCY');         
                
            $excel->getActiveSheet()
                ->getStyle('A1:L12')->applyFromArray(
                    array(
                        'font'=>array(
                            'bold'=>true,
                            'size'=>11,
                        )
                    )
                );

            $excel->getActiveSheet()
            ->getStyle('C11:L11')->applyFromArray(
                array(
                    'font'=>array(
                        'size'=>10,
                    )
                )
            );

            $excel->getActiveSheet()
            ->getStyle('A13:L10000')->applyFromArray(
                array(
                    'font'=>array(
                        'size'=>10,
                    )
                )
            );
            $excel->getActiveSheet()->getStyle('A11:L10000')->getAlignment()->setHorizontal('right');
          
           
            $excel->getActiveSheet()
                ->getStyle('H'.$sum_cell.':L'.$sum_cell)->applyFromArray(
                    array(
                        'font'=>array(
                            'bold'=>true,
                        )
                    )
                );
            $excel->getActiveSheet()->getStyle('H13:I'.$sum_cell)->getNumberFormat()->setFormatCode('#,##0.00');
            $excel->getActiveSheet()->getStyle('K13:K'.$sum_cell)->getNumberFormat()->setFormatCode('#,##0.00');
        
            $excel->getActiveSheet()->freezePane('M13');
            $excel->getActiveSheet()->setTitle('INTEREST INCOME PROOF');
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
        

        // //SHEET 3 STARTS HERE
        // $excel->createSheet();       
        // $excel->setActiveSheetIndex(2);
        // $month_index = date("m",strtotime($reporting_date)) -1;
        // $months = array('January', 'February','March','April','May','June','July','August','September','October','November','December');
        // $current_month = $months[$month_index];
        // $last_day_of_current_month = date('Y-m-d', strtotime('last day of '.$current_month));
        // $previous_month = $months[$month_index-1];
        // $last_day_of_previous_month = date('Y-m-d', strtotime('last day of '.$previous_month));
       
        // $table = new InterestIncomeSub();
        // $month_end_data = $table->where('reporting_date','<=',$last_day_of_previous_month)->orderBy('reporting_date', 'desc')->take(1)->get();
        // foreach ($month_end_data as $data) {
        //     $excel->getActiveSheet()
        //     ->setCellvalue('A7', $this->ExportDateFormat($data->reporting_date))
        //     ->setCellvalue('B7', $data->gbp)
        //     ->setCellvalue('G7', $this->ExportDateFormat($data->reporting_date))
        //     ->setCellvalue('H7', $data->usd);           
        // }
        // $data= $table->where('reporting_date','>',$last_day_of_previous_month)->where('reporting_date','<=',$last_day_of_current_month)->orderBy('reporting_date', 'asc')->get();
        // $i = 8;
        // $x = 7;
        // foreach ($data as $data) {
        //     $excel->getActiveSheet()
        //     ->setCellvalue('A'.$i, $this->ExportDateFormat($data->reporting_date))
        //     ->setCellvalue('B'.$i, $data->gbp)
        //     ->setCellvalue('C'.$i, '=B'.$i.'-B'.$x)
        //     ->setCellvalue('G'.$i, $this->ExportDateFormat($data->reporting_date))
        //     ->setCellvalue('H'.$i, $data->usd)
        //     ->setCellvalue('I'.$i, '=H'.$i.'-H'.$x);
           
        //     $i++;
        //     $x++;
        // }

        // $table = new variables();
        // $rates = $table->where('reporting_date','>',$last_day_of_previous_month)->where('reporting_date','<=',$last_day_of_current_month)->orderBy('reporting_date', 'asc')->get();
        // $i = 8;
        // foreach ($rates as $data) {
        //     $excel->getActiveSheet()
        //     ->setCellvalue('D'.$i, $data->gbp)
        //     ->setCellvalue('E'.$i, '=C'.$i.'*D'.$i)
        //     ->setCellvalue('J'.$i, $data->usd)
        //     ->setCellvalue('K'.$i, '=I'.$i.'*J'.$i)
        //     ->setCellvalue('L'.$i, '=E'.$i.'+K'.$i);
           
        //     $i++;
        // }
        
        
        // $sum_cell = $i + 2;
        
        // $excel->getActiveSheet()->setTitle('INTEREST INCOME PROOF');
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename= "Pacement with Subsidiary"'.$reporting_date.'".xlsx"');
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
        $variables = new variables();
        $reporting_date = $variables->orderBy('created_at', 'desc')->pluck('reporting_date')->first(); 
        $first_day_of_the_year = date('Y-m-d', strtotime('first day of January this year'));
        $excel = new \PHPExcel();
        $column_identifier = array("B", "D", "F", "G", "H", "I", "J", "W"); 
        
        //CHECK THE FILES FOR RIGHT CURRENCY
        //START CHECK
        $excel = \PHPExcel_IOFactory::load($request->file('file_1'));
        $excel->setActiveSheetIndex(0);
        $excel = $excel->getActiveSheet(); 
        if($excel->getCell('C10')->getValue() !="GBP") {
           session(['error_gbp'=>'Please Select the Right Cashflow File as Indicated by the Label']);
            return back();
        }  

        $excel = \PHPExcel_IOFactory::load($request->file('file_2'));
        $excel->setActiveSheetIndex(0);
        $excel = $excel->getActiveSheet(); 
        if($excel->getCell('C10')->getValue() != "USD") {
            session(['error_usd'=>'Please Select the Right Cashflow File as Indicated by the Label']);
            return back();
        }  
        //END CHECK
        
        //START IMPORT
        $excel = \PHPExcel_IOFactory::load($request->file('file_1'));
        $excel->setActiveSheetIndex(0);
        $excel = $excel->getActiveSheet(); 
        if($excel->getCell('C10')->getValue() =="GBP"){
            DB::table('cashflow_subsidiary_placements_gbp')->delete();
        
            $row = $excel->getHighestRow();
            
            for ($i=10; $i <= $row; $i++) { 
                $table = new cashflowSubsidiaryPlacementsGbp();
                if ($this->DateFormat($excel->getCell($column_identifier[4].$i)->getValue()) >= $first_day_of_the_year && $this->DateFormat($excel->getCell($column_identifier[3].$i)->getValue()) <= $reporting_date) {
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
        }


        $excel = \PHPExcel_IOFactory::load($request->file('file_2'));
        $excel->setActiveSheetIndex(0);
        $excel = $excel->getActiveSheet(); 
        $column_identifier = array("B", "D", "F", "G", "H", "I", "J", "W");         
        if($excel->getCell('C10')->getValue() =="USD"){
            DB::table('cashflow_subsidiary_placements_usd')->delete();
        
            $row = $excel->getHighestRow();
            
            for ($i=10; $i <= $row; $i++) { 
                $table = new cashflowSubsidiaryPlacementsUsd();
                if ($this->DateFormat($excel->getCell($column_identifier[4].$i)->getValue()) >= $first_day_of_the_year && $this->DateFormat($excel->getCell($column_identifier[3].$i)->getValue()) <= $reporting_date) {
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
        }
       
        //validate both files.
        
        Session::flash('success','Uploads were Successfull');          
        return back();
    }

    
}