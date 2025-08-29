<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Helper\GlobalHelper;
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
use App\Http\Resources\ApiResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\Global_;

class RegisterController extends Controller
{
    use ApiResponse;
    public function step1(Request $request): JsonResponse|ApiResource
    {
        if (!$request->has('password')) {
            $request->merge(['password' => Str::random(8)]);
        }
        // if (!config('services.vonage.key') || !config('services.vonage.secret')) {
        //     return ApiResource::make(
        //         status_code: 500,
        //         message: 'Vonage service not configured. Please contact support.',
        //     );
        // }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:png,jpg,jpeg',
            'email' => 'required|email|unique:users,email',
            'phone_code' => 'required|exists:countries,phone_code',
            'phone' => 'required|string|unique:users,phone',
            'gender' => 'nullable|in:male,female',
            'nationality_id' => 'required|exists:nationalities,id',
            'password' => 'required|string|min:8|max:50',
        ]);

        if ($validator->fails()) {
            return ApiResource::make(
                status_code: 422,
                message: $validator->errors()->first(),
            );
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $image = $request->file('image')->store('parents', 'public');
        }

        // $ok = Governorate::where('id', $data['governorate_id'])
        //     ->where('country_id', $data['country_id'])->exists();
        // if (!$ok) {
        //     return ApiResource::make(
        //         status_code: 422,
        //         message: 'Validation Faild Governorate does not belong to selected country',
        //     );
        // }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_code' => $data['phone_code'],
            'phone' => $data['phone'],
            'image' => $image ?? null,
            'gender' => $data['gender'] ?? null,
            'nationality_id' => $data['nationality_id'],
            'password' => Hash::make($data['password']),
            'registration_token' => (string) Str::uuid(),
            'is_complete' => false,
            'phone_verified_at' => null,
        ]);

        // Send OTP via Vonage
        $otp = OtpService::sendOtp($user->phone);

        if (!$otp) {
            return ApiResource::make(
                status_code: 500,
                message: 'Failed to send OTP. Please try again.',
            );
        }

        return ApiResource::make(
            status_code: 201,
            message: 'User Created Successfully , Please Verify your Phone Number',
            data: [
                'registration_token' => $user->registration_token,
                'next_endpoint' => 'api/register/verify-phone',
            ]
        );
    }

    public function verifyPhone(Request $request): JsonResponse|ApiResource
    {
        $validator = Validator::make($request->all(), [
            'registration_token' => 'required|string',
            'otp' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ApiResource::make(
                status_code: 422,
                message: $validator->errors()->first(),
            );
        }

        $data = $validator->validated();


        //get the user that currently register
        $user = $this->getRegistrationUser($request);

        // Verify OTP
        $isVerified = OtpService::verifyOtp($user->phone, $data['otp']);

        if (!$isVerified) {
            return ApiResource::make(
                status_code: 422,
                message: 'Invalid OTP'
            );
        }

        $user->update(['phone_verified_at' => now()]);

        return ApiResource::make(
            status_code: 200,
            message: 'Phone verfied successfully',
            data: [
                'next_endpoint' => 'api/register/step2'
            ]
        );
    }

    public function step2(Request $request): JsonResponse|ApiResource
    {
        $user = $this->getRegistrationUser($request);

        GlobalHelper::checkPhoneVerification($user->phone_verified_at);

        $validator = Validator::make($request->all(), [
            'child.name' => 'required|string|max:255',
            'child.birth_date' => 'required|date',
            'child.gender' => 'required|in:male,female',
            'child.country_id' => 'nullable|exists:countries,id',
            'child.governorate_id' => 'required|exists:governorates,id',
            'child.nationality_id' => 'required|exists:nationalities,id',
            // 'child.city' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return ApiResource::make(
                status_code: 422,
                message: $validator->errors()->first()
            );
        }

        $data = $validator->validated();

        $childData = $data['child'];

        // Upload child image if exists
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('children', 'public');
        }

        //check governorate related to country
        $ok = Governorate::where('id', $childData['governorate_id'])
            ->orWhere('country_id', $childData['country_id'] ?? null)
            ->exists();
        if (!$ok) {
            return ApiResource::make(
                status_code: 422,
                message: 'Validation Faild Governorate does not belong to selected country',
            );
        }
        $child = $user->children()->first();

        if ($child) {
            $child->update([
                'name' => $childData['name'],
                'birth_date' => $childData['birth_date'],
                'gender' => $childData['gender'],
                // 'nationality' => $childData['nationality'] ?? null,
                'country_id' => $childData['country_id'] ?? null,
                'governorate_id' => $childData['governorate_id'],
                'nationality_id' => $childData['nationality_id'],
                // 'city' => $childData['city'] ?? null,
                'image' => $imagePath,
            ]);
        } else {
            $child = $user->children()->create([
                'name' => $childData['name'],
                'birth_date' => $childData['birth_date'],
                'gender' => $childData['gender'],
                'country_id' => $childData['country_id'] ?? null,
                'governorate_id' => $childData['governorate_id'],
                'nationality_id' => $childData['nationality_id'],
                // 'nationality' => $childData['nationality'] ?? null,
                // 'city' => $childData['city'] ?? null,
                'image' => $imagePath,
            ]);
        }

        return ApiResource::make(
            status_code: 201,
            message: 'Step 2 Completed, Child Information Saved',
            data: [
                'child_id' => $child->id,
                'next_endpoint' => 'api/register/step3'
            ]
        );
    }

    public function step3(Request $request): JsonResponse|ApiResource
    {
        $user = $this->getRegistrationUser($request);

        GlobalHelper::checkPhoneVerification($user->phone_verified_at);

        if (!$user->children()->exists()) {
            return ApiResource::make(
                status_code: 422,
                message: 'Child Information Required',
                data: [
                    'next_endpoint' => 'api/register/step2'
                ]
            );
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
            return ApiResource::make(
                status_code: 422,
                message: $validator->errors()->first()
            );
        }

        $data = $validator->validated();
        $medicalData = $data['medical'];
        $child = $user->children()->latest()->first();

        if (!$child) {
            return ApiResource::make(
                status_code: 400,
                message: 'Child information required before medical info .'
            );
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

        return ApiResource::make(
            status_code: 201,
            message: 'Step 3 Completed , Medical Information Saved',
            data: [
                'medical_id' => $medical->id,
                'child_id' => $child->id,
                'next_endpoint' => 'api/register/step4',
            ]
        );
    }

    public function step4(Request $request): JsonResponse|ApiResource
    {
        $user = $this->getRegistrationUser($request);

        GlobalHelper::checkPhoneVerification($user->phone_verified_at);

        $validator = Validator::make($request->all(), [
            'ability.can_sit' => 'required|in:yes,no,with_help',
            'ability.can_walk' => 'required|in:yes,no,with_help',
            'ability.uses_hands' => 'required|in:yes,no,one_hand',
            'ability.target_goals' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ApiResource::make(
                status_code: 422,
                message: $validator->errors()->first()
            );
        }

        $data = $validator->validated();
        $abilityData = $data['ability'];
        $child = $user->children()->latest()->first();

        if (!$child) {
            return ApiResource::make(
                status_code: 400,
                message: 'Child information required before medical info .'
            );
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

        return ApiResource::make(
            status_code: 201,
            message: 'Register Completed Successfully',
            data: [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'child_id' => $child->id,
            ]
        );
    }

    // Helper function to find user by registration_token
    protected function getRegistrationUser(Request $request): User
    {

        // Accept token either as Bearer token or in request body
        // $authHeader = $request->header('Authorization');
        // if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
        //     $token = substr($authHeader, 7);
        // }

        $token = $request->input('registration_token');

        if (!$token) {
            abort(ApiResource::make(
                status_code: 422,
                message: 'Register token Required'
            ));
        }

        $user = User::where('registration_token', $token)->first();

        if (!$user) {
            abort(ApiResource::make(
                status_code: 422,
                message: 'Invalid Registeration Token'
            ));
        }

        return $user;
    }
}
