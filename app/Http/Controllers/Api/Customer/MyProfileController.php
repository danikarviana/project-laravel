<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controllers\HasMiddleware;

class MyProfileController extends Controller implements HasMiddleware
{
    /**
     * middleware
     * 
     * @return array
     */
    public static function middleware(): array
    {
        return [
            'auth:api'
        ];
    }

    /**
     * index
     * 
     * @return void
     */
    public function index()
    {
        $profile = Customer::query()
            ->where('id', auth()->guard('api')->user()->id)
            ->firstOrFail();

        return response()->json([
            'success'  => true,
            'message'  => 'Detail Profil',
            'customer' => $profile
        ]);
    }

    /**
     * update
     * 
     * @param Request $request
     * @return void
     */
    public function update(Request $request)
    {
        //set validation
        $validator = Validator::make($request->all(), [
            'name'     => 'required',
            'email'    => 'required|unique:customers,email,' . auth()->guard('api')->user()->id,
            'password' => 'nullable|confirmed',
            'image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data'    => $validator->errors()
            ], 422);
        }

        //ambil profil customer yang login
        $profile = Customer::query()
            ->where('id', auth()->guard('api')->user()->id)
            ->firstOrFail();

        //jika ada upload gambar baru, simpan dan update
        if ($request->hasFile('image')) {

            //hapus gambar lama jika ada
            if ($profile->image) {
                Storage::delete('public/customers/' . basename($profile->image));
            }

            //upload gambar baru
            $image = $request->file('image');
            $image->storeAs('public/customers', $image->hashName());

            //simpan nama file ke database
            $profile->image = $image->hashName();
        }

        //update data profil
        $profile->name  = $request->name;
        $profile->email = $request->email;

        //update password jika diberikan
        if ($request->filled('password')) {
            $profile->password = bcrypt($request->password);
        }

        //simpan perubahan
        $profile->save();

        return response()->json([
            'success'  => true,
            'message'  => 'Update Profil Berhasil',
            'customer' => $profile
        ]);
    }
}
