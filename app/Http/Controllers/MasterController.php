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

    public function generateStockPolicy()
    {

        $now = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+14 day'));

        $policies = DB::select("
                SELECT vendor_materials.material_number, vendor_materials.material_description, COALESCE(mrp.`usage`,0) AS `usage`
                FROM vendor_materials
                LEFT JOIN
                (SELECT vendor_boms.vendor_material_number, vendor_boms.vendor_material_description, SUM(target.quantity * vendor_boms.`usage`) AS `usage` FROM
                (SELECT material_number, SUM(plan) AS quantity FROM vendor_plan_deliveries
                WHERE due_date >= '" . $now . "'
                AND due_date <= '" . $end . "'
                GROUP BY material_number) AS target
                LEFT JOIN vendor_boms ON target.material_number = vendor_boms.ympi_material_number
                GROUP BY vendor_boms.vendor_material_number, vendor_boms.vendor_material_description) AS mrp
                ON vendor_materials.material_number = mrp.vendor_material_number
                WHERE vendor_materials.category = 'SUPPORTING MATERIAL'");

        DB::beginTransaction();
        $count_updated = 0;
        for ($i = 0; $i < count($policies); $i++) {
            try {
                $update = DB::table('vendor_materials')
                    ->where('material_number', $policies[$i]->material_number)
                    ->update([
                        'policy' => $policies[$i]->usage,
                    ]);

                $count_updated += $update;

            } catch (\Throwable$th) {
                DB::rollback();
                $response = array(
                    'status' => false,
                    'message' => $e->getMessage(),
                );
                return Response::json($response);

            }

        }

        DB::commit();
        $response = array(
            'status' => true,
            'count_updated' => $count_updated,
        );
        return Response::json($response);

    }

    public function generatePlanDeliveryData()
    {

        $response = array(
            'status' => true,
            'rows' => 1,
        );
        return Response::json($response);

    }

    public function generateForecast()
    {

        $response = array(
            'status' => true,
            'rows' => 1,
        );
        return Response::json($response);

    }

}
