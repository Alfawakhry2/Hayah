<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Models\Child;
use App\Models\Governorate;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use ApiResponse;
    public function step1(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:png,jpg,jpeg',
            'email' => 'required|email|unique:users,email',
            'phone_code' => 'required|exists:countries,phone_code',
            'phone' => 'required|string|unique:users,phone',
            'country_id' => 'required|exists:countries,id',
            'governorate_id' => 'required|exists:governorates,id',
            'nationality_id' => 'required|exists:nationalities,id',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, "Validation Faild", $validator->errors());
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $image = $request->file('image')->store('parents' , 'public');
        }

        $ok = Governorate::where('id', $data['governorate_id'])
            ->where('country_id', $data['country_id'])->exists();
        if (!$ok) {
            return $this->errorResponse(422, 'Governorate does not belong to selected country');
        }


        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_code' => $data['phone_code'],
            'phone' => $data['phone'],
            'image' =>$image ?? null ,
            'country_id' => $data['country_id'],
            'governorate_id' => $data['governorate_id'],
            'nationality_id' => $data['nationality_id'],
            'password' => Hash::make($data['password']),
            'registration_token' => (string) Str::uuid(),
            'is_complete' => false,
            'phone_verified_at' => null,
        ]);

        // Send OTP via Vonage
        $otp = OtpService::sendOtp($user->phone);

        if (!$otp) {
            // return response()->json([
            //     'message' => 'Failed to send OTP. Please try again.'
            // ], 500);
            return $this->errorResponse(500, "Failed to send OTP. Please try again.");
        }

        // return response()->json([
        //     'message' => 'User created successfully. Please verify your phone.',
        //     'registration_token' => $user->registration_token,
        //     'next_endpoint' => 'api/register/verify-phone'
        // ], 201);

        return $this->successResponse(201, 'User Created Successfully , Please Verify your Phone Number', [
            'registration_token' => $user->registration_token,
            'next_endpoint' => 'api/register/verify-phone',
        ]);
    }

    public function verifyPhone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'registration_token' => 'required|string',
            'otp' => 'required|string'
        ]);

        if ($validator->fails()) {
            // return response()->json([
            //     'message' => 'Validation failed',
            //     'errors' => $validator->errors()
            // ], 422);

            return $this->errorResponse(422, 'Validation Faild', $validator->errors());
        }

        $data = $validator->validated();
        //get the user that currently register
        $user = $this->getRegistrationUser($request);

        // Verify OTP
        $isVerified = OtpService::verifyOtp($user->phone, $data['otp']);

        if (!$isVerified) {
            // return response()->json([
            //     'message' => 'Invalid OTP code'
            // ], 422);
            return $this->errorResponse(422, 'Invalid OTP');
        }

        $user->update(['phone_verified_at' => now()]);

        // return response()->json([
        //     'message' => 'Phone verified successfully',
        //     'next_endpoint' => 'api/register/step2'
        // ]);
        return $this->successResponse(200, 'phone verfied successfully', ['next_endpoint' => 'api/register/step2']);
    }

    public function step2(Request $request): JsonResponse
    {
        $user = $this->getRegistrationUser($request);

        if (!$user->phone_verified_at) {
            // return response()->json([
            //     'message' => 'Phone verification required',
            //     'go_to_endPoint' => 'api/register/verify-phone'
            // ], 403);

            return $this->errorResponse(403, 'Phone Verification Required', ['next_endpoint' => 'api/register/verify-phone']);
        }

        $validator = Validator::make($request->all(), [
            'child.name' => 'required|string|max:255',
            'child.birth_date' => 'required|date',
            'child.gender' => 'required|in:male,female',
            // 'child.nationality_id' => 'nullable|string|max:255',
            'child.city' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            // return response()->json([
            //     'message' => 'Validation failed',
            //     'errors' => $validator->errors()
            // ], 422);
            return $this->errorResponse(422, 'Validation Faild', $validator->errors());
        }

        $data = $validator->validated();

        $childData = $data['child'];

        // Upload child image if exists
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('children', 'public');
        }

        $child = $user->children()->first();

        if ($child) {
            $child->update([
                'name' => $childData['name'],
                'birth_date' => $childData['birth_date'],
                'gender' => $childData['gender'],
                // 'nationality' => $childData['nationality'] ?? null,
                'city' => $childData['city'] ?? null,
                'image' => $imagePath,
            ]);
        } else {
            $child = $user->children()->create([
                'name' => $childData['name'],
                'birth_date' => $childData['birth_date'],
                'gender' => $childData['gender'],
                // 'nationality' => $childData['nationality'] ?? null,
                'city' => $childData['city'] ?? null,
                'image' => $imagePath,
            ]);
        }

        // return response()->json([
        //     'message' => 'Step 2 completed. Child information saved.',
        //     'child_id' => $child->id,
        //     'next_endpoint' => 'api/register/step3'
        // ]);
        return $this->successResponse(200, 'Step 2 Completed , Child Information Saved', [
            'child_id' => $child->id,
            'next_endpoint' => 'api/register/step3',
        ]);
    }

    public function step3(Request $request): JsonResponse
    {
        $user = $this->getRegistrationUser($request);

        if (!$user->phone_verified_at) {
            // return response()->json([
            //     'message' => 'Phone verification required',
            //     'go_to_endPoint' => 'api/register/verify-phone'
            // ], 403);

            return $this->errorResponse(403, 'Phone Verification Required', ['next_endpoint' => 'api/register/verify-phone']);
        }

        $validator = Validator::make($request->all(), [
            'medical.age' => 'required|integer|min:5|max:16',
            'medical.length' => 'required|numeric|min:0',
            'medical.weight' => 'required|numeric|min:0',
            'medical.diagnosis' => 'required|in:syndrome,genetic_mutation,oxygen_deficiency',
            'medical.severity' => 'required|in:mild,medium,severe',
            'medical.has_seizures' => 'required|boolean',
            'medical.on_medication' => 'required|boolean',
            'medical.medication_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            // return response()->json([
            //     'message' => 'Validation failed',
            //     'errors' => $validator->errors()
            // ], 422);
            return $this->errorResponse(422, 'Validation Faild', $validator->errors());
        }

        $data = $validator->validated();
        $medicalData = $data['medical'];
        $child = $user->children()->latest()->first();

        if (!$child) {
            // return response()->json([
            //     'message' => 'Child information required before medical info'
            // ], 400);

            return $this->errorResponse(400, 'Child information required before medical info .');
        }

        $medical = $child->medicalInfo()->first();

        if ($medical) {
            $medical->update([
                'age' => $medicalData['age'],
                'length' => $medicalData['length'],
                'weight' => $medicalData['weight'],
                'diagnosis' => $medicalData['diagnosis'],
                'severity' => $medicalData['severity'],
                'has_seizures' => $medicalData['has_seizures'],
                'on_medication' => $medicalData['on_medication'],
                'medication_name' => $medicalData['medication_name'] ?? null,
            ]);
        } else {
            //medical info not found , that related to this parent
            $medical = $child->medicalInfo()->create([
                'age' => $medicalData['age'],
                'length' => $medicalData['length'],
                'weight' => $medicalData['weight'],
                'diagnosis' => $medicalData['diagnosis'],
                'severity' => $medicalData['severity'],
                'has_seizures' => $medicalData['has_seizures'],
                'on_medication' => $medicalData['on_medication'],
                'medication_name' => $medicalData['medication_name'] ?? null,
            ]);
        }

        // return response()->json([
        //     'message' => 'Step 3 complete. Medical information added.',
        //     'medical_id' => $medical->id,
        //     'child_id' => $child->id,
        //     'next_endpoint' => 'api/register/step4'
        // ]);

        return $this->successResponse(201, 'Step 3 Completed , Medical Information Saved', [
            'medical_id' => $medical->id,
            'child_id' => $child->id,
            'next_endpoint' => 'api/register/step4',
        ]);
    }

    public function step4(Request $request): JsonResponse
    {
        $user = $this->getRegistrationUser($request);

        if (!$user->phone_verified_at) {
            // return response()->json([
            //     'message' => 'Phone verification required',
            //     'go_to_endpoint' => 'api/register/verify-phone'
            // ], 403);
            return $this->errorResponse(403, 'Phone Verification Required', ['next_endpoint' => 'api/register/verify-phone']);
        }

        $validator = Validator::make($request->all(), [
            'ability.can_sit' => 'required|in:yes,no,with_help',
            'ability.can_walk' => 'required|in:yes,no,with_help',
            'ability.uses_hands' => 'required|in:yes,no,one_hand',
            'ability.target_goals' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            // return response()->json([
            //     'message' => 'Validation failed',
            //     'errors' => $validator->errors()
            // ], 422);
            return $this->errorResponse(422, 'Validation Faild', $validator->errors());
        }

        $data = $validator->validated();
        $abilityData = $data['ability'];
        $child = $user->children()->latest()->first();

        if (!$child) {
            // return response()->json([
            //     'message' => 'Child information required before ability info'
            // ], 400);

            return $this->errorResponse(400, 'Child information required before Ability information .');
        }

        $ability = $child->ability()->first();
        if ($ability) {
            $ability->update([
                'can_sit' => $abilityData['can_sit'],
                'can_walk' => $abilityData['can_walk'],
                'uses_hands' => $abilityData['uses_hands'],
                'target_goals' => $abilityData['target_goals'] ?? null,
            ]);
        } else {
            $ability = $child->ability()->create([
                'can_sit' => $abilityData['can_sit'],
                'can_walk' => $abilityData['can_walk'],
                'uses_hands' => $abilityData['uses_hands'],
                'target_goals' => $abilityData['target_goals'] ?? null,
            ]);
        }

        // Mark registration complete
        $user->update(['is_complete' => true]);

        $token = JWTAuth::fromUser($user);

        // return response()->json([
        //     'message' => 'Register Completed , Welcome To Our App',
        //     'access_token' => $token,
        //     'token_type' => 'bearer',
        //     'expires_in' => auth('api')->factory()->getTTL() * 60,
        //     'child_id' => $child->id,
        // ], 200);

        return $this->successResponse(201, 'Register Completed Successfully', [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'child_id' => $child->id,
        ]);
    }

    // Helper function to find user by registration_token
    protected function getRegistrationUser(Request $request): User
    {
        $token = null;

        // Accept token either as Bearer token or in request body
        // $authHeader = $request->header('Authorization');
        // if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
        //     $token = substr($authHeader, 7);
        // }

        if (!$token) {
            $token = $request->input('registration_token');
        }

        if (!$token) {
            abort($this->errorResponse(401, 'Register token Required'));
            // $user =  $this->errorResponse(401 , 'Registeration Token Required');
        }

        $user = User::where('registration_token', $token)->first();

        if (!$user) {
            abort($this->errorResponse(401, 'Invalid Registeration Token'));
            // $user =  $this->errorResponse(401 , 'Invalid Registeration Token');

        }

        return $user;
    }
}
