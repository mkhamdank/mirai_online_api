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


    public function fetchVendorRegistration(Request $request)
    {

        $need_if = db::connection('mysql_new')
            ->table('vendor_registrations')
            ->where('need_if', 1)
            ->get();

        try {
            $update_need_if = db::connection('mysql_new')
                ->table('vendor_registrations')
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
                    'purpose' => $email[0]['purpose'],
                    'pick_up' => $email[0]['pick_up'],
                    'remark' => $email[0]['remark'],
                    'car' => $email[0]['car'],
                    'closure_status' => 'driver',
                    'requested_id' => $email[0]['requested_id'],
                    'requested_name' => $email[0]['requested_name'],
                    'requested_phone' => $email[0]['requested_phone'],
                    'driver_phone' => $email[0]['driver_phone'],
                    'token' => $email[0]['token'],
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

        $driver_task = DB::connection('mysql_new')->table('driver_tasks')
            ->where('synced', null)
            ->where('remark',null)
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
                ->where('remark',null)
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

    public function fetchDriverLogJapanese()
    {

        $driver_task = DB::connection('mysql_new')->table('driver_tasks')
            ->where('synced', null)
            ->where('remark','japanese')
            ->whereIn('closure_status', ['japanese','closed'])
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
            ->where('remark','japanese')
            ->where(DB::RAW('DATE_FORMAT(date_from,"%Y-%m-%d")'), '<', date('Y-m-d'))
            ->get();

        if (count($driver_task_before)) {
            $driver_task_before = DB::connection('mysql_new')->table('driver_tasks')
                ->where('synced', '!=', null)
                ->where('remark','japanese')
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

    public function fetchDriverLogDaily()
    {

        $driver_task = DB::connection('mysql_new')->table('driver_tasks')
            ->where('synced', null)
            ->where('remark','daily_japanese')
            ->whereIn('closure_status', ['daily_japanese','closed'])
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
            ->where('remark','daily_japanese')
            ->where(DB::RAW('DATE_FORMAT(date_from,"%Y-%m-%d")'), '<', date('Y-m-d'))
            ->get();

        if (count($driver_task_before)) {
            $driver_task_before = DB::connection('mysql_new')->table('driver_tasks')
                ->where('synced', '!=', null)
                ->where('remark','daily_japanese')
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

    function fetchVendorGift()
    {
        $vendor_gift = DB::connection('mysql_new')->table('vendor_gifts')
        ->where('status','Active')
            ->get();

        try {
            if (count($vendor_gift) > 0) {
                $status = 200;
                $response = $vendor_gift;
                return response()->json($response, $status);
            } else {
                $status = 200;
                $response = $vendor_gift;
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

    function fetchVendorHoliday()
    {
        $vendor_gift = DB::connection('mysql_new')->table('vendor_gifts')
        ->where('country','ID')
            ->get();

        try {
            if (count($vendor_gift) > 0) {
                $status = 200;
                $response = $vendor_gift;
                return response()->json($response, $status);
            } else {
                $status = 200;
                $response = $vendor_gift;
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

    function fetchPassengerAttendance()
    {
        $passenger_attendance = DB::connection('mysql_new')->table('driver_passenger_attendances')
        ->where('synced',null)
        ->get();

        for ($i = 0; $i < count($passenger_attendance); $i++) {
            $update_passenger_attendance = DB::connection('mysql_new')->table('driver_passenger_attendances')
                ->where('id', $passenger_attendance[$i]->id)
                ->update([
                    'synced' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        try {
            if (count($passenger_attendance) > 0) {
                $status = 200;
                $response = $passenger_attendance;
                return response()->json($response, $status);
            } else {
                $status = 200;
                $response = $passenger_attendance;
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

    function insertIncomingLog(Request $request) {
        $truncate = DB::
            table('qa_incoming_logs')
            ->truncate();

        try {
            $data = $request->all();

            for ($i=0; $i < count($data); $i++) { 
                $insert = db::table('qa_incoming_logs')
                ->insert([
                    'incoming_check_code' => $data[$i]['incoming_check_code'],
                    'lot_number' => $data[$i]['lot_number'],
                    'location' => $data[$i]['location'],
                    'inspector_id' => $data[$i]['inspector_id'],
                    'material_number' => $data[$i]['material_number'],
                    'material_description' => $data[$i]['material_description'],
                    'vendor' => $data[$i]['vendor'],
                    'qty_rec' => $data[$i]['qty_rec'],
                    'qty_check' => $data[$i]['qty_check'],
                    'invoice' => $data[$i]['invoice'],
                    'inspection_level' => $data[$i]['inspection_level'],
                    'repair' => $data[$i]['repair'],
                    'scrap' => $data[$i]['scrap'],
                    'return' => $data[$i]['return'],
                    'total_ok' => $data[$i]['total_ok'],
                    'total_ng' => $data[$i]['total_ng'],
                    'ng_ratio' => $data[$i]['ng_ratio'],
                    'status_lot' => $data[$i]['status_lot'],
                    'report_evidence' => $data[$i]['report_evidence'],
                    'send_email_status' => $data[$i]['send_email_status'],
                    'send_email_at' => $data[$i]['send_email_at'],
                    'hpl' => $data[$i]['hpl'],
                    'serial_number' => $data[$i]['serial_number'],
                    'note_all' => $data[$i]['note_all'],
                    'remark' => $data[$i]['remark'],
                    'total_ng_pcs' => $data[$i]['total_ng_pcs'],
                    'created_by' => $data[$i]['created_by'],
                    'deleted_at' => $data[$i]['deleted_at'],
                    'created_at' => $data[$i]['created_at'],
                    'updated_at' => $data[$i]['updated_at'],
                    'synced_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $status = 200;
            $response = count($data). ' incoming logs inserted successfully.';
            return response()->json($response, $status);

        } catch (\Exception $e) {
            $status = 401;
            $response = [
                'error' => $e->getMessage() . ' line ' . $e->getLine(),
            ];
            return response()->json($response, $status);

        }
    }

    function insertIncomingNGLog(Request $request) {
        $truncate = DB::
            table('qa_incoming_ng_logs')
            ->truncate();

        try {
            $data = $request->all();

            for ($i=0; $i < count($data); $i++) { 
                $insert = db::table('qa_incoming_ng_logs')
                ->insert([
                    'incoming_check_code' => $data[$i]['incoming_check_code'],
                    'incoming_check_log_id' => $data[$i]['incoming_check_log_id'],
                    'lot_number' => $data[$i]['lot_number'],
                    'location' => $data[$i]['location'],
                    'inspector_id' => $data[$i]['inspector_id'],
                    'material_number' => $data[$i]['material_number'],
                    'material_description' => $data[$i]['material_description'],
                    'vendor' => $data[$i]['vendor'],
                    'qty_rec' => $data[$i]['qty_rec'],
                    'qty_check' => $data[$i]['qty_check'],
                    'qty_ng' => $data[$i]['qty_ng'],
                    'invoice' => $data[$i]['invoice'],
                    'inspection_level' => $data[$i]['inspection_level'],
                    'ng_name' => $data[$i]['ng_name'],
                    'status_ng' => $data[$i]['status_ng'],
                    'note_ng' => $data[$i]['note_ng'],
                    'area' => $data[$i]['area'],
                    'serial_number' => $data[$i]['serial_number'],
                    'created_by' => $data[$i]['created_by'],
                    'deleted_at' => $data[$i]['deleted_at'],
                    'created_at' => $data[$i]['created_at'],
                    'updated_at' => $data[$i]['updated_at'],
                    'synced_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $status = 200;
            $response = count($data). ' incoming NG logs inserted successfully.';
            return response()->json($response, $status);

        } catch (\Exception $e) {
            $status = 401;
            $response = [
                'error' => $e->getMessage() . ' line ' . $e->getLine(),
            ];
            return response()->json($response, $status);

        }
    }

    public function updateJapaneseOtp(Request $request)
    {
        $email = $request->all();

        DB::beginTransaction();
        try {
            $insert = DB::connection('mysql_new')->table('japaneses')
            ->where('employee_id', $email[0]['employee_id'])
                ->update([
                    'driver_otp' => $email[0]['otp'],
                    'updated_at' => date('Y-m-d H:i:s'),
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

    function insertPassenger(Request $request) {
        $truncate = DB::connection('mysql_new')
            ->table('driver_passengers')
            ->truncate();

        try {
            $data = $request->all();

            for ($i=0; $i < count($data); $i++) { 
                $insert = db::connection('mysql_new')
                ->table('driver_passengers')
                ->insert([
                    'destination' => $data[$i]['destination'],
                    'employee_id' => $data[$i]['employee_id'],
                    'name' => $data[$i]['name'],
                    'department' => $data[$i]['department'],
                    'hire_date' => $data[$i]['hire_date'],
                    'grade_code' => $data[$i]['grade_code'],
                    'employment_status' => $data[$i]['employment_status'],
                    'tag' => $data[$i]['tag'],
                    'meeting_point' => $data[$i]['meeting_point'],
                    'status' => $data[$i]['status'],
                    'pick_up_time' => $data[$i]['pick_up_time'],
                    'created_by' => $data[$i]['created_by'],
                    'created_at' => $data[$i]['created_at'],
                    'updated_at' => $data[$i]['updated_at'],
                    'synced_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $status = 200;
            $response = count($data). ' passengers inserted successfully.';
            return response()->json($response, $status);

        } catch (\Exception $e) {
            $status = 401;
            $response = [
                'error' => $e->getMessage() . ' line ' . $e->getLine(),
            ];
            return response()->json($response, $status);

        }
    }

    function insertCaseNGLog(Request $request) {
        $truncate = DB::
            table('pn_case_log_ngs')
            ->truncate();

        try {
            $data = $request->all();

            for ($i=0; $i < count($data); $i++) { 
                $insert = db::table('pn_case_log_ngs')
                ->insert([
                    'log_id' => $data[$i]['log_id'],
                    'form_number' => $data[$i]['form_number'],
                    'ng' => $data[$i]['ng'],
                    'ng_status' => $data[$i]['ng_status'],
                    'operator' => $data[$i]['operator'],
                    'line' => $data[$i]['line'],
                    'type' => $data[$i]['type'],
                    'location' => $data[$i]['location'],
                    'qty' => $data[$i]['qty'],
                    'remark' => $data[$i]['remark'],
                    'created_by' => $data[$i]['created_by'],
                    'created_at' => $data[$i]['created_at'],
                    'updated_at' => $data[$i]['updated_at'],
                    'synced_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $status = 200;
            $response = count($data). ' case NG log inserted successfully.';
            return response()->json($response, $status);

        } catch (\Exception $e) {
            $status = 401;
            $response = [
                'error' => $e->getMessage() . ' line ' . $e->getLine(),
            ];
            return response()->json($response, $status);

        }
    }

    function insertCaseLog(Request $request) {
        $truncate = DB::
            table('pn_case_log_proccesses')
            ->truncate();

        try {
            $data = $request->all();

            for ($i=0; $i < count($data); $i++) { 
                $insert = db::table('pn_case_log_proccesses')
                ->insert([
                    'form_number' => $data[$i]['form_number'],
                    'line' => $data[$i]['line'],
                    'operator' => $data[$i]['operator'],
                    'type' => $data[$i]['type'],
                    'location' => $data[$i]['location'],
                    'qty' => $data[$i]['qty'],
                    'created_by' => $data[$i]['created_by'],
                    'created_at' => $data[$i]['created_at'],
                    'updated_at' => $data[$i]['updated_at'],
                    'synced_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $status = 200;
            $response = count($data). ' case log inserted successfully.';
            return response()->json($response, $status);

        } catch (\Exception $e) {
            $status = 401;
            $response = [
                'error' => $e->getMessage() . ' line ' . $e->getLine(),
            ];
            return response()->json($response, $status);

        }
    }

    function insertScrapReturn(Request $request) {
        $truncate = DB::
            table('scrap_logs')
            ->truncate();

        try {
            $data = $request->all();

            for ($i=0; $i < count($data); $i++) { 
                $insert = db::table('scrap_logs')
                ->insert([
                    'scrap_id' => $data[$i]['scrap_id'],
                    'slip' => $data[$i]['slip'],
                    'order_no' => $data[$i]['order_no'],
                    'material_number' => $data[$i]['material_number'],
                    'material_description' => $data[$i]['material_description'],
                    'spt' => $data[$i]['spt'],
                    'valcl' => $data[$i]['valcl'],
                    'category' => $data[$i]['category'],
                    'issue_location' => $data[$i]['issue_location'],
                    'receive_location' => $data[$i]['receive_location'],
                    'remark' => $data[$i]['remark'],
                    'quantity' => $data[$i]['quantity'],
                    'uom' => $data[$i]['uom'],
                    'category_reason' => $data[$i]['category_reason'],
                    'reason' => $data[$i]['reason'],
                    'summary' => $data[$i]['summary'],
                    'no_invoice' => $data[$i]['no_invoice'],
                    'cites_cat' => $data[$i]['cites_cat'],
                    'created_by' => $data[$i]['created_by'],
                    'scraped_by' => $data[$i]['scraped_by'],
                    'canceled_by' => $data[$i]['canceled_by'],
                    'canceled_user' => $data[$i]['canceled_user'],
                    'canceled_user_at' => $data[$i]['canceled_user_at'],
                    'slip_created' => $data[$i]['slip_created'],
                    'created_at' => $data[$i]['created_at'],
                    'deleted_at' => $data[$i]['deleted_at'],
                    'updated_at' => $data[$i]['updated_at'],
                    'synced_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $status = 200;
            $response = count($data). ' scrap log inserted successfully.';
            return response()->json($response, $status);

        } catch (\Exception $e) {
            $status = 401;
            $response = [
                'error' => $e->getMessage() . ' line ' . $e->getLine(),
            ];
            return response()->json($response, $status);

        }
    }

    public function fetchDriverGasoline()
    {

        $driver_task = DB::connection('mysql_new')->table('driver_tasks')
        ->select('task_id')
            ->get();

        $response = array(
            'status' => true,
            'driver_task' => $driver_task,
        );
        return Response::json($response);
    }

    public function fetchMoldingMaster()
    {
        $molding_master = DB::connection('mysql_new')->table('molding_diagnose_masters')
        ->select('fixed_asset_number', 'fixed_asset_name', 'vendor', 'acquired_date', 'classification', 'standard_shot', 'total_shot', 'status', 'remark')
            ->get();

        $response = array(
            'status' => true,
            'molding_master' => $molding_master,
        );
        return response()->json($response);
    }

    public function fetchMoldingHistoryInput(Request $request)
    {
        $fixed_asset_number = $request->fixed_asset_number;
        
        $molding_history_input = DB::connection('mysql_new')->table('molding_diagnose_shots')
        ->leftJoin('molding_diagnose_masters', 'molding_diagnose_shots.fixed_asset_number', '=', 'molding_diagnose_masters.fixed_asset_number')
        ->select('molding_diagnose_shots.fixed_asset_number', 'molding_name', 'period', 'molding_diagnose_shots.total_shot', 'standard_shot', 'accumulative_shot', 'molding_diagnose_shots.status', 'molding_diagnose_shots.remark', 'molding_diagnose_shots.created_at', 'molding_diagnose_shots.created_by')
            ->where('molding_diagnose_shots.fixed_asset_number', $fixed_asset_number)
            ->orderBy('molding_diagnose_shots.created_at', 'desc')
            ->get();

        $response = array(
            'status' => true,
            'molding_history_input' => $molding_history_input
        );
        return response()->json($response);
    }

    public function fetchMoldingHistoryCheck(Request $request)
    {
        $fixed_asset_number = $request->fixed_asset_number;
        
        $molding_history_check = DB::connection('mysql_new')->table('molding_diagnose_forms')
        ->select('form_number', 'fixed_asset_number', 'fixed_asset_name', 'vendor', 'status', 'remark', 'created_by', 'created_at')
            ->where('fixed_asset_number', $fixed_asset_number)
            ->orderBy('molding_diagnose_forms.created_at', 'desc')
            ->get();

        $response = array(
            'status' => true,
            'molding_history_check' => $molding_history_check,
        );
        return response()->json($response);
    }

    public function fetchMoldingReport($form_number)
    {   
        // ------------------- PRODUCT REPORT -------------------
        $product = DB::table('molding_diagnose_forms')
            ->leftJoin('molding_diagnose_product_forms', 'molding_diagnose_forms.form_number', '=', 'molding_diagnose_product_forms.master_form_number')
            ->where('molding_diagnose_forms.form_number', $form_number)
            ->select('molding_diagnose_forms.fixed_asset_name', 'molding_diagnose_forms.photo_product',
            'molding_diagnose_product_forms.points', 'molding_diagnose_product_forms.check_by', 'molding_diagnose_product_forms.check_at')
            ->first();

        $product_checklist = DB::table('molding_diagnose_product_masters')
            ->leftJoin(db::raw('(SELECT * FROM molding_diagnose_product_details WHERE master_form_number = "' . $form_number . '") as details'), 'molding_diagnose_product_masters.id', '=', 'details.ng_id')
            ->select('molding_diagnose_product_masters.id','item_ng','category_check','grouping', 'details.diagnose_result', 'details.deduction as actual_deduction')
            ->orderBy('id', 'asc')
            ->get();

        // -------------------- PRODUCT NG -------------
        $product_ngs = DB::table('molding_diagnose_product_ngs')
            ->where('molding_diagnose_product_ngs.master_form_number', $form_number)
            ->select('molding_diagnose_product_ngs.fixed_asset_name', 'molding_diagnose_product_ngs.photo1', 'molding_diagnose_product_ngs.photo2',
            'molding_diagnose_product_ngs.id_ng', 'molding_diagnose_product_ngs.ng_name', 'molding_diagnose_product_ngs.check_by', 'molding_diagnose_product_ngs.check_at')
            ->orderBy('id_ng', 'asc')
            ->get()
            ->toArray();

        // return view('molding.report.report_product_ng', compact('form_number', 'ngs'));

        // -------------------- MOLDING REPORT -------------
        $moldings = DB::table('molding_diagnose_forms')
            ->leftJoin('molding_diagnose_molding_forms', 'molding_diagnose_forms.form_number', '=', 'molding_diagnose_molding_forms.master_form_number')
            ->where('molding_diagnose_forms.form_number', $form_number)
            ->select('molding_diagnose_forms.fixed_asset_name',
            'molding_diagnose_molding_forms.points', 'molding_diagnose_molding_forms.check_by', 'molding_diagnose_molding_forms.check_at')
            ->first();

        $molding_checklist = DB::table('molding_diagnose_molding_masters')
            ->leftJoin(db::raw('(SELECT * FROM molding_diagnose_molding_details WHERE master_form_number = "' . $form_number . '") as details'), 'molding_diagnose_molding_masters.id', '=', 'details.ng_id')
            ->select('molding_diagnose_molding_masters.id','item_ng','grouping', 'details.diagnose_result', 'details.deduction as actual_deduction', 'molding_diagnose_molding_masters.daerah_ng', 'details.parts', 'molding_diagnose_molding_masters.item_check', db::raw('GROUP_CONCAT(details.item_name SEPARATOR ", ") as item_name'), 'details.note')
            ->groupBy('molding_diagnose_molding_masters.id','item_ng','grouping', 'details.diagnose_result', 'details.deduction', 'molding_diagnose_molding_masters.daerah_ng', 'details.parts', 'molding_diagnose_molding_masters.item_check', 'details.note')
            ->orderBy('id', 'asc')
            ->get();

        // return view('molding.report.report_mold_form', compact('form_number', 'molding_name', 'checklist'));

        // -------------------- MOLDING NG -------------

        $molding_ng = DB::table('molding_diagnose_molding_details')
            ->where('molding_diagnose_molding_details.master_form_number', $form_number)
            ->select('molding_diagnose_molding_details.fixed_asset_name', 'molding_diagnose_molding_details.photo1', 'molding_diagnose_molding_details.photo2',
            'molding_diagnose_molding_details.ng_id', 'molding_diagnose_molding_details.ng_name')
            ->orderBy('ng_id', 'asc')
            ->get()
            ->toArray();

        // return view('molding.report.report_mold_ng', compact('form_number', 'ngs'));

        // -------------------- MOLDING EVALUASI -------------

        $data_master = DB::table('molding_diagnose_forms')
        ->where('molding_diagnose_forms.form_number', $form_number)
        ->select('molding_diagnose_forms.fixed_asset_name', 'molding_diagnose_forms.rank',
        'molding_diagnose_forms.total_score', 'product_category', 'production_qty', 'production_date', 'production_period', 'molding_diagnose_forms.check_by', 'molding_diagnose_forms.check_at')
        ->first();

        $response = array(
            'status' => true,
            'data_evaluasi' => $data_master,
            'data_molding' => $moldings,
            'data_molding_checklist' => $molding_checklist,
            'data_molding_ng' => $molding_ng,
            'data_product' => $product,
            'data_product_checklist' => $product_checklist,
            'data_product_ng' => $product_ngs,
        );
        return response()->json($response);
    }

    public function inputDriverLists(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {
            $truncate = DB::connection('mysql_new')
            ->table('driver_lists')
            ->truncate();
            for ($i = 0; $i < count($data); $i++) {
                $insert = DB::connection('mysql_new')->table('driver_lists')
                ->insert(
                    [
                        'driver_id' => $data[$i]['driver_id'],
                        'driver_name' => $data[$i]['driver_name'],
                        'phone_no' => $data[$i]['phone_no'],
                        'whatsapp_no' => $data[$i]['whatsapp_no'],
                        'plat_no' => $data[$i]['plat_no'],
                        'car' => $data[$i]['car'],
                        'passenger_id' => $data[$i]['passenger_id'],
                        'passenger_name' => $data[$i]['passenger_name'],
                        'passenger_category' => $data[$i]['passenger_category'],
                        'created_by' => '1930',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            }

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

}
