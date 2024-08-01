<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Log; // Pastikan untuk mengimpor Log

class AuthController extends Controller
{
    private $response = [
        'message' => null,
        'data' => null,
    ];

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'prodi' => 'required',
                'nip' => 'required|unique:users,nip',
                'password' => 'required',
            ]);

            $data = User::create([
                'name' => $request->name,
                'prodi' => $request->prodi,
                'nip' => $request->nip,
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'message' => "success",
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error during registration: ' . $e->getMessage());
            return response()->json([
                'message' => 'failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            Log::info('Starting login process');
            
            $request->validate([
                'nip' => 'required',
                'password' => 'required',
            ]);

            Log::info('Validation passed');
            
            $user = User::where('nip', $request->nip)->first();
            
            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('Login failed: Invalid NIP or password');
                return response()->json([
                    'message' => 'failed',
                    'error' => 'NIP atau password salah',
                ], 401);
            }

            if (!$request->has('device_name')) {
                Log::warning('Login failed: device_name is missing');
                return response()->json([
                    'message' => 'failed',
                    'error' => 'Device name is required',
                ], 400);
            }

            Log::info('Creating token');
            $token = $user->createToken($request->device_name)->plainTextToken;
            
            $this->response['message'] = 'success';
            $this->response['data'] = [
                'token' => $token
            ];

            Log::info('Login successful');
            return response()->json($this->response, 200);
        } catch (\Exception $e) {
            Log::error('Error during login: ' . $e->getMessage());
            return response()->json([
                'message' => 'failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function me()
    {
        $user = Auth::user();

        $this->response['message'] = 'success';
        $this->response['data'] = $user;

        return response()->json($this->response, 200);
    }

    public function logout()
    {
        $logout = auth()->user()->currentAccessToken()->delete();

        $this->response['message'] = 'success';

        return response()->json($this->response, 200);
    }
}
