<?php

namespace App\Http\Controllers;

use App\Diesel;
use App\DieselItem;
use App\Record;
use App\Renewal;
use App\Type;
use Carbon\Carbon;
use Excel;

use App\PreMaintenance;
use App\Machinery;
use App\WorkOrder;
use App\DieselUsageExport;

class ReportController extends Controller
{
    public function index()
    {
        return view('report');
    }

    public function woReport()
    {
        $data = array();

        $workorders = WorkOrder::all();

        array_push($data,
            ["COMPANY", "MACHINES", "DOWN SINCE (DATE)", "VARIANCE DAYS FROM DOWN TILL NOW (DAYS)", "ESTIMATE BACK TO NORMAL ON (DATE)",
                "WORK DESCRIPTION", "SUPPLIER", "WORK STATUS", "AMOUNT  (RM)", "REMARK"]);

        foreach ($workorders as $workorder) {
            $machinery = Machinery::where('reg_no', $workorder->reg_no)->first();

            $company = $machinery ? $machinery->company->name : '';
            $machine = $machinery ? $machinery->model->name . ' ' . $machinery->reg_no : '';
            $down_since_date = Carbon::parse($workorder->create_time)->format('d M Y');
            $variance_days = Carbon::parse($workorder->create_time)->diffInDays(Carbon::now(), false);
            $estimate_time = Carbon::parse($workorder->estimate_time)->format('d M Y');
            $work_description = $workorder->title;
            $supplier = ' ';
            $work_status = strtoupper($workorder->status);
            $amount = 100;
            $remark = '';

            array_push($data, [$company, $machine, $down_since_date, $variance_days, $estimate_time, $work_description, $supplier, $work_status, $amount, $remark]);
        }

        ob_end_clean();

        Excel::create('W.O REPORT', function ($excel) use ($data) {
            $excel->sheet("Report", function ($sheet) use ($data) {
                $sheet->fromArray($data, null, 'A1', false, false);

                for ($intRowNumber = 1; $intRowNumber <= count($data) + 1; $intRowNumber++) {
                    $sheet->setSize('A' . $intRowNumber, 20, 30);
                    $sheet->setSize('B' . $intRowNumber, 20, 30);
                    $sheet->setSize('C' . $intRowNumber, 20, 30);
                    $sheet->setSize('D' . $intRowNumber, 20, 30);
                    $sheet->setSize('E' . $intRowNumber, 20, 30);
                    $sheet->setSize('F' . $intRowNumber, 20, 30);
                    $sheet->setSize('G' . $intRowNumber, 20, 30);
                    $sheet->setSize('H' . $intRowNumber, 20, 30);
                    $sheet->setSize('I' . $intRowNumber, 20, 30);
                    $sheet->setSize('J' . $intRowNumber, 20, 30);
                }
            });
        })->download('xlsx');
    }

