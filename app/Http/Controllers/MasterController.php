<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Response;

class MasterController extends Controller
{

    public function fetchPlanDelivery()
    {

        $delivery = db::table('vendor_plan_deliveries')
            ->where('due_date', 'LIKE', '%' . date('Y-m') . '%')
            ->select(
                'material_number',
                'material_description',
                'due_date',
                db::raw('date_format(due_date, "%d-%b") AS date'),
                'plan',
                'actual'
            )
            ->get();

        $response = array(
            'status' => true,
            'delivery' => $delivery,
        );
        return Response::json($response);

    }

}
