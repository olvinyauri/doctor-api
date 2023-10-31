<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Login a user and create a token
     * @param Request $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */

    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (!auth()->attempt($credentials)) {
            return response()->json([
                'status_code' => 401,
                'message' => 'Email atau password yang Anda masukkan salah',
            ], 401);
        }

        $token = auth()->user()->createToken('auth_token')->plainTextToken;

        return $this->respondWithToken($token, auth()->user());
    }

    /**
     * Register a user and create a token
     * @param Request $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */

    public function register(Request $request)
    {
        try {
            $role_id = $request->role_id ?? 3;

            $user = User::create([
                'role_id' => $request->role_id ?? 3,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'username' => $request->username,
                'password' => $request->password,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if ($role_id == 2) {
                $employee = DB::table('employees')
                    ->insert([
                        'user_id' => $user->id,
                        'qualification' => $request->qualification,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            } else if ($role_id == 3) {
                $patient = DB::table('patients')
                    ->insert([
                        'user_id' => $user->id,
                        'access_code' => $request->access_code,
                        'height' => $request->height,
                        'weight' => $request->weight,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }
            

            if (!$user) {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Error in creating user',
                ], 500);
            }

            return response()->json([
                'status_code' => 201,
                'message' => 'Successfully created user',
                'data' => $user,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 401,
                'message' => $th->getMessage(),
            ], 401);
        }
    }

    /**
     * Logout a user and invalidate the token
     * 
     * @return \Illuminate\Http\JsonResponse
     */

    public function logout()
    {
        try {
            $token = request()->header('Authorization');

            if (!$token) {
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Token not provided',
                ], 401);
            }

            // get token from string
            $token = explode(' ', $token)[1];

            // invalidate token
            auth()->user()->tokens()->where('id', $token)->delete();

            return response()->json([
                'status_code' => 200,
                'message' => 'Successfully logged out',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 401,
                'message' => $th->getMessage(),
            ], 401);
        }
    }

    /**
     * Refresh a token
     * 
     * @return \Illuminate\Http\JsonResponse
     */

    public function refresh()
    {
        $token = auth()->user()->createToken('auth_token')->plainTextToken;

        return $this->respondWithToken($token, auth()->user());
    }

    /**
     * Get the token array structure
     * 
     * @param string $token
     * 
     * @return \Illuminate\Http\JsonResponse
     */

    protected function respondWithToken($token, $user)
    {

        if ($user->role_id == 1) {
        } else if ($user->role_id == 2) {
            $employee_data = DB::table('employees')
                ->where('user_id', $user->id)
                ->first();
        } else {
            $patient_data = DB::table('patients')
                ->where('user_id', $user->id)
                ->first();
        }
        $user->employee_id = $employee_data->id ?? null;
        $user->patient_id = $patient_data->id ?? null;
        $user->access_code = $patient_data->access_code ?? null;
        return response()->json([
            'status_code' => 200,
            'message' => 'Successfully logged in',
            'access_token' => $token,
            'user' => $user,
            'token_type' => 'bearer',
        ]);
    }

    /**
     * Get the authenticated user
     * 
     * @return \Illuminate\Http\JsonResponse
     */

    public function me()
    {
        $user = auth()->user();
        if ($user->role_id == 1) {
        } else if ($user->role_id == 2) {
            $employee_data = DB::table('employees')
                ->where('user_id', $user->id)
                ->first();
        } else {
            $patient_data = DB::table('patients')
                ->where('user_id', $user->id)
                ->first();
        }

        $user->employee_data = $employee_data ?? null;
        $user->patient_data = $patient_data ?? null;

        return response()->json(
            [
                'status_code' => 200,
                'message' => 'Successfully get user data',
                'data' => auth()->user(),
            ]
        );
    }

    /**
     * Get default response if not login yet
     * 
     * @return \Illuminate\Http\JsonResponse
     */

    public function unAuthorized()
    {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    /**
     * Update Password with check old password
     */
    public function updatePassword(Request $request)
    {
        try {
            $user = auth()->user();
            $old_password = $request->old_password;
            $new_password = $request->new_password;

            // check old password
            if (!password_verify($old_password, $user->password)) {
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Old password is wrong',
                ], 401);
            }

            $user = DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'password' => bcrypt($new_password),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            return response()->json([
                'status_code' => 200,
                'message' => 'Update password successfully',
                'data' => $user
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    /**
     * Update Profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();
            $name = $request->name;
            $email = $request->email;
            $phone = $request->phone;
            $address = $request->address;
            $birth_date = $request->birth_date;
            $gender = $request->gender;

            $data_to_be_updated = [];

            if ($name != null) {
                $data_to_be_updated['name'] = $name;
            }

            if ($email != null) {
                $data_to_be_updated['email'] = $email;
            }

            if ($phone != null) {
                $data_to_be_updated['phone'] = $phone;
            }

            if ($address != null) {
                $data_to_be_updated['address'] = $address;
            }

            if ($birth_date != null) {
                $data_to_be_updated['birth_date'] = $birth_date;
            }

            if($gender != null){
                $data_to_be_updated['gender'] = $gender;
            }

            $user = DB::table('users')
                ->where('id', $user->id)
                ->update($data_to_be_updated);

            return response()->json([
                'status_code' => 200,
                'message' => 'Update profile successfully',
                'data' => $user
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }
}
