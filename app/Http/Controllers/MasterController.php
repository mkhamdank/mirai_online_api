<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Response;

class MasterController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

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

        $ng_list = DB::table('ng_lists')->where('synced_at', null)->get();
        $outgoing_crestec = DB::table('qa_outgoing_vendor_crestecs')->where('synced_at', null)->get();
        $outgoing = DB::table('qa_outgoing_vendors')->where('synced_at', null)->get();
        $outgoing_final = DB::table('qa_outgoing_vendor_finals')->where('synced_at', null)->get();
        $outgoing_recheck = DB::table('qa_outgoing_vendor_rechecks')->where('synced_at', null)->get();

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

            $total = DB::table($request->get('table'))->where('id', $request->get('id'))->update([
                'synced_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $response = array(
                'status' => true,
            );
            return Response::json($response);

        } catch (\Exception $e) {
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
                ->orderBy('id','desc')
                ->whereNull('deleted_at')
                ->get();

            $wpos_approvers = db::connection('mysql_new')
            ->table('wpos_approvals')
            ->orderBy('id','desc')
            ->whereNull('deleted_at')
            ->get();

            // $wpos = db::connection('mysql_new')->select("SELECT * from wpos_logs where deleted_at is null");

            $status = 200;

            $response = array(
                'status' => true,
                'wpos' => $wpos,
                'wpos_approvers' => $wpos_approvers,

            );
            return response()->json($response, $status);


        } catch (\Exception $e) {
            $status = 401;
            $response = [
                'error' => $e->getMessage(),
            ];
            return response()->json($response, $status);
        }
    }

    public function getWPOSId(Request $request)
    {
        try {
            $wpos = db::connection('mysql_new')
                ->table('wpos_logs')
                ->where('id', $request->get('id'))
                ->first();

            $wpos_approvers = db::connection('mysql_new')
                ->table('wpos_approvals')
                ->where('wpos_id', $request->get('id'))
                ->whereNull('approved_at')
                ->get();

            $status = 200;
            
            $response = array(
                'status' => true,
                'wpos' => $wpos,
                'wpos_approvers' => $wpos_approvers
            );

            return response()->json($response, $status);

        } catch (\Exception $e) {
            $status = 401;
            $response = [
                'error' => $e->getMessage(),
            ];
            return response()->json($response, $status);
        }
    }


    public function postWPOSApproval(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {

            $update_need_if = db::connection('mysql_new')
                ->table('wpos_approvals')
                ->where('wpos_id', $data[0]['id'])
                ->where('department', $data[0]['dept'])
                ->update([
                    'real_approver_id' => $data[0]['user'],
                    'real_approver_name' => $data[0]['name'],
                    'status' => $data[0]['status'],
                    'reason' => $data[0]['reason'],
                    'approved_at' => date('Y-m-d H:i:s'),
                ]);

        } catch (\Exception $e) {
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

        } catch (\Exception $e) {
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
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            // }

        } catch (\Exception $e) {
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
                    'driver_id' => $email[0]['driver_id'],
                    'driver_name' => $email[0]['driver_name'],
                    'valid_from' => $email[0]['valid_from'],
                    'valid_to' => $email[0]['valid_to'],
                    'destination' => $email[0]['destination'],
                    'passenger' => $email[0]['passenger'],
                    'created_by' => $email[0]['created_by'],
                    'created_at' => $email[0]['created_at'],
                    'updated_at' => $email[0]['updated_at'],
                ]);

            $filedecode = base64_decode($email[0]['base64_file']);
            file_put_contents(public_path('images/qrcode/driver/qrcode' . $email[0]['code'] . '.png'), $filedecode);
        } catch (\Exception $e) {
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

    public function inputDriverTask(Request $request)
    {
        $email = $request->all();

        DB::beginTransaction();
        try {
            $insert = DB::connection('mysql_new')->table('driver_tasks')
                ->insert([
                    'task_id' => $email[0]['task_id'],
                    'date_from' => $email[0]['date_from'],
                    'date_to' => $email[0]['date_to'],
                    'created_by_id' => $email[0]['created_by_id'],
                    'created_by_name' => $email[0]['created_by_name'],
                    'driver_id' => $email[0]['driver_id'],
                    'driver_name' => $email[0]['driver_name'],
                    'destination' => $email[0]['destination'],
                    'plat_no' => $email[0]['plat_no'],
                    'car' => $email[0]['car'],
                    'created_by_id' => $email[0]['created_by_id'],
                    'created_by_name' => $email[0]['created_by_name'],
                    'created_at' => $email[0]['created_at'],
                    'updated_at' => $email[0]['updated_at'],
                ]);

        } catch (\Exception $e) {
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

    public function fetchDriverLog()
    {
        // $qr_code = DB::connection('mysql_new')->table('qr_code_generators')
        // ->where('synced',null)
        // ->where('remark','NOT LIKE',"%regular%")
        // ->get();

        // for ($i=0; $i < count($qr_code); $i++) {
        //     $update_qr_code = DB::connection('mysql_new')->table('qr_code_generators')
        //     ->where('id',$qr_code[$i]->id)
        //     ->update([
        //         'synced' => date('Y-m-d H:i:s'),
        //         'updated_at' => date('Y-m-d H:i:s'),
        //     ]);
        // }

        // $driver_log = DB::connection('mysql_new')->table('driver_control_logs')->get();

        // for ($i=0; $i < count($driver_log); $i++) {
        //     $update_driver_log = DB::connection('mysql_new')->table('driver_control_logs')
        //     ->where('id',$driver_log[$i]->id)
        //     ->update([
        //         'synced' => date('Y-m-d H:i:s'),
        //         'updated_at' => date('Y-m-d H:i:s'),
        //     ]);
        // }

        $driver_task = DB::connection('mysql_new')->table('driver_tasks')
            ->where('synced', null)
            ->where('times', '!=', null)
            ->get();

        for ($i = 0; $i < count($driver_task); $i++) {
            $update_driver_task = DB::connection('mysql_new')->table('driver_tasks')
                ->where('id', $driver_task[$i]->id)
                ->update([
                    'synced' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        $driver_task_before = DB::connection('mysql_new')->table('driver_tasks')
            ->where('synced', '!=', null)
            ->where(DB::RAW('DATE_FORMAT(date_from,"%Y-%m-%d")'), '<', date('Y-m-d'))
            ->get();

        if (count($driver_task_before)) {
            $driver_task_before = DB::connection('mysql_new')->table('driver_tasks')
                ->where('synced', '!=', null)
                ->where(DB::RAW('DATE_FORMAT(date_from,"%Y-%m-%d")'), '<', date('Y-m-d'))
                ->update([
                    'deleted_at' => date('Y-m-d H:i:s'),
                ]);
        }

        $response = array(
            'status' => true,
            'driver_task' => $driver_task,
        );
        return Response::json($response);
    }

    public function deleteDriverTask($task_id)
    {

        $driver_task = DB::connection('mysql_new')->table('driver_tasks')
            ->where('task_id', $task_id)
            ->first();

        if ($driver_task) {
            $driver_task = DB::connection('mysql_new')->table('driver_tasks')
                ->where('task_id', $task_id)
                ->delete();
        }

        $response = array(
            'status' => true,
        );
        return Response::json($response);
    }

    public function getAttendance(Request $request)
    {

        $attendance = DB::connection('mysql_new')->table('attendances')
            ->where('synced', null)
            ->get();

        try {
            if (count($attendance) > 0) {
                for ($i = 0; $i < count($attendance); $i++) {
                    $update = DB::connection('mysql_new')->table('attendances')->where('id', $attendance[$i]->id)->update([
                        'synced' => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                $status = 200;
                $response = $attendance;
                return response()->json($response, $status);
            } else {
                $status = 200;
                $response = $attendance;
                return response()->json($response, $status);
            }

        } catch (\Exception $e) {
            $status = 401;
            $response = [
                'error' => $e->getMessage(),
            ];
            return response()->json($response, $status);

        }

    }

    public function syncFixedAsset()
    {
        try {
            $fa_check = db::connection('mysql_new')->select("SELECT * from fixed_asset_checks where sync_at is null");

            $fa_audit = db::connection('mysql_new')->select("SELECT * from fixed_asset_audits where sync_at is null");

            db::connection('mysql_new')->table('fixed_asset_checks')->whereNull('sync_at')
                ->update(['sync_at' => date('Y-m-d H:i:s')]);

            db::connection('mysql_new')->table('fixed_asset_audits')->whereNull('sync_at')
                ->update(['sync_at' => date('Y-m-d H:i:s')]);

            $response = array(
                'status' => true,
                'fa_check' => $fa_check,
                'fa_audit' => $fa_audit,
                'sync_at' => date('Y-m-d H:i:s'),
            );

            db::connection('mysql_new')->table('fixed_asset_checks')->whereNull('sync_at')
                ->update(['sync_at' => date('Y-m-d H:i:s')]);
            return Response::json($response);
        } catch (Exception $e) {

        }

    }

    public function insertFixedAsset(Request $req)
    {
        try {
            DB::connection('mysql_new')->table('fixed_asset_checks')->where('period', $req->get('period'))->where('location', $req->get('location'))->update([
                'appr_manager_by' => $req->get('appr_manager_by'),
                'appr_manager_at' => date('Y-m-d H:i:s'),
                'appr_status' => 'send',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $status = 200;
            $response = $req->all();
            return response()->json($response, $status);

        } catch (\Exception $e) {
            $status = 401;
            $response = [
                'error' => $e->getMessage(),
            ];
            return response()->json($response, $status);

        }
    }

    public function AddFixedAsset(Request $req)
    {
        try {
            if (count($req->get('cek_asset')) > 0) {
                foreach ($req->get('cek_asset') as $key => $value) {
                    DB::connection('mysql_new')->table('fixed_asset_checks')
                        ->updateOrInsert([
                            'period' => $value['period'],
                            'sap_number' => $value['sap_number'],
                        ], [
                            'period' => $value['period'],
                            'sap_number' => $value['sap_number'],
                            'asset_name' => $value['asset_name'],
                            'category' => $value['category'],
                            'location' => $value['location'],
                            'asset_section' => $value['asset_section'],
                            'asset_images' => $value['asset_images'],
                            'pic' => $value['pic'],
                            'status' => $value['status'],
                            'audit_type' => $value['audit_type'],
                            'created_by' => $value['created_by'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                }
            }

            if (count($req->get('audit_asset')) > 0) {
                foreach ($req->get('audit_asset') as $key => $value) {
                    DB::connection('mysql_new')->table('fixed_asset_audits')
                        ->updateOrInsert([
                            'period' => $value['period'],
                            'sap_number' => $value['sap_number'],
                        ], [
                            'period' => $value['period'],
                            'sap_number' => $value['sap_number'],
                            'asset_name' => $value['asset_name'],
                            'category' => $value['category'],
                            'location' => $value['location'],
                            'asset_section' => $value['asset_section'],
                            'asset_images' => $value['asset_images'],
                            'pic' => $value['pic'],
                            'status' => $value['status'],
                            'audit_type' => $value['audit_type'],
                            'checked_by' => $value['checked_by'],
                            'created_by' => $value['created_by'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                }
            }

            $status = 200;
            $response = $req->all();
            return response()->json($response, $status);

        } catch (\Exception $e) {
            $status = 401;
            $response = [
                'error' => $e->getMessage(),
            ];
            return response()->json($response, $status);

        }
    }

    public function insertPlanDelivery(Request $request)
    {

        $plan_delivery = $request->all();

        DB::connection('mysql_new')->beginTransaction();
        try {
            for ($i = 0; $i < count($plan_delivery); $i++) {
                $insert = DB::connection('mysql_new')
                    ->table('material_plan_deliveries')
                    ->insert([
                        'id' => $plan_delivery[$i]['id'],
                        'need_if' => 0,
                        'vendor_code' => $plan_delivery[$i]['vendor_code'],
                        'po_number' => $plan_delivery[$i]['po_number'],
                        'item_line' => $plan_delivery[$i]['item_line'],
                        'material_number' => $plan_delivery[$i]['material_number'],
                        'issue_date' => $plan_delivery[$i]['issue_date'],
                        'eta_date' => $plan_delivery[$i]['eta_date'],
                        'due_date' => $plan_delivery[$i]['due_date'],
                        'quantity' => $plan_delivery[$i]['quantity'],
                        'created_by' => $plan_delivery[$i]['created_by'],
                        'created_at' => $plan_delivery[$i]['created_at'],
                        'updated_at' => $plan_delivery[$i]['updated_at'],
                    ]);

            }

        } catch (\Exception $e) {
            DB::connection('mysql_new')->rollback();
            $status = 401;
            $response = [
                'error' => $e->getMessage() . ' line ' . $e->getLine(),
            ];
            return response()->json($response, $status);
        }

        DB::connection('mysql_new')->commit();
        $status = 200;
        return response()->json($status);

    }

    public function getSyncPlanDelivery(Request $request)
    {

        $need_if = DB::connection('mysql_new')
            ->table('material_plan_deliveries')
            ->where('need_if', 1)
            ->get();

        try {

            $update_need_if = db::connection('mysql_new')
                ->table('material_plan_deliveries')
                ->where('need_if', 1)
                ->update([
                    'need_if' => 0,
                ]);

            $status = 200;
            $response = $need_if;
            return response()->json($response, $status);

        } catch (\Exception $e) {
            $status = 401;
            $response = [
                'error' => $e->getMessage() . ' line ' . $e->getLine(),
            ];
            return response()->json($response, $status);

        }

    }

    public function insertVendorMail(Request $request)
    {

        $vendor_mails = $request->all();

        DB::connection('mysql_new')->beginTransaction();
        try {

            db::connection('mysql_new')
                ->table('vendor_mails')
                ->where('vendor_code', $vendor_mails[0]['vendor_code'])
                ->delete();

            $insert_data = [];
            for ($i = 0; $i < count($vendor_mails); $i++) {
                db::connection('mysql_new')
                    ->table('vendor_mails')
                    ->insert([
                        'vendor_code' => $vendor_mails[$i]['vendor_code'],
                        'name' => $vendor_mails[$i]['name'],
                        'email' => $vendor_mails[$i]['email'],
                        'remark' => $vendor_mails[$i]['remark'],
                        'vendor_name' => $vendor_mails[$i]['vendor_name'],
                        'created_by' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

        } catch (\Exception $e) {
            DB::connection('mysql_new')->rollback();
            $status = 401;
            $response = [
                'error' => $e->getMessage() . ' line ' . $e->getLine(),
            ];
            return response()->json($response, $status);
        }

        DB::connection('mysql_new')->commit();
        $status = 200;
        return response()->json($status);

    }

    public function updateRawMaterialControl(Request $request)
    {

        $material_controls = $request->all();

        DB::connection('mysql_new')->beginTransaction();
        try {
            for ($i = 0; $i < count($material_controls); $i++) {
                DB::connection('mysql_new')
                    ->table('material_controls')
                    ->where('id', $material_controls[$i]['id'])
                    ->delete();

                DB::connection('mysql_new')
                    ->table('material_controls')
                    ->insert([
                        'id' => $material_controls[$i]['id'],
                        'material_number' => $material_controls[$i]['material_number'],
                        'material_description' => $material_controls[$i]['material_description'],
                        'purchasing_group' => $material_controls[$i]['purchasing_group'],
                        'controlling_group' => $material_controls[$i]['controlling_group'],
                        'vendor_code' => $material_controls[$i]['vendor_code'],
                        'vendor_name' => $material_controls[$i]['vendor_name'],
                        'vendor_shortname' => $material_controls[$i]['vendor_shortname'],
                        'category' => $material_controls[$i]['category'],
                        'pic' => $material_controls[$i]['pic'],
                        'control' => $material_controls[$i]['control'],
                        'remark' => $material_controls[$i]['remark'],
                        'multiple_order' => $material_controls[$i]['multiple_order'],
                        'minimum_order' => $material_controls[$i]['minimum_order'],
                        'sample_qty' => $material_controls[$i]['sample_qty'],
                        'lead_time' => $material_controls[$i]['lead_time'],
                        'urgent_lead_time' => $material_controls[$i]['urgent_lead_time'],
                        'dts' => $material_controls[$i]['dts'],
                        'round' => $material_controls[$i]['round'],
                        'first_reminder' => $material_controls[$i]['first_reminder'],
                        'second_reminder' => $material_controls[$i]['second_reminder'],
                        'material_category' => $material_controls[$i]['material_category'],
                        'location' => $material_controls[$i]['location'],
                        'incoming' => $material_controls[$i]['incoming'],
                        'created_by' => $material_controls[$i]['created_by'],
                        'updated_at' => $material_controls[$i]['updated_at'],
                        'created_at' => $material_controls[$i]['created_at'],
                    ]);
            }

        } catch (\Exception $e) {
            DB::connection('mysql_new')->rollback();
            $status = 401;
            $response = [
                'error' => $e->getMessage() . ' line ' . $e->getLine(),
            ];
            return response()->json($response, $status);
        }

        DB::connection('mysql_new')->commit();
        $status = 200;
        return response()->json($status);

    }

}
