<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\SignUpRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class UserController extends Controller
{
    public function signup(SignUpRequest $data){
        $user = User::create([
            'first_name' => $data->first_name,
            'surname' => $data->surname,
            'password' => Hash::make($data->password),
            'phone' => $data->phone,
        ]);
        return ["id" => $user->id];
    }

    public function login(LoginRequest $data){
        $user = User::where("phone", $data->phone)->first();
        throw_if(
            !$user || !Hash::check($data->password, $user->password),
            new ApiException(422, "Wrong phone number or password")
        );
        $token = $user->createToken("token")->plainTextToken;
        return ["token" => $token];
    }

    public function logout(){
        $user = auth()->user();
        throw_if(!$user, new ApiException(403, "You need authorization"));
        $user->tokens()->delete();
        return response(null, 200);
    }


    public function search(Request $request)
    {
        $search = $request->query('search');

        $users = User::query()->when($search, function ($query, $search) {
            $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

            $query->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->where(function ($q2) use ($word) {
                        $q2->where('first_name', 'like', "%{$word}%")
                            ->orWhere('surname', 'like', "%{$word}%")
                            ->orWhere('phone', 'like', "%{$word}%");
                    });
                }
            });
        })->get(['id', 'first_name', 'surname', 'phone']);
        return response()->json($users, 200);
    }
}
