<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /**
     * Handle login request (mendukung pemanggilan ke index maupun store)
     */
    public function index(Request $request)
    {
        return $this->authenticate($request);
    }

    public function store(Request $request)
    {
        return $this->authenticate($request);
    }

    private function authenticate(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Mengambil Kredensial Email & Password
        $credentials = $request->only('email', 'password');

        // 3. Proses Autentikasi Menggunakan JWT Guard
        // CATATAN: Ganti 'api' menjadi 'api_customer' jika di config/auth.php Anda membuat guard khusus customer
        if (!$token = auth()->guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password Anda salah.'
            ], 401);
        }

        // 4. Jika Login Sukses, Kembalikan Data User dan Token JWT
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'user'    => auth()->guard('api')->user(),
            'token'   => $token,
        ], 200);
    }
}