    public function upcomingPMReport()
    {
        $data = array();

        $types = Type::all();

        array_push($data,
            ["NO", "Type of Machineries", "", "Possible Working Units", "", "Starting Working Units", "",
                "Ending Working Hours", "", "SMU (Hr/Km) (a)", "", "Utilization (%)", "Total Diesel Usage (Litre) (b)", "Ideal Fuel Consumption", "",
                "Actual Fuel Consumption (b) / (a)", "", "Variance between actual & ideal (%)", "In Time / Late", "Remaining SMU Hrs", "Comment"]);

        $index = 1;
        foreach ($types as $type) {
            $machineries = Machinery::where('type_id', $type->id)->get();

            if (count($machineries) > 0) {
                foreach ($machineries as $machinery) {
                    $no = $index++;
                    $type_model = $machinery->type->name . ' ' . $machinery->model->name;
                    $reg_no = $machinery->reg_no;

                    $premaintenance = PreMaintenance::where('reg_no', $machinery->id)->first();

                    $possible_working = $premaintenance ? $premaintenance->routine_service : 0;
                    $possible_working_unit = $premaintenance ? $premaintenance->service_unit : 'km';

                    $record = Record::where('reg_no', $machinery->reg_no)->first();

                    $starting_working = $record ? $record->last_meter : 0;
                    $starting_working_unit = $possible_working_unit;
                    $ending_working = $record ? $record->current_meter : 0;
                    $ending_working_unit = $possible_working_unit;
                    $smu = $ending_working - $starting_working;
                    $smu_unit = $possible_working_unit;
                    $utilization = sprintf("%.2f", $smu * 100 / ($possible_working == 0 ? 1 : $possible_working));

                    $diesel = Diesel::where('reg_no', $machinery->reg_no)->first();

                    $total_diesel = $diesel ? $diesel->litres : 0;
                    $ideal_fuel_consumption_val = 0.14;
                    $ideal_fuel_consumption = '< ' . $ideal_fuel_consumption_val;
                    $ideal_fuel_consumption_unit = 'L/KM';
                    $actual_fuel_consumption_val = $total_diesel / ($smu == 0 ? 1 : $smu);
                    $actual_fuel_consumption = sprintf("%.2f", $total_diesel / ($smu == 0 ? 1 : $smu));
                    $actual_fuel_consumption_unit = 'L/KM';
                    $variance_val = ($actual_fuel_consumption_val - $ideal_fuel_consumption_val) * 100 / $ideal_fuel_consumption_val;
                    $variance = sprintf("%.2f", $variance_val);
                    $intime = 'In Time';
                    $remaining_smu_hrs = random_int(1000, 9999);
                    $comment = '';

                    array_push($data, [$no, $type_model, $reg_no, $possible_working, $possible_working_unit, $starting_working, $starting_working_unit,
                        $ending_working, $ending_working_unit, $smu, $smu_unit, $utilization, $total_diesel, $ideal_fuel_consumption, $ideal_fuel_consumption_unit,
                        $actual_fuel_consumption, $actual_fuel_consumption_unit, $variance, $intime, $remaining_smu_hrs, $comment]);
                }
            }
        }

        ob_end_clean();

        Excel::create('UPCOMING P.M REPORT', function ($excel) use ($data) {
            $excel->sheet("Report", function ($sheet) use ($data) {
                $sheet->fromArray($data, null, 'A1', false, false);

                for ($intRowNumber = 1; $intRowNumber <= count($data) + 1; $intRowNumber++) {
                    $sheet->setSize('A' . $intRowNumber, 20, 30);
                    $sheet->setSize('B' . $intRowNumber, 20, 30);
                    $sheet->setSize('C' . $intRowNumber, 20, 30);
                    $sheet->setSize('D' . $intRowNumber, 20, 30);
                    $sheet->setSize('E' . $intRowNumber, 20, 30);
                    $sheet->setSize('F' . $intRowNumber, 20, 30);
                    $sheet->setSize('G' . $intRowNumber, 20, 30);
                    $sheet->setSize('H' . $intRowNumber, 20, 30);
                    $sheet->setSize('I' . $intRowNumber, 20, 30);
                    $sheet->setSize('J' . $intRowNumber, 20, 30);
                    $sheet->setSize('K' . $intRowNumber, 20, 30);
                    $sheet->setSize('L' . $intRowNumber, 20, 30);
                    $sheet->setSize('M' . $intRowNumber, 20, 30);
                    $sheet->setSize('N' . $intRowNumber, 20, 30);
                    $sheet->setSize('O' . $intRowNumber, 20, 30);
                    $sheet->setSize('P' . $intRowNumber, 20, 30);
                    $sheet->setSize('Q' . $intRowNumber, 20, 30);
                    $sheet->setSize('R' . $intRowNumber, 20, 30);
                    $sheet->setSize('S' . $intRowNumber, 20, 30);
                    $sheet->setSize('T' . $intRowNumber, 20, 30);
                    $sheet->setSize('U' . $intRowNumber, 20, 30);
                }
            });
        })->download('xlsx');
    }

