<?php

use App\ReportCall;
use App\ReportDurationCall;
use App\ReportWaitCall;

if(!function_exists('add_report_call')) {
    function add_report_call($call) {
        $company_id = $call->company_id;
        $year = date('Y', strtotime($call->start));
        $month = date('m', strtotime($call->start));
        $day = date('d', strtotime($call->start));
        $hour = date('d', strtotime($call->start));
        $call_type_id = $call->call_type_id;
        $call_status_id = $call->call_status_id;
        if(!empty($call->call_end_id)) {
            $call_end_id = $call->call_end_id;
        } else {
            $call_end_id = null;
        }
        if(!empty($call->ddi_id)) {
            $ddi_id = $call->ddi_id;
        } else {
            $ddi_id = null;
        }
        
        //RC: miramos si tenemos el registro
        $report_call_query = ReportCall::where('company_id', $company_id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('day', $hour)
            ->where('day', $day)
            ->where('hour', $hour)
            ->where('call_type_id', $call_type_id)
            ->where('call_status_id', $call_status_id);

        if($call_end_id) {
            $report_call_query->where('call_end_id', $call_end_id);
        } else {
            $report_call_query->whereNull('call_end_id');
        }

        if($ddi_id) {
            $report_call_query->where('ddi_id', $ddi_id);
        } else {
            $report_call_query->whereNull('ddi_id');
        }

        $report_call = $report_call_query->first();

        if(!empty($report_call)) {
            $report_call->total++;
            $report_call->duration += $call->duration;
            $report_call->save();
        } else {
            $report_call = new ReportCall();
            $report_call->company_id = $company_id;
            $report_call->year = $year;
            $report_call->month = $month;
            $report_call->day = $day;
            $report_call->hour = $hour;
            $report_call->call_type_id = $call_type_id;
            $report_call->call_status_id = $call_status_id;
            $report_call->call_end_id = $call_end_id;
            $report_call->ddi_id = $ddi_id;
            $report_call->total = 1;
            $report_call->duration = $call->duration;
            $report_call->save();
        }

    }
}

if(!function_exists('remove_report_call')) {
    function remove_report_call($call) {
        $company_id = $call->company_id;
        $year = date('Y', strtotime($call->start));
        $month = date('m', strtotime($call->start));
        $day = date('d', strtotime($call->start));
        $hour = date('d', strtotime($call->start));
        $call_type_id = $call->call_type_id;
        $call_status_id = $call->call_status_id;
        if(!empty($call->call_end_id)) {
            $call_end_id = $call->call_end_id;
        } else {
            $call_end_id = null;
        }
        if(!empty($call->ddi_id)) {
            $ddi_id = $call->ddi_id;
        } else {
            $ddi_id = null;
        }
        
        //RC: miramos si tenemos el registro
        $report_call_query = ReportCall::where('company_id', $company_id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('day', $hour)
            ->where('day', $day)
            ->where('hour', $hour)
            ->where('call_type_id', $call_type_id)
            ->where('call_status_id', $call_status_id);

        if($call_end_id) {
            $report_call_query->where('call_end_id', $call_end_id);
        } else {
            $report_call_query->whereNull('call_end_id');
        }

        if($ddi_id) {
            $report_call_query->where('ddi_id', $ddi_id);
        } else {
            $report_call_query->whereNull('ddi_id');
        }

        $report_call = $report_call_query->first();

        if(!empty($report_call)) {
            $report_call->total--;
            if($report_call->total < 0) {
                $report_call->total = 0;
            }

            $report_call->duration -= $call->duration;
            if($report_call->duration < 0) {
                $report_call->duration = 0;
            }
            $report_call->save();
        }
    }
}

if(!function_exists('add_report_duration_call')) {
    function add_report_duration_call($call) {
        $company_id = $call->company_id;
        $year = date('Y', strtotime($call->start));
        $month = date('m', strtotime($call->start));
        $day = date('d', strtotime($call->start));
        $hour = date('d', strtotime($call->start));
        $call_type_id = $call->call_type_id;
        $call_status_id = $call->call_status_id;
        if(!empty($call->call_end_id)) {
            $call_end_id = $call->call_end_id;
        } else {
            $call_end_id = null;
        }
        if(!empty($call->ddi_id)) {
            $ddi_id = $call->ddi_id;
        } else {
            $ddi_id = null;
        }
        $range = ceil($call->duration);
        if($range > 600) {
            $range = 600;
        }
        
        //RC: miramos si tenemos el registro
        $report_call_query = ReportDurationCall::where('company_id', $company_id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('day', $hour)
            ->where('day', $day)
            ->where('hour', $hour)
            ->where('call_type_id', $call_type_id)
            ->where('call_status_id', $call_status_id);

        if($call_end_id) {
            $report_call_query->where('call_end_id', $call_end_id);
        } else {
            $report_call_query->whereNull('call_end_id');
        }

        if($ddi_id) {
            $report_call_query->where('ddi_id', $ddi_id);
        } else {
            $report_call_query->whereNull('ddi_id');
        }

        $report_call_query->where('range', $range);

        $report_call = $report_call_query->first();

        if(!empty($report_call)) {
            $report_call->total++;
            $report_call->save();
        } else {
            $report_call = new ReportDurationCall();
            $report_call->company_id = $company_id;
            $report_call->year = $year;
            $report_call->month = $month;
            $report_call->day = $day;
            $report_call->hour = $hour;
            $report_call->call_type_id = $call_type_id;
            $report_call->call_status_id = $call_status_id;
            $report_call->call_end_id = $call_end_id;
            $report_call->ddi_id = $ddi_id;
            $report_call->range = $range;
            $report_call->total = 1;
            $report_call->save();
        }

    }
}

if(!function_exists('remove_report_duration_call')) {
    function remove_report_duration_call($call) {
        $company_id = $call->company_id;
        $year = date('Y', strtotime($call->start));
        $month = date('m', strtotime($call->start));
        $day = date('d', strtotime($call->start));
        $hour = date('d', strtotime($call->start));
        $call_type_id = $call->call_type_id;
        $call_status_id = $call->call_status_id;
        if(!empty($call->call_end_id)) {
            $call_end_id = $call->call_end_id;
        } else {
            $call_end_id = null;
        }
        if(!empty($call->ddi_id)) {
            $ddi_id = $call->ddi_id;
        } else {
            $ddi_id = null;
        }

        $range = ceil($call->duration);
        if($range > 600) {
            $range = 600;
        }
        
        //RC: miramos si tenemos el registro
        $report_call_query = ReportDurationCall::where('company_id', $company_id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('day', $hour)
            ->where('day', $day)
            ->where('hour', $hour)
            ->where('call_type_id', $call_type_id)
            ->where('call_status_id', $call_status_id);

        if($call_end_id) {
            $report_call_query->where('call_end_id', $call_end_id);
        } else {
            $report_call_query->whereNull('call_end_id');
        }

        if($ddi_id) {
            $report_call_query->where('ddi_id', $ddi_id);
        } else {
            $report_call_query->whereNull('ddi_id');
        }

        $report_call_query->where('range', $range);

        $report_call = $report_call_query->first();

        if(!empty($report_call)) {
            $report_call->total--;
            if($report_call->total < 0) {
                $report_call->total = 0;
            }
            $report_call->save();
        }
    }
}

if(!function_exists('add_report_wait_call')) {
    function add_report_wait_call($call) {
        $company_id = $call->company_id;
        $year = date('Y', strtotime($call->start));
        $month = date('m', strtotime($call->start));
        $day = date('d', strtotime($call->start));
        $hour = date('d', strtotime($call->start));
        $call_type_id = $call->call_type_id;
        $call_status_id = $call->call_status_id;
        if(!empty($call->call_end_id)) {
            $call_end_id = $call->call_end_id;
        } else {
            $call_end_id = null;
        }
        if(!empty($call->ddi_id)) {
            $ddi_id = $call->ddi_id;
        } else {
            $ddi_id = null;
        }
        $range = ceil($call->duration_wait);
        if($range > 90) {
            $range = 90;
        }
        
        //RC: miramos si tenemos el registro
        $report_call_query = ReportWaitCall::where('company_id', $company_id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('day', $hour)
            ->where('day', $day)
            ->where('hour', $hour)
            ->where('call_type_id', $call_type_id)
            ->where('call_status_id', $call_status_id);

        if($call_end_id) {
            $report_call_query->where('call_end_id', $call_end_id);
        } else {
            $report_call_query->whereNull('call_end_id');
        }

        if($ddi_id) {
            $report_call_query->where('ddi_id', $ddi_id);
        } else {
            $report_call_query->whereNull('ddi_id');
        }

        $report_call_query->where('range', $range);

        $report_call = $report_call_query->first();

        if(!empty($report_call)) {
            $report_call->total++;
            $report_call->save();
        } else {
            $report_call = new ReportWaitCall();
            $report_call->company_id = $company_id;
            $report_call->year = $year;
            $report_call->month = $month;
            $report_call->day = $day;
            $report_call->hour = $hour;
            $report_call->call_type_id = $call_type_id;
            $report_call->call_status_id = $call_status_id;
            $report_call->call_end_id = $call_end_id;
            $report_call->ddi_id = $ddi_id;
            $report_call->range = $range;
            $report_call->total = 1;
            $report_call->save();
        }

    }
}

if(!function_exists('remove_report_wait_call')) {
    function remove_report_wait_call($call) {
        $company_id = $call->company_id;
        $year = date('Y', strtotime($call->start));
        $month = date('m', strtotime($call->start));
        $day = date('d', strtotime($call->start));
        $hour = date('d', strtotime($call->start));
        $call_status_id = $call->call_status_id;
        if(!empty($call->call_end_id)) {
            $call_end_id = $call->call_end_id;
        } else {
            $call_end_id = null;
        }
        if(!empty($call->ddi_id)) {
            $ddi_id = $call->ddi_id;
        } else {
            $ddi_id = null;
        }

        $range = ceil($call->duration_wait);
        if($range > 90) {
            $range = 90;
        }
        
        //RC: miramos si tenemos el registro
        $report_call_query = ReportWaitCall::where('company_id', $company_id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('day', $hour)
            ->where('day', $day)
            ->where('hour', $hour)
            ->where('call_status_id', $call_status_id);

        if($call_end_id) {
            $report_call_query->where('call_end_id', $call_end_id);
        } else {
            $report_call_query->whereNull('call_end_id');
        }

        if($ddi_id) {
            $report_call_query->where('ddi_id', $ddi_id);
        } else {
            $report_call_query->whereNull('ddi_id');
        }

        $report_call_query->where('range', $range);

        $report_call = $report_call_query->first();

        if(!empty($report_call)) {
            $report_call->total--;
            if($report_call->total < 0) {
                $report_call->total = 0;
            }
            $report_call->save();
        }
    }
}