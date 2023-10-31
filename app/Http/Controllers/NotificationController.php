<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Save Notif To DB
     */
    public function saveNotifToDB(Request $request)
    {
        try {
            $notif = DB::table('notifications')->insert([
                'type' => $request->type,
                'notifiable_type' => $request->notifiable_type,
                'notifiable_id' => $request->notifiable_id,
                'data' => $request->data,
                'created_at' => Date('Y-m-d H:i:s'),
                'updated_at' => Date('Y-m-d H:i:s')
            ]);

            if (!$notif) {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Failed to save notification'
                ]);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Success to save notification'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    // save notif to db using array not Request
    public function saveNotifToDBArray($data)
    {
        try {
            // generate random uid
            $uid = uniqid();
            $notif = DB::table('notifications')->insert([
                'id' => $uid,
                'type' => $data['type'],
                'notifiable_type' => $data['notifiable_type'],
                'notifiable_id' => $data['notifiable_id'],
                'data' => $data['data'],
                'created_at' => Date('Y-m-d H:i:s'),
                'updated_at' => Date('Y-m-d H:i:s')
            ]);

            if (!$notif) {
                return json_encode([
                    'status_code' => 500,
                    'message' => 'Failed to save notification'
                ]);
            }

            return json_encode([
                'status_code' => 200,
                'message' => 'Success to save notification'
            ]);
        } catch (\Throwable $th) {
            return json_encode([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Get All Notif
     */
    public function getAllNotif(Request $request)
    {
        try {
            $user = auth()->user();
            $limit = $request->limit ?? 10;

            $notifs = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($limit);
                

            return response()->json([
                'status_code' => 200,
                'message' => 'Get all notifications',
                'data' => $notifs,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Read Notif
     
     */
    public function readNotif(Request $request)
    {
        try {
            $id = $request->id;

            $notif = DB::table('notifications')
                ->where('id', $id)
                ->update([
                    'read_at' => Date('Y-m-d H:i:s')
                ]);

            if (!$notif) {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Failed to read notification'
                ]);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Success to read notification'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }
}
