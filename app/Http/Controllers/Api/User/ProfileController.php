<?php

namespace App\Http\Controllers\Api\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\ApiResource;
use App\Http\Controllers\Controller;
use App\Models\MedicalInfo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        return ApiResource::make(status_code: 200, message: "User Profile", data: $user);
    }


    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            // User info
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp',
            'gender' => 'sometimes|in:male,female',
            'nationality_id' => 'sometimes|exists:nationalities,id',
            'name' => 'sometimes|string|max:255',
            'phone_code' => 'sometimes|exists:countries,phone_code',
            'phone' => 'sometimes|unique:users,phone,' . $user->id,
            'email' => 'sometimes|unique:users,email,' . $user->id,

            // Child medical info
            'age' => 'sometimes|integer',
            'length' => 'sometimes|integer',
            'weight' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return ApiResource::make(
                status_code: 422,
                message:  $validator->errors()->first(),
            );
        }

        $validated = $validator->validated();

        // Update user
        $user->update($validated);

        // Update medical info (assuming one child)
        $child = $user->children()->first();
        if ($child && $child->medicalInfo) {
            $child->medicalInfo->update([
                'age' => $validated['age'] ?? $child->medicalInfo->age,
                'length' => $validated['length'] ?? $child->medicalInfo->length,
                'weight' => $validated['weight'] ?? $child->medicalInfo->weight,
            ]);
        }

        // Handle image upload
        if ($request->hasFile('image')) {

            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }
            $imagePath = $request->file('image')->store('users', 'public');
            $user->image = $imagePath;
            $user->save();
        }

        return ApiResource::make(
            status_code: 200,
            message: "Profile Data Saved ",
            data: $user->load('children.medicalInfo')
        );
    }


    public function destroy(){
        $user = Auth::user();

        $user->delete();
        if($user->image){
            Storage::disk('public')->delete($user->image);
        }

        return ApiResource::make(status_code:200 , message:"Account Deleted");

    }
}
