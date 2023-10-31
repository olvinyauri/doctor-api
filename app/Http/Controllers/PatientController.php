<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    /**
     * Get All Patients
     */
    public function getAllPatients(Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            $search = $request->search;

            $patients = DB::table('patients')
                ->leftJoin('users', 'patients.user_id', '=', 'users.id')
                ->select('patients.*', 'users.name as user_name', 'users.birth_date', 'users.gender', 'users.phone', 'users.address', 'users.email')
                ->where('users.name', 'like', '%' . $search . '%')
                ->orWhere('users.email', 'like', '%' . $search . '%')
                ->paginate($limit);


            return response()->json([
                'status_code' => 200,
                'message' => 'Get all patients successfully',
                'data' => $patients
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    /**
     * Update Access Code
     */
    public function updateAccessCode(Request $request)
    {
        try {
            $id = $request->id;
            $access_code = $request->access_code;

            $patient = DB::table('patients')
                ->where('id', $id)
                ->update([
                    'access_code' => $access_code,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            return response()->json([
                'status_code' => 200,
                'message' => 'Update access code successfully',
                'data' => $patient
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }
}
