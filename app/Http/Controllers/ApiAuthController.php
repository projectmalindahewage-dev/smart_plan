<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
class ApiAuthController extends Controller {
 public function register(Request $request): JsonResponse { $data=$request->validate(['name'=>['required','string','max:255'],'email'=>['required','string','email','max:255','unique:users,email'],'password'=>['required','confirmed',Password::defaults()],'device_name'=>['nullable','string','max:255']]); $user=User::create(['name'=>$data['name'],'email'=>$data['email'],'password'=>Hash::make($data['password'])]); return $this->tokenResponse($user,$data['device_name']??'api-client',201); }
 public function login(Request $request): JsonResponse { $data=$request->validate(['email'=>['required','email'],'password'=>['required','string'],'device_name'=>['nullable','string','max:255']]); $user=User::where('email',$data['email'])->first(); if (! $user || ! Hash::check($data['password'],$user->password)) return response()->json(['message'=>'Invalid credentials.'],422); return $this->tokenResponse($user,$data['device_name']??'api-client'); }
 public function logout(Request $request): JsonResponse { $request->user()->currentAccessToken()?->delete(); return response()->json(['message'=>'Logged out successfully.']); }
 private function tokenResponse(User $user,string $deviceName,int $status=200): JsonResponse { return response()->json(['message'=>'Authenticated successfully.','token'=>$user->createToken($deviceName)->plainTextToken,'token_type'=>'Bearer','user'=>$user],$status); }
}