    public function upcomingRenewalReport()
    {
        $data = array();

        $renewals = Renewal::all();

        array_push($data, ["COMPANY", "REG NO.", "ROAD TAX", "INSURANCE", "PUSPAKOM", "ROAD TAX", "INSURANCE", "PUSPAKOM"]);

        foreach ($renewals as $renewal) {
            $machinery = Machinery::where('reg_no', $renewal->reg_no)->first();

            $company = $machinery ? $machinery->company->name : '';
            $reg_no = $renewal->reg_no;

            if ($renewal->road_tax_last_renewal) {
                $road_tax = Carbon::parse($renewal->road_tax_last_renewal)->addMonthNoOverflow($renewal->road_tax_routine)->format('d-M-Y');
                $road_tax_variance = Carbon::now()->diffInDays(Carbon::parse($renewal->road_tax_last_renewal)->addMonthNoOverflow($renewal->road_tax_routine), false);
            } else {
                $road_tax = '-';
                $road_tax_variance = '-';
            }

            if ($renewal->insurance_last_renewal) {
                $insurance = Carbon::parse($renewal->insurance_last_renewal)->addMonthNoOverflow($renewal->insurance_routine)->format('d-M-Y');
                $insurance_variance = Carbon::now()->diffInDays(Carbon::parse($renewal->insurance_last_renewal)->addMonthNoOverflow($renewal->insurance_routine), false);
            } else {
                $insurance = '-';
                $insurance_variance = '-';
            }


            if ($renewal->puspakom_last_renewal) {
                $puspakom = Carbon::parse($renewal->puspakom_last_renewal)->addMonthNoOverflow($renewal->puspakom_routine)->format('d-M-Y');
                $puspakom_variance = Carbon::now()->diffInDays(Carbon::parse($renewal->puspakom_last_renewal)->addMonthNoOverflow($renewal->puspakom_routine), false);
            } else {
                $puspakom = '-';
                $puspakom_variance = '-';
            }

            array_push($data, [$company, $reg_no, $road_tax, $insurance, $puspakom, $road_tax_variance, $insurance_variance, $puspakom_variance]);
        }

        ob_end_clean();

        Excel::create('UPCOMING RENEWAL REPORT', function ($excel) use ($data) {
            $excel->sheet("Report", function ($sheet) use ($data) {
                $sheet->fromArray($data, null, 'A1', false, false);

                for ($intRowNumber = 1; $intRowNumber <= count($data) + 1; $intRowNumber++) {
                    $sheet->setSize('A' . $intRowNumber, 20, 30);
                    $sheet->setSize('B' . $intRowNumber, 20, 30);
                    $sheet->setSize('C' . $intRowNumber, 20, 30);
                    $sheet->setSize('D' . $intRowNumber, 20, 30);
                    $sheet->setSize('E' . $intRowNumber, 20, 30);
                    $sheet->setSize('F' . $intRowNumber, 20, 30);
                    $sheet->setSize('G' . $intRowNumber, 20, 30);
                    $sheet->setSize('H' . $intRowNumber, 20, 30);
                }
            });
        })->download('xlsx');
    }

    public function expenseReport()
    {
        $data = array();

        $machineries = Machinery::all();

        $headers = ['Diesel Masuk(Litre)', 'Tarikh'];
        $footers = ['', 'Jumlah'];

        foreach ($machineries as $machinery) {
            array_push($headers, $machinery->company->name . ' ' . $machinery->model->name . ' ' . $machinery->reg_no);
            array_push($footers, 0);
        }

        array_push($headers, 'Jumlah Harian', '', 'Balance', '');
        array_push($footers, 0, 'Litre', 0, 'Litre');

        array_push($data, $headers);
        ob_end_clean();

        $totalBalance = 300;

        $nowDate = Carbon::now();
        $daysInMonth = $nowDate->daysInMonth;

        $balanceInDay = $totalBalance;

        for ($i = 0; $i < $daysInMonth; $i++) {
            $rowData = array();

            array_push($rowData, '', $i);
            $date = Carbon::now()->setDate($nowDate->year, $nowDate->month, ($i + 1));

            $dieselUsedInDay = 0;
            foreach ($machineries as $key => $machinery) {
                $diesel = Diesel::where('reg_no', $machinery->reg_no)->first();

                if ($diesel) {
                    $dieselUsed = DieselItem::where('diesel_id', $diesel->id)->where('create_date', $date->format('Y-m-d'))->sum('litre');
                    $dieselUsedInDay += $dieselUsed;
                    $footers[2 + $key] += $dieselUsed ? $dieselUsed : 0;
                    $footers[2 + count($machineries) ] += $dieselUsedInDay;

                    array_push($rowData, $dieselUsed ? $dieselUsed : '');
                } else {
                    array_push($rowData, '');
                }
            }

            $balanceInDay -= $dieselUsedInDay;
            array_push($rowData, $dieselUsedInDay, 'Litre', $balanceInDay, 'Litre');

            array_push($data, $rowData);
        }

        $footers[4 + count($machineries) ] = $balanceInDay;

        array_push($data, $footers);

        Excel::create('EXPENSE REPORT', function ($excel) use ($data, $headers) {
            $excel->sheet("Report", function ($sheet) use ($data, $headers) {
                $sheet->fromArray($data, null, 'A1', false, false);

                for ($intRowNumber = 1; $intRowNumber <= count($data) + 1; $intRowNumber++) {
                    for ($intColNumber = 0; $intColNumber < count($headers); $intColNumber++) {
                        $sheet->setSize(chr(65 + $intColNumber) . $intRowNumber, 20, 30);
                    }
                }
            });
        })->download('xlsx');
    }

    public function dieselUsageReport() {
        ob_end_clean();

        $data = [];
      Excel::create('EXPENSE REPORT', function ($excel) use ($data) {
            $excel->sheet("Report", function ($sheet) use ($data) {
                $sheet->loadView('home', [], []);
            });
        })->download('xlsx');
    }
}
