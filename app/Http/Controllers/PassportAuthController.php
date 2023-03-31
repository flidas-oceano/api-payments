<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PassportAuthController extends Controller
{
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|min:4',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);
 
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
       
        $token = $user->createToken('LaravelAuthApp')->accessToken;
 
        return response()->json(['token' => $token], 200);
    }
    public function login(Request $request)
    {
        $data = [
            'email' => $request->email,
            'password' => $request->password
        ];
 
        if (auth()->attempt($data)) {
            $token = auth()->user()->createToken('LaravelAuthApp')->accessToken;
            return response()->json(['token' => $token], 200);
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }   
    public function logout(Request $request)
    {
        $user = Auth::guard('api')->user();
        $user->token()->revoke();

        return response()->json(['message' => 'Successfully logged out']);
    }
    
    public function users(Request $request)
    {
        $users = User::all();
        return response()->json([
            'users' => $users
        ]); 
    }  

    public function register2(Request $request)
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
        
        $token = $user->createToken('LaravelAuthApp')->accessToken;
        
        return response()->json([
            'token'=> $token,
            'token_type'=>'Bearer',
        ], 200);
    }
    public function login2(Request $request)
    {
       
        try {
            $result = $request->validate([
                'email' => 'required',
                "password" => "required",
                'remember' => 'boolean',
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
            // $token->expires_at= Carbon::now()->addWeeks(1);
            $token->expires_at= Carbon::now()->addMinutes(10);

        $token->save();

        return response()->json([
            'access_token'=> $tokenRes->accessToken,
            'token_type'=>'Bearer',
            'expires_at'=> Carbon::parse(
                $tokenRes->token->expires_at
            )->toDateTimeString()
        ]);
    }   
    public function logout2(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'messagge' => 'Successfully logged out'
        ]);
    }
    
    public function user2(Request $request)
    {
        $users=User::all();
        return response()->json($users);
    }  

}