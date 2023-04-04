<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Token;

class PassportAuthController extends Controller
{
    // public function register(Request $request)
    // {
    //     $this->validate($request, [
    //         'name' => 'required|min:4',
    //         'email' => 'required|email',
    //         'password' => 'required|min:8',
    //     ]);
 
    //     $user = User::create([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'password' => bcrypt($request->password)
    //     ]);
       
    //     $token = $user->createToken('LaravelAuthApp')->accessToken;
 
    //     return response()->json(['token' => $token], 200);
    // }
    // public function login(Request $request)
    // {
    //     $data = [
    //         'email' => $request->email,
    //         'password' => $request->password
    //     ];
 
    //     if (auth()->attempt($data)) {
    //         $token = auth()->user()->createToken('LaravelAuthApp')->accessToken;
    //         return response()->json(['token' => $token], 200);
    //     } else {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }
    // }   
    // public function logout(Request $request)
    // {
    //     $user = Auth::guard('api')->user();
    //     $user->token()->revoke();

    //     return response()->json(['message' => 'Successfully logged out']);
    // }
    
    // public function users(Request $request)
    // {
    //     $users = User::all();
    //     return response()->json([
    //         'users' => $users
    //     ]); 
    // }  

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

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
       
        $user->save(); 
        
        // $token = $user->createToken('LaravelAuthApp')->accessToken;
        
        // return response()->json([
        //     'token'=> $token,
        //     'token_type'=>'Bearer',
        // ], 200);
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

        if(!Auth::attempt(['email' => $request->email, 'password' => $request->password]))
            return response()->json(['messagge' => 'Error en las credenciales.'],401);

        $user = $request->user();
        $tokenRes = $user->createToken('Personal Access Token');
        $token = $tokenRes->token;

        if($request->remember_me)
            // $token->expires_at= Carbon::now()->addMinutes(60);
            $token->expires_at= Carbon::now()->addSeconds(10);

        $token->save();

        return response()->json([
            'access_token'=> $tokenRes->accessToken,
            'token_type'=>'Bearer',
            'expires_at'=> Carbon::parse(
                $tokenRes->token->expires_at
            )->toDateTimeString(),
            'now' => now(),
            'expires_in'=> $tokenRes->token->expires_at->diffInSeconds(now()),
            'token_expired' => Carbon::parse($tokenRes->token->expires_at)->isPast()
        ]);
    } 
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'messagge' => 'Successfully logged out'
        ]);
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
        // $credentials = $request->only('email', 'password');
        // if (Auth::attempt($credentials)) {
        //     $user = Auth::user();
        //     $tokens = $user->tokens->filter(function ($token) {
        //         return $token->expires_at === null || $token->expires_at->gt(Carbon::now());
        //     })->map(function ($token) {
        //         $tiempo_restante = $token->expires_at ? $token->expires_at->diffInSeconds(Carbon::now()) : null;
        //         return [
        //             'id' => $token->id,
        //             'name' => $token->name,
        //             'abilities' => $token->abilities,
        //             'last_used_at' => $token->last_used_at,
        //             "user_id" => $token->user_id,
        //             "client_id" => $token->client_id,
        //             "name" => $token->name,
        //             "scopes" => $token->scopes,
        //             "revoked" => $token->revoked,
        //             "created_at" => $token->created_at,
        //             "updated_at" => $token->updated_at,
        //             "expires_at" => $token->expires_at,
        //             'tiempo_restante' => $tiempo_restante,
        //             'tiempo_restante2' => $token->expires_at && $token->expires_at->lt(Carbon::now()),

        //         ];
        //     });
        
        //     return response()->json([
        //         'tokens' => $tokens
        //     ]);
        // } else {
        //     return response()->json([
        //         'message' => 'Invalid credentials'
        //     ], 401);
        // }
    }
    public function tokenIsValid(Request $request){
        try {
            $data = $request->user()->token();

            $check = Auth::guard('api')->check();
        return response()->json([
            'data' => $data,
            'isValid' => $check 
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