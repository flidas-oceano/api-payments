<?php
namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\User;
use Laravel\Passport\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;

class PassportAuthController extends Controller
{


    public function register(Request $request)
    {
        try {
            $result = $request->validate([
                'name' => 'required',
                'email' => 'required|string|unique:users',
                'password' => 'required|string|confirmed',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);


        return response()->json([
            'message'=>'Usuario creado.',
        ], 200);
    }

    public function login(Request $request)
    {

        try {
            $result = $request->validate([
                'email' => 'required',
                "password" => "required",
                'remember_me' => 'boolean',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->validator->errors()
            ], 422);
        }

        $credentials= ['email' => $request->email, 'password' => $request->password];

        if(auth()->attempt($credentials)){
            $token = $request->user()->createToken($request->email)->accessToken;


            return response()->json([
                'access_token'=> $token,
                'token_type'=>'Bearer',
            ]);

        }else{
            return response()->json(['messagge' => 'Error en las credenciales.'],401);
        }



    }

    public function logout(Request $request)
    {
        $user = User::find($request->user_id);

        if ($user) {

            $user->tokens->each(function($token, $key) {
                $token->revoke();
             });

            return response()->json([
                'message' => 'Successfully logged out',
            ]);

        }

        return response()->json([
            'message' => 'Error logging out',
        ], 401);
    }

    public function user(Request $request)
    {
        $users=User::all();
        return response()->json($users);
    }

    public function expiredToken(Request $request){
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $tokens = $user->tokens->map(function ($token) {
                $tiempo_restante = $token->expires_at ? $token->expires_at->diffInSeconds(now()) : null;
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at,
                    "user_id" => $token->user_id,
                    "client_id" => $token->client_id,
                    "name" => $token->name,
                    "scopes" => $token->scopes,
                    "revoked" => $token->revoked,
                    "created_at" => $token->created_at,
                    "updated_at" => $token->updated_at,
                    "expires_at" => $token->expires_at,
                    'cabonnow' => Carbon::now(),
                    'tokenExpired' => $token->expires_at && $token->expires_at->lt(Carbon::now()),
                    'tiempo_restante' => $tiempo_restante,
                ];
            });
            return response()->json(['tokens' => $tokens]);
        } else {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    }

    public function tokenIsValid(Request $request){
        try {
            $data = $request->user()->token();

            $check = Auth::guard('api')->check();
        return response()->json([
            'data' => $data,
            'isValid' => $check,
            'user' => $request->user()
        ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }
}
