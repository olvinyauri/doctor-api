<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnnouncementController extends Controller
{

    /**
     * Get All Announcement
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllAnnouncement()
    {
        try {
            $limit = request()->query('limit') ?? 10;

            $announcement = DB::table('announcements')
                ->join('employees', 'announcements.employee_id', '=', 'employees.id')
                ->leftJoin('users', 'employees.user_id', '=', 'users.id')
                ->select('announcements.*', 'users.name as employee_name', 'employees.qualification as employee_qualification')
                ->paginate($limit);

            $imageController = new ImageController();

            foreach ($announcement as $key => $value) {
                if ($value->image != null) {
                    $value->image = $imageController->getAccessibleImageURL($value->image);
                }
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Get all announcement',
                'meta' => $announcement
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Get Announcement By ID
     * 
     * @param Request $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAnnouncementById(Request $request)
    {
        try {
            $id = $request->id;
            $announcement = DB::table('announcements')
                ->where('id', $id)
                ->join('employees', 'announcements.employee_id', '=', 'employees.id')
                ->leftJoin('users', 'employees.user_id', '=', 'users.id')
                ->select('announcements.*', 'users.name as employee_name', 'employees.qualification as employee_qualification')
                ->get()->first();

            return response()->json([
                'status_code' => 200,
                'message' => 'Get detail announcement',
                'data' => $announcement
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Create Announcement
     * 
     * @param Request $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function createAnnouncement(Request $request)
    {
        try {
            $image_path = null;

            if ($request->image != null) {
                $this->validate($request, [
                    'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                ]);
                $image_path = $request->file('image')->store('announcement_image');
            }

            $announcement = DB::table('announcements')->insert([
                'employee_id' => $request->employee_id,
                'title' => $request->title,
                'content' => $request->content,
                'image' => $image_path,
                'created_at' => Date('Y-m-d H:i:s'),
                'updated_at' => Date('Y-m-d H:i:s')
            ]);

            if (!$announcement) {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Failed to create announcement'
                ]);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Success to create announcement'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Update Announcement
     * 
     * @param Request $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAnnouncement(Request $request)
    {
        try {
            $image_path = null;

            if ($request->image != null) {
                $this->validate($request, [
                    'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                ]);
                $image_path = $request->file('image')->store('announcement_image');
            }

            $data_to_be_updated = [];

            if ($request->employee_id != null) {
                $data_to_be_updated['employee_id'] = $request->employee_id;
            }

            $data_to_be_updated['title'] = $request->title;

            $data_to_be_updated['content'] = $request->content;

            $data_to_be_updated['image'] = $image_path;

            $announcement = DB::table('announcements')
                ->where('id', $request->id)
                ->update($data_to_be_updated);

            if (!$announcement) {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Failed to update announcement'
                ]);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Success to update announcement'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Delete Announcement
     * 
     * @param Request $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAnnouncement(Request $request)
    {
        try {
            $announcement = DB::table('announcements')
                ->where('id', $request->id)
                ->delete();

            if (!$announcement) {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Failed to delete announcement'
                ]);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Success to delete announcement'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }
}
