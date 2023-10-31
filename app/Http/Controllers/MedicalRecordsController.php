<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicalRecordsController extends Controller
{
    // id, reservation_id, icd_code, action, complaint, physical_exam, diagnosis, recommendation, recipe, desc

    /**
     * Get ICDS 
     */
    public function getICDS(Request $request)
    {
        try {
            $limit = request()->query('limit') ?? 10;

            $search = $request->query('search');

            if ($search != null) {
                $icds = DB::table('icds')
                    ->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name_en', 'like', '%' . $search . '%')
                    ->orWhere('name_id', 'like', '%' . $search . '%')
                    ->paginate($limit);
            } else {
                $icds = DB::table('icds')->paginate($limit);
            }


            return response()->json([
                'status_code' => 200,
                'message' => 'Get all icds',
                'data' => $icds
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Add Medical Record
     */

    public function addMedicalRecord(Request $request)
    {
        try {
            $medicalRecord = DB::table('medical_records')->insert([
                'reservation_id' => $request->reservation_id,
                'icd_code' => $request->icd_code,
                'action' => $request->action,
                'complaint' => $request->complaint,
                'physical_exam' => $request->physical_exam,
                'diagnosis' => $request->diagnosis,
                'recommendation' => $request->recommendation,
                'recipe' => $request->recipe,
                'desc' => $request->desc,
                'created_at' => Date('Y-m-d H:i:s'),
                'updated_at' => Date('Y-m-d H:i:s')
            ]);

            $reservation_controller = new ReservationController();

            $reservation_controller->updateReservationStatus($request->reservation_id, 2);

            if (!$medicalRecord) {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Failed to add medical record'
                ]);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Success to add medical record'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Get Medical Records
     */

    public function getMyMedicalRecords(Request $request)
    {
        try {
            $limit = request()->query('limit') ?? 10;
            $patient_id = $request->patient_id;
            if ($patient_id == null) {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Patient id is required'
                ]);
            }
            $medicalRecords = DB::table('medical_records')
                ->leftJoin('reservations', 'medical_records.reservation_id', '=', 'reservations.id')
                ->leftJoin('schedules', 'reservations.schedule_id', '=', 'schedules.id')
                ->leftJoin('employees', 'schedules.employee_id', '=', 'employees.id')
                ->leftJoin('places', 'schedules.place_id', '=', 'places.id')
                ->leftJoin('icds', 'medical_records.icd_code', '=', 'icds.code')
                ->leftJoin('users', 'employees.user_id', '=', 'users.id')
                ->where('reservations.patient_id', $request->patient_id)
                ->select('medical_records.*', 'icds.*', 'schedules.schedule_date', 'schedules.schedule_time', 'schedules.schedule_time_end', 'places.name as place_name', 'employees.qualification as employee_qualification', 'users.name as employee_name')
                ->paginate($limit);

            return response()->json([
                'status_code' => 200,
                'message' => 'Get all medical records',
                'data' => $medicalRecords,
            ]);

            
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }
}
