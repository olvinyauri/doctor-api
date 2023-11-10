<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    /**
     * Get All Doctors
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllDoctors()
    {
        try {
            $limit = request()->query('limit') ?? 10;

            $doctors = DB::table('users')
                ->where('users.role_id', 1)
                ->join('employees', 'users.id', '=', 'employees.user_id')
                ->select('users.id',  'employees.id as employee_id', 'users.name', 'users.email', 'users.phone', 'users.address', 'employees.qualification')
                ->paginate($limit);

            return response()->json([
                'status_code' => 200,
                'message' => 'Get all doctors',
                'meta' => $doctors
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Get Doctor By ID
     * 
     * @param Request $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDoctorById(Request $request)
    {
        try {
            $id = $request->id;
            $doctors = DB::table('users')
                ->where('users.role_id', 2)
                ->where('users.id', $id)
                ->join('employees', 'users.id', '=', 'employees.user_id')
                ->select('users.id', 'employees.id as employee_id', 'users.name', 'users.email', 'users.phone', 'users.address', 'employees.qualification')
                ->get()->first();

            return response()->json([
                'status_code' => 200,
                'message' => 'Get detail doctor',
                'data' => $doctors
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Get Places of Doctor By ID
     * 
     * @param Request $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */

    public function getPlacesOfDoctorById(Request $request)
    {
        try {
            $id = $request->id;
            $places = DB::table('places')
                ->where('places.employee_id', $id)
                ->join('employees', 'places.employee_id', '=', 'employees.id')
                ->join('users', 'employees.user_id', '=', 'users.id')
                ->select('places.id', 'places.name', 'places.address', 'places.reservationable', 'places.employee_id', 'users.name as doctor_name')
                ->get();

            return response()->json([
                'status_code' => 200,
                'message' => 'Get all places of doctor',
                'data' => $places
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Get Doctor Schedules By Place and Employee ID
     * 
     * @return \Illuminate\Http\JsonResponse
     */

    public function getDoctorSchedules(Request $request)
    {
        try {
            $employee_id = $request->query('employee_id');
            if ($employee_id == null) {
                $user_id = auth()->user()->id;
                $employee_data = DB::table('employees')->where('user_id', $user_id)->first();
                $employee_id = $employee_data->id;
            }
            $place_id = $request->query('place_id');

            if ($place_id != null) {

                $schedules = DB::table('schedules')
                    ->where('schedules.employee_id', $employee_id)
                    ->where('schedules.place_id', $place_id)
                    ->whereDate('schedules.schedule_date', '>=', date('Y-m-d'))
                    ->join('employees', 'schedules.employee_id', '=', 'employees.id')
                    ->join('places', 'schedules.place_id', '=', 'places.id')
                    ->select('schedules.id', 'schedules.schedule_date', 'schedules.schedule_time', 'schedules.schedule_time_end', 'schedules.qty', 'places.name as place_name')
                    ->get();
            } else {

                $schedules = DB::table('schedules')
                    ->where('schedules.employee_id', $employee_id)
                    ->whereDate('schedules.schedule_date', '>=', date('Y-m-d'))
                    ->join('employees', 'schedules.employee_id', '=', 'employees.id')
                    ->join('places', 'schedules.place_id', '=', 'places.id')
                    ->select('schedules.id', 'schedules.schedule_date', 'schedules.schedule_time', 'schedules.schedule_time_end', 'schedules.qty', 'places.name as place_name')
                    ->get();
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Get all schedules of doctor',
                'data' => $schedules,
                'employee_id' => $employee_id,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Get Doctor Schedules By Place and Employee ID
     * 
     * @return \Illuminate\Http\JsonResponse
     */

    public function getMySchedules(Request $request)
    {
        try {
            $user_id = auth()->user()->id;
            $employee_data = DB::table('employees')->where('user_id', $user_id)->first();
            $employee_id = $employee_data->id;

            $limit = request()->query('limit') ?? 10;

            $schedules = DB::table('schedules')
                ->where('schedules.employee_id', $employee_id)
                ->join('employees', 'schedules.employee_id', '=', 'employees.id')
                ->join('places', 'schedules.place_id', '=', 'places.id')
                ->select('schedules.id', 'schedules.schedule_date', 'schedules.schedule_time', 'schedules.schedule_time_end', 'schedules.qty', 'places.name as place_name')
                ->paginate($limit);


            return response()->json([
                'status_code' => 200,
                'message' => 'Get all schedules of doctor',
                'data' => $schedules,
                'employee_id' => $employee_id,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Create Doctor Schedule
     */

    public function createDoctorSchedule(Request $request)
    {
        try {
            $schedule = DB::table('schedules')->insert([
                'employee_id' => $request->employee_id,
                'place_id' => $request->place_id,
                'schedule_date' => $request->schedule_date,
                'schedule_time' => $request->schedule_time,
                'schedule_time_end' => $request->schedule_time_end,
                'qty' => $request->qty,
                'created_at' => Date('Y-m-d H:i:s'),
                'updated_at' => Date('Y-m-d H:i:s')
            ]);

            if (!$schedule) {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Failed to create schedule'
                ]);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Success to create schedule'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    /**
     * Update Doctor Schedule
     */

    public function updateDoctorSchedule(Request $request)
    {
        try {
            $schedule = DB::table('schedules')
                ->where('id', $request->id)
                ->update([
                    'employee_id' => $request->employee_id,
                    'place_id' => $request->place_id,
                    'schedule_date' => $request->schedule_date,
                    'schedule_time' => $request->schedule_time,
                    'schedule_time_end' => $request->schedule_time_end,
                    'qty' => $request->qty,
                    'updated_at' => Date('Y-m-d H:i:s')
                ]);

            if (!$schedule) {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Failed to update schedule'
                ]);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Success to update schedule'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }
}
