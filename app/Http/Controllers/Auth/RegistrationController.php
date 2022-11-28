<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Mail\Register_confirm_mail_send;
use DB;
use Exception;
use Mail;

class RegistrationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Customer Registration Function
     */
    public function customerRegister(Request $request) {
        $data = $request->all();
        Validator::make($data, [
            'email' => 'required|email:filter|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ])->validate();

        $data['role'] = 'customer';
        $data['permission'] = 'approved';

        $check = User::create([
            'name'=>$data['name'] ?? '',
            'nikename'=>$data['nike_name'] ?? '',
            'email'=>$data['email'],
            'password'=>bcrypt($data['password']),
            'phone'=>$data['phone'] ?? null,
            'role'=>$data['role'],
            'permission'=>$data['permission']
        ]);

        if ($check) {
            $lastInsertedID = $check->id;
            $keys = array_keys($data);
            foreach($keys as $key){
                if($key != '_token' && $key != "password" && $key != "password_confirmation" && $key != "role" && $key != "permission" && $data[$key] != "" ){
                    DB::table('customermetas')->insert([
                        'user_id'=>$lastInsertedID,
                        'key'=>$key,
                        'value'=>$data[$key],
                    ]);
                }
            }
            //Send mail to admin
            try{
                \Mail::to('support@lifeanalytics.org', 'Daisukekubota')
                    ->send(new Register_confirm_mail_send($data));

                return $check;
            }
            catch(\Exception $e){
                echo ($e->getMessage());
                return response()->json([
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
    /**
     * Agency Registration Function
     */
    public function agencyRegister(Request $request) {
        $data = $request->all();
        Validator::make($data, [
            'email' => 'required|email:filter|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ])->validate();

        $data['role'] = 'agency';
        $data['permission'] = 'suspend';

        $check = DB::table('users')->insert([
            'name'=>$data['name'] ?? '',
            'nikename'=>$data['nike_name'],
            'email'=>$data['email'],
            'password'=>bcrypt($data['password']),
            'phone'=>$data['phone'] ?? null,
            'role'=>$data['role'],
            'permission'=>$data['permission']
        ]);
        

        if ($check) {
            $lastInsertedID = DB::getPdo()->lastInsertId();
            $keys = array_keys($data);
            foreach($keys as $key){
                if($key != '_token' && $key != "password" && $key != "password_confirmation" && $key != "role" && $key != "permission" && $data[$key] != "" ){
                    DB::table('agencymetas')->insert([
                        'user_id'=>$lastInsertedID,
                        'key'=>$key,
                        'value'=>$data[$key],
                    ]);
                }
            }       
                 
            $data['id'] = $lastInsertedID;

            //Send mail to admin
            try{
                \Mail::to('support@lifeanalytics.org', 'Daisukekubota')
                    ->send(new Register_confirm_mail_send($data));

                return response()->json([
                    'status' => 'success',
                    'message' => 'Your registration request sent to administrator. Please wait approval of administrator.'
                ]);
            }
            catch(\Exception $e){
                echo ($e->getMessage());
                return response()->json([
                    'error' => $e->getMessage(),
                ]);
            }
            return true;
        }
    }
}
