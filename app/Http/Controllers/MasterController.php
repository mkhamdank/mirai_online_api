<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
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

    public function fetchDataQA()
    {

        $ng_list = DB::table('ng_lists')->where('synced_at',null)->get();
        $outgoing_crestec = DB::table('qa_outgoing_vendor_crestecs')->where('synced_at',null)->get();
        $outgoing = DB::table('qa_outgoing_vendors')->where('synced_at',null)->get();
        $outgoing_final = DB::table('qa_outgoing_vendor_finals')->where('synced_at',null)->get();
        $outgoing_recheck = DB::table('qa_outgoing_vendor_rechecks')->where('synced_at',null)->get();

        $response = array(
            'status' => true,
            'ng_list' => $ng_list,
            'outgoing_crestec' => $outgoing_crestec,
            'outgoing' => $outgoing,
            'outgoing_final' => $outgoing_final,
            'outgoing_recheck' => $outgoing_recheck,
            'sync_at' => date('Y-m-d H:i:s'),
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

                    } catch (\Throwable $th) {
                        DB::rollback();
                        $response = array(
                            'status' => false,
                            'message' => $th->getMessage(),
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

            public function getAuditMolding()
            {
                $molding_check = db::connection('mysql_new')->select("SELECT * from pe_molding_checks where sync_at is null");
                $molding_check_detail = db::connection('mysql_new')->select("SELECT * from pe_molding_check_details where sync_at is null");
                $molding_finding = db::connection('mysql_new')->select("SELECT * from pe_molding_findings where sync_at is null");
                $molding_handling = db::connection('mysql_new')->select("SELECT * from pe_molding_handlings where sync_at is null");

                db::connection('mysql_new')->table('pe_molding_checks')->whereNull('sync_at')
                ->update(['sync_at' => date('Y-m-d H:i:s')]);

                db::connection('mysql_new')->table('pe_molding_check_details')->whereNull('sync_at')
                ->update(['sync_at' => date('Y-m-d H:i:s')]);

                db::connection('mysql_new')->table('pe_molding_findings')->whereNull('sync_at')
                ->update(['sync_at' => date('Y-m-d H:i:s')]);

                db::connection('mysql_new')->table('pe_molding_handlings')->whereNull('sync_at')
                ->update(['sync_at' => date('Y-m-d H:i:s')]);


                $response = array(
                    'status' => true,
                    'molding_check' => $molding_check,
                    'molding_check_detail' => $molding_check_detail,
                    'molding_finding' => $molding_finding,
                    'molding_handling' => $molding_handling,
                    'sync_at' => date('Y-m-d H:i:s'),
                );
                return Response::json($response);
            }

            public function postAuditMolding(Request $request)
            {

                $request->get('molding_check');

                $update_check = db::table('pe_molding_checks')
                ->insert([
                    'check_date' => $sync2['molding_check'][$i]->check_date,
                    'molding_name' => $sync2['molding_check'][$i]->molding_name,
                    'molding_type' => $sync2['molding_check'][$i]->molding_type,
                    'location' => $sync2['molding_check'][$i]->location,
                    'pic' => $sync2['molding_check'][$i]->pic,
                    'conclusion' => $sync2['molding_check'][$i]->conclusion,
                    'status' => $sync2['molding_check'][$i]->status,
                    'remark' => $sync2['molding_check'][$i]->remark,
                    'sync_at' => $sync2['sync_at'],
                    'created_by' => $sync2['molding_check'][$i]->created_by,
                    'created_at' => $sync2['molding_check'][$i]->created_at,
                    'updated_at' => $sync2['molding_check'][$i]->updated_at,
                    'deleted_at' => $sync2['molding_check'][$i]->deleted_at,
                ]);

                $update_check = db::table('pe_molding_check_details')
                ->insert([
                    'check_id' => $sync2['molding_check_detail'][$i]->check_id,
                    'part_name' => $sync2['molding_check_detail'][$i]->part_name,
                    'point_check' => $sync2['molding_check_detail'][$i]->point_check,
                    'standard' => $sync2['molding_check_detail'][$i]->standard,
                    'how_check' => $sync2['molding_check_detail'][$i]->how_check,
                    'handle' => $sync2['molding_check_detail'][$i]->handle,
                    'photo_before1' => $sync2['molding_check_detail'][$i]->photo_before1,
                    'photo_before2' => $sync2['molding_check_detail'][$i]->photo_before2,
                    'photo_after1' => $sync2['molding_check_detail'][$i]->photo_after1,
                    'photo_after2' => $sync2['molding_check_detail'][$i]->photo_after2,
                    'photo_activity1' => $sync2['molding_check_detail'][$i]->photo_activity1,
                    'photo_activity2' => $sync2['molding_check_detail'][$i]->photo_activity2,
                    'judgement' => $sync2['molding_check_detail'][$i]->judgement,
                    'note' => $sync2['molding_check_detail'][$i]->note,
                    'status' => $sync2['molding_check_detail'][$i]->status,
                    'remark' => $sync2['molding_check_detail'][$i]->remark,
                    'sync_at' => $sync2['sync_at'],
                    'created_by' => $sync2['molding_check_detail'][$i]->created_by,
                    'created_at' => $sync2['molding_check_detail'][$i]->created_at,
                    'updated_at' => $sync2['molding_check_detail'][$i]->updated_at,
                    'deleted_at' => $sync2['molding_check_detail'][$i]->deleted_at,
                ]);

                $update_check = db::table('pe_molding_findings')
                ->insert([
                    'id' => $sync2['molding_finding'][$i]->id,
                    'check_id' => $sync2['molding_finding'][$i]->check_id,
                    'check_date' => $sync2['molding_finding'][$i]->check_date,
                    'pic' => $sync2['molding_finding'][$i]->pic,
                    'molding_name' => $sync2['molding_finding'][$i]->molding_name,
                    'molding_type' => $sync2['molding_finding'][$i]->molding_type,
                    'part_name' => $sync2['molding_finding'][$i]->part_name,
                    'problem' => $sync2['molding_finding'][$i]->problem,
                    'handling_temporary' => $sync2['molding_finding'][$i]->handling_temporary,
                    'notes' => $sync2['molding_finding'][$i]->notes,
                    'handling_note' => $sync2['molding_finding'][$i]->handling_note,
                    'handling_eviden' => $sync2['molding_finding'][$i]->handling_eviden,
                    'close_date' => $sync2['molding_finding'][$i]->close_date,
                    'status' => $sync2['molding_finding'][$i]->status,
                    'note' => $sync2['molding_finding'][$i]->note,
                    'remark' => $sync2['molding_finding'][$i]->remark,
                    'sync_at' => $sync2['sync_at'],
                    'created_by' => $sync2['molding_finding'][$i]->created_by,
                    'created_at' => $sync2['molding_finding'][$i]->created_at,
                    'updated_at' => $sync2['molding_finding'][$i]->updated_at,
                    'deleted_at' => $sync2['molding_finding'][$i]->deleted_at,
                ]);

                $update_check = db::table('pe_molding_handlings')
                ->insert([
                    'id' => $sync2['molding_handling'][$i]->id,
                    'finding_id' => $sync2['molding_handling'][$i]->finding_id,
                    'check_date' => $sync2['molding_handling'][$i]->check_date,
                    'pic' => $sync2['molding_handling'][$i]->pic,
                    'molding_name' => $sync2['molding_handling'][$i]->molding_name,
                    'part_name' => $sync2['molding_handling'][$i]->part_name,
                    'handling_note' => $sync2['molding_handling'][$i]->handling_note,
                    'handling_att1' => $sync2['molding_handling'][$i]->handling_att1,
                    'handling_att2' => $sync2['molding_handling'][$i]->handling_att2,
                    'status' => $sync2['molding_handling'][$i]->status,
                    'remark' => $sync2['molding_handling'][$i]->remark,
                    'sync_at' => $sync2['sync_at'],
                    'created_by' => $sync2['molding_handling'][$i]->created_by,
                    'created_at' => $sync2['molding_handling'][$i]->created_at,
                    'updated_at' => $sync2['molding_handling'][$i]->updated_at,
                    'deleted_at' => $sync2['molding_handling'][$i]->deleted_at,
                ]);
            }

            public function updateTableSync(Request $request)
            {
                try {

                  $total = DB::table($request->get('table'))->where('id',$request->get('id'))->update([
                    'synced_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);


                  $response = array(
                    'status' => true,
                );
                  return Response::json($response);

              } catch (\Exception$e) {
                  $response = [
                    'error' => $e->getMessage(),
                ];
                return Response::json($response);
            }
        }


        public function getWPOS()
        {
            try {
              $wpos = db::connection('mysql_new')
               ->table('wpos_logs')
               ->get();

              // $wpos = db::connection('mysql_new')->select("SELECT * from wpos_logs where deleted_at is null");

              $status = 200;
              $response = $wpos;
              return response()->json($response, $status);

              } catch (\Exception$e) {
                  $status = 401;
                  $response = [
                    'error' => $e->getMessage(),
                ];
                return response()->json($response, $status);
            }
        }

        public function fetchEQDelivery(Request $request)
        {

            $need_if = db::connection('mysql_new')
            ->table('equipment_plan_deliveries')
            ->where('need_if', 1)
            ->get();

            try {
              $update_need_if = db::connection('mysql_new')
              ->table('equipment_plan_deliveries')
              ->where('need_if', 1)
              ->update([
                'need_if' => 0,
            ]);

              $status = 200;
              $response = $need_if;
              return response()->json($response, $status);

          } catch (\Exception$e) {
              $status = 401;
              $response = [
                'error' => $e->getMessage(),
            ];
            return response()->json($response, $status);
        }
    }


    public function insertEQDelivery(Request $request)
    {
        $po_detail = $request->all();

        DB::beginTransaction();
        try {
            // for ($i = 0; $i < count($po_detail); $i++) {
                $insert = DB::connection('mysql_new')
                ->table('equipment_plan_deliveries')
                ->insert([
                  'need_if' => 0,
                  'id' => $po_detail['id'],
                  'no_po' => $po_detail['no_po'],
                  'no_item' => $po_detail['no_item'],
                  'item_name' => $po_detail['nama_item'],
                  'po_date' => $po_detail['tgl_po'],
                  'delivery_date' => $po_detail['delivery_date'],
                  'quantity' => $po_detail['qty'],
                  'uom' => $po_detail['uom'],
                  'price' => $po_detail['price'],
                  'created_by' => $po_detail['created_by'],
                  'created_at' => date('Y-m-d H:i:s'),
                  'updated_at' => date('Y-m-d H:i:s')
              ]);
            // }

        } catch (\Exception$e) {
          DB::rollback();
          $status = 401;
          $response = [
            'error' => $e->getMessage()];
            return response()->json($response, $status);
        }

        DB::commit();
        $status = 200;
        return response()->json($status);

    }

    public function inputQrCode(Request $request)
      {
        $email = $request->all();

        DB::beginTransaction();
        try {
          $insert = DB::connection('mysql_new')->table('qr_code_generators')
            ->insert([
              'base64_file' => $email[0]['base64_file'],
              'path_file' => $email[0]['path_file'],
              'purpose' => $email[0]['purpose'],
              'remark' => $email[0]['remark'],
              'id_gen' => $email[0]['id_gen'],
              'code' => $email[0]['code'],
              'created_by' => $email[0]['created_by'],
              'created_at' => $email[0]['created_at'],
              'updated_at' => $email[0]['updated_at'],
            ]);

          $filedecode = base64_decode($email[0]['base64_file']);
            file_put_contents(public_path('images/qrcode/qrcode'.$email[0]['code'].'.png'),$filedecode);
        } catch (\Exception$e) {
          DB::rollback();
          $status = 401;
          $response = [
            'error' => $e->getMessage(),
          ];
          return response()->json($response, $status);
        }

        DB::commit();
        $status = 200;
        return response()->json($status);
      }

}
