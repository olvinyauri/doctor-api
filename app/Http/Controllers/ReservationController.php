<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    // set $image_controller as global variable
    private $image_controller;

    public function __construct()
    {
        $this->image_controller = new ImageController();
    }

    /**
     * Book Doctor
     * 
     * @param  mixed $request
     * @return void
     */
    public function bookDoctor(Request $request)
    {
        try {
            $patient_id = $request->patient_id;
            $schedule_id = $request->schedule_id;
            $reservation_code = rand(100000, 999999);
            $is_bpjs = $request->is_bpjs;

            if ($request->payment_proof != null) {
                $this->validate($request, [
                    'payment_proof' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                ]);
                $image_path = $request->file('payment_proof')->store('payment_proof');

                $payment_proof = $image_path;
            } else {
                $payment_proof = null;
            }

            if ($request->ktp_image != null) {
                $this->validate($request, [
                    'ktp_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                ]);
                $image_path = $request->file('ktp_image')->store('ktp_image');

                $ktp_image = $image_path;
            } else {
                $ktp_image = null;
            }

            if ($request->surat_rujukan != null) {
                $this->validate($request, [
                    'surat_rujukan' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                ]);
                $image_path = $request->file('surat_rujukan')->store('surat_rujukan');

                $surat_rujukan = $image_path;
            } else {
                $surat_rujukan = null;
            }

            if ($request->bpjs_image != null) {
                $this->validate($request, [
                    'bpjs_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                ]);
                $image_path = $request->file('bpjs_image')->store('bpjs_image');

                $bpjs_card = $image_path;
            } else {
                $bpjs_card = null;
            }

            $nomor_urut = DB::table('reservations')
                ->where('schedule_id', $schedule_id)
                ->count();

            $approve_status = 0;
            $status = 0;

            $reservation = DB::table('reservations')
                ->insertGetId([
                    'patient_id' => $patient_id,
                    'schedule_id' => $schedule_id,
                    'reservation_code' => $reservation_code,
                    'bpjs' => $is_bpjs,
                    'nomor_urut' => $nomor_urut + 1,
                    'approve' => $approve_status,
                    'status' => $status,
                    'bukti_pembayaran' => $payment_proof,
                    'ktp' => $ktp_image,
                    'surat_rujukan' => $surat_rujukan,
                    'bpjs_card' => $bpjs_card,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);


            if (!$reservation) {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Error in booking doctor',
                ]);
            }

            $reservation_data = DB::table('reservations')
                ->where('id', $reservation)
                ->first();

            return response()->json([
                'status_code' => 200,
                'data' => $reservation_data,
                'message' => 'Success booking doctor',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    /**
     * Get My Reservations
     * 
     * @param  mixed $request
     * @return void
     */
    public function getMyReservations(Request $request)
    {
        $patient_id = $request->query('patient_id');
        $status = $request->query('status');

        if ($status == null) {
            $reservation_data = DB::table('reservations')
                ->where('patient_id', $patient_id)
                ->leftJoin('schedules', 'reservations.schedule_id', '=', 'schedules.id')
                ->leftJoin('employees', 'schedules.employee_id', '=', 'employees.id')
                ->leftJoin('users', 'employees.user_id', '=', 'users.id')
                ->leftJoin('places', 'schedules.place_id', '=', 'places.id')
                ->select('reservations.*',  'users.name as doctor_name', 'employees.qualification', 'places.name as place_name')
                ->get();
        } else {
            $reservation_data = DB::table('reservations')
                ->where('patient_id', $patient_id)
                ->where('status', $status)
                ->leftJoin('schedules', 'reservations.schedule_id', '=', 'schedules.id')
                ->leftJoin('employees', 'schedules.employee_id', '=', 'employees.id')
                ->leftJoin('users', 'employees.user_id', '=', 'users.id')
                ->leftJoin('places', 'schedules.place_id', '=', 'places.id')
                ->select('reservations.*',  'users.name as doctor_name', 'employees.qualification', 'places.name as place_name')
                ->get();
        }

        // get how many reservation before this reservation
        foreach ($reservation_data as $key => $value) {
            $nomor_urut = DB::table('reservations')
                ->where('schedule_id', $value->schedule_id)
                ->where('approve', 1)
                ->where('status', 0)
                ->where('nomor_urut', '<', $value->nomor_urut)
                ->count();

            if ($nomor_urut == 0) {
                $reservation_data[$key]->ahead_reservation = null;
            } else {
                $reservation_data[$key]->ahead_reservation = $nomor_urut;
            }

            // get current active reservation
            $current_active_reservation = DB::table('reservations')
                ->where('schedule_id', $value->schedule_id)
                ->where('approve', 1)
                ->where('status', 1)
                ->orderBy('nomor_urut', 'desc')
                ->first();

            $reservation_data[$key]->current_active_reservation = $current_active_reservation->nomor_urut ?? null;
        }



        return response()->json([
            'status_code' => 200,
            'data' => $reservation_data,
            'message' => 'Success get my reservations',
        ]);
    }

    /**
     * Get Detail Reservation
     * 
     * @return void
     */
    public function getReservationDetailById(Request $request)
    {
        $reservation_id = $request->id;

        $reservation_data = DB::table('reservations')
            ->leftJoin('schedules', 'reservations.schedule_id', '=', 'schedules.id')
            ->leftJoin('employees', 'schedules.employee_id', '=', 'employees.id')
            ->leftJoin('users', 'employees.user_id', '=', 'users.id')
            ->leftJoin('places', 'schedules.place_id', '=', 'places.id')
            ->where('reservations.id', $reservation_id)
            ->select('reservations.*', 'schedules.*', 'users.name as doctor_name', 'employees.qualification', 'places.name as place_name')
            ->first();

        // get how many reservation before this reservation
        $nomor_urut = DB::table('reservations')
            ->where('schedule_id', $reservation_data->schedule_id)
            ->where('approve', 1)
            ->where('status', 0)
            ->where('nomor_urut', '<', $reservation_data->nomor_urut)
            ->count();

        if ($nomor_urut == 0) {
            $reservation_data->ahead_reservation = null;
        } else {
            $reservation_data->ahead_reservation = $nomor_urut;
        }

        // get current active reservation
        $current_active_reservation = DB::table('reservations')
            ->where('schedule_id', $reservation_data->schedule_id)
            ->where('approve', 1)
            ->where('status', 1)
            ->orderBy('nomor_urut', 'desc')
            ->first();

        $reservation_data->current_active_reservation = $current_active_reservation->nomor_urut ?? null;

        return response()->json([
            'status_code' => 200,
            'data' => $reservation_data,
            'message' => 'Success get detail reservation',
        ]);
    }

    /**
     * Get Today Reservations
     * 
     * @return json
     * @param  mixed $request
     */

    public function getTodayReservations(Request $request)
    {
        // get $employee_id from current user login
        $user_id = auth()->user()->id;
        $employee_data = DB::table('employees')->where('user_id', $user_id)->first();
        $employee_id = $employee_data->id;

        $today = date('Y-m-d');

        $reservation_data = DB::table('reservations')
            ->leftJoin('schedules', 'reservations.schedule_id', '=', 'schedules.id')
            ->leftJoin('employees', 'schedules.employee_id', '=', 'employees.id')
            ->leftJoin('users', 'employees.user_id', '=', 'users.id')
            ->leftJoin('places', 'schedules.place_id', '=', 'places.id')
            ->where('schedules.employee_id', $employee_id)
            ->where('schedules.schedule_date', $today)
            ->select('reservations.*', 'schedules.*', 'users.name as doctor_name', 'employees.qualification', 'places.name as place_name')
            ->get();

        $total_waiting = $reservation_data->where('approve', 0)->count();
        $total_all = $reservation_data->count();



        foreach ($reservation_data as $key => $value) {
            if ($value->bukti_pembayaran != null) {
                $reservation_data[$key]->bukti_pembayaran = $this->image_controller->getAccessibleImageURL($value->bukti_pembayaran);
            }
            if ($value->ktp != null) {
                $reservation_data[$key]->ktp = $this->image_controller->getAccessibleImageURL($value->ktp);
            }
            if ($value->surat_rujukan != null) {
                $reservation_data[$key]->surat_rujukan = $this->image_controller->getAccessibleImageURL($value->surat_rujukan);
            }
            if ($value->bpjs_card != null) {
                $reservation_data[$key]->bpjs_card = $this->image_controller->getAccessibleImageURL($value->bpjs_card);
            }
        }

        $result = [
            'total_waiting' => $total_waiting,
            'total_all' => $total_all,
            'list_item' => $reservation_data,
        ];

        return response()->json([
            'status_code' => 200,
            'data' => $result,
            'message' => 'Success get today reservations',
        ]);
    }

    /**
     * Get Today Reservations Total
     * 
     * @return json
     * @param  mixed $request
     */

    public function getTodayReservationsTotal(Request $request)
    {
        $user_id = auth()->user()->id;
        $employee_data = DB::table('employees')->where('user_id', $user_id)->first();
        $employee_id = $employee_data->id;

        $today = date('Y-m-d');

        $reservation_data = DB::table('reservations')
            ->leftJoin('schedules', 'reservations.schedule_id', '=', 'schedules.id')
            ->leftJoin('employees', 'schedules.employee_id', '=', 'employees.id')
            ->leftJoin('users', 'employees.user_id', '=', 'users.id')
            ->leftJoin('places', 'schedules.place_id', '=', 'places.id')
            ->where('schedules.employee_id', $employee_id)
            ->where('schedules.schedule_date', $today)
            ->select('reservations.*', 'schedules.*', 'users.name as doctor_name', 'employees.qualification', 'places.name as place_name')
            ->get();

        $total_waiting = $reservation_data->where('approve', 0)->count();
        $total_all = $reservation_data->count();


        $result = [
            'total_waiting' => $total_waiting,
            'total_all' => $total_all,
        ];

        return response()->json([
            'status_code' => 200,
            'data' => $result,
            'message' => 'Success get today reservations',
        ]);
    }

    /**
     * Get Today Reservations
     * 
     * @return json
     * @param  mixed $request
     */

    public function getAllReservations(Request $request)
    {
        $user_id = auth()->user()->id;
        $employee_data = DB::table('employees')->where('user_id', $user_id)->first();
        $employee_id = $employee_data->id;

        $limit = $request->query('limit') ?? 10;
        $status = $request->query('status');

        if ($status != null) {

            $reservation_data = DB::table('reservations')
                ->leftJoin('schedules', 'reservations.schedule_id', '=', 'schedules.id')
                ->leftJoin('employees', 'schedules.employee_id', '=', 'employees.id')
                ->leftJoin('patients', 'reservations.patient_id', '=', 'patients.id')
                ->leftJoin('users', 'patients.user_id', '=', 'users.id')
                ->leftJoin('places', 'schedules.place_id', '=', 'places.id')
                ->where('schedules.employee_id', $employee_id)
                ->where('reservations.status', $status)
                ->select('reservations.*',  'users.name as patient_name', 'users.phone', 'places.name as place_name', 'schedule_date', 'schedule_time', 'schedule_time_end', 'nomor_urut')
                ->paginate($limit);
        } else {

            $reservation_data = DB::table('reservations')
                ->leftJoin('schedules', 'reservations.schedule_id', '=', 'schedules.id')
                ->leftJoin('employees', 'schedules.employee_id', '=', 'employees.id')
                ->leftJoin('patients', 'reservations.patient_id', '=', 'patients.id')
                ->leftJoin('users', 'patients.user_id', '=', 'users.id')
                ->leftJoin('places', 'schedules.place_id', '=', 'places.id')
                ->where('schedules.employee_id', $employee_id)
                ->select('reservations.*',  'users.name as patient_name', 'users.phone', 'places.name as place_name', 'schedule_date', 'schedule_time', 'schedule_time_end', 'nomor_urut')
                ->paginate($limit);
        }


        foreach ($reservation_data as $key => $value) {
            if ($value->bukti_pembayaran != null) {
                $reservation_data[$key]->bukti_pembayaran = $this->image_controller->getAccessibleImageURL($value->bukti_pembayaran);
            }
            if ($value->ktp != null) {
                $reservation_data[$key]->ktp = $this->image_controller->getAccessibleImageURL($value->ktp);
            }
            if ($value->surat_rujukan != null) {
                $reservation_data[$key]->surat_rujukan = $this->image_controller->getAccessibleImageURL($value->surat_rujukan);
            }
            if ($value->bpjs_card != null) {
                $reservation_data[$key]->bpjs_card = $this->image_controller->getAccessibleImageURL($value->bpjs_card);
            }
            if ($value->bpjs == 1) {
                $reservation_data[$key]->payment_name = 'BPJS';
            } else {
                $reservation_data[$key]->payment_name = 'TRANSFER';
            }
        }


        return response()->json([
            'status_code' => 200,
            'data' => $reservation_data,
            'message' => 'Success get all reservations',
        ]);
    }

    /**
     * Approve Or Reject Reservation
     * 
     * @return json
     * @param  mixed $request
     * 
     * 1 = approve
     * 3 = reject
     */

    public function approveOrRejectReservation(Request $request)
    {
        $reservation_id = $request->reservation_id;
        $status = $request->status;

        $reservation_data = DB::table('reservations')
            ->where('id', $reservation_id)
            ->first();

        $schedule_data = DB::table('schedules')
            ->leftJoin('places', 'schedules.place_id', '=', 'places.id')
            ->where('schedules.id', $reservation_data->schedule_id)
            ->first();


        $doctor_data = DB::table('employees')
            ->leftJoin('users', 'employees.user_id', '=', 'users.id')
            ->where('employees.id', $schedule_data->employee_id)
            ->first();

        $patient_data = DB::table('patients')
            ->leftJoin('users', 'patients.user_id', '=', 'users.id')
            ->where('patients.id', $reservation_data->patient_id)
            ->select('users.name', 'users.email', 'users.phone', 'users.id as user_id')
            ->first();

        $title = '';
        $message = '';

        if ($status == 1) {
            $title = 'Reservasi Disetujui';
            $message = 'Reservasi anda disetujui oleh Dokter ' . $doctor_data->name . ' di ' . $schedule_data->schedule_date . ' ' . $schedule_data->schedule_time . ' - ' . $schedule_data->schedule_time_end . ' di ' . $schedule_data->name . '. Silahkan datang ke tempat praktek sesuai jadwal yang telah ditentukan';
        } else {
            $title = 'Reservasi Ditolak';
            $message = 'Maaf, reservasi anda pada ' . $schedule_data->schedule_date . ' ' . $schedule_data->schedule_time . ' - ' . $schedule_data->schedule_time_end . ' di ' . $schedule_data->name . ' telah ditolak oleh Dokter ' . $doctor_data->name . '. Silahkan coba reservasi di waktu lain';
        }

        $data_notification = [
            'title' => $title,
            'message' => $message,
        ];

        $reservation = DB::table('reservations')
            ->where('id', $reservation_id)
            ->update([
                'approve' => 1,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if (!$reservation) {
           
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to approve or reject reservation',
            ]);
        }

        $notification_controller = new NotificationController();
        $data_notification = json_encode($data_notification);

        $notif_send = $notification_controller->saveNotifToDBArray([
            'type' => 'reservation',
            'notifiable_type' => 'patient',
            'notifiable_id' => $patient_data->user_id,
            'data' => $data_notification,
        ]);
        

        return response()->json([
            'status_code' => 200,
            'data' => $reservation_data,
            'notif_send' => $notif_send,
            'message' => 'Success approve or reject reservation',
        ]);
    }

    public function updateReservationStatus($reservation_id, $status)
    {
        $reservation = DB::table('reservations')
            ->where('id', $reservation_id)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if (!$reservation) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to update reservation status',
            ]);
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'Success update reservation status',
        ]);
    }
}
