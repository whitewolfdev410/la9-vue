<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\Agency_email_send;
use DB;
use Exception;
use Mail;

class AgencyController extends Controller
{
    public function agency_rejection(Request $request) {
        $input = $request->all();
        $user_email = DB::table('users')->where('id', $input['user_id'])->value('email');
        try {
            DB::table('users')->where('id', $input['user_id'])->update(['permission' => 'deny']);

            $emailData['status'] = 'reject';
            $emailData['message'] = 'Your registration request rejected';

        //    $input = $request->all();
        //     Validator::make($input, [
        //         'message'=> 'required',
        //         'name'=> 'required|string',
        //         'email'=> 'required|email',
        //         'phone'=> 'required',
        //     ])->validate();

        //     $file = $request->file('file');
        //     if($file) {
        //         $file_name = time()."_".$file->getClientOriginalName();
        //         $input['file'] = $file_name;
        //     } else {

        //     }

        try {
            DB::table('users')->where('id', $input['user_id'])->update(['permission' => 'approved']);

            $emailData['status'] = 'approve';
            $emailData['message'] = 'Your registration request approved.';

            \Mail::to($user_email)
                    ->send(new Agency_email_send($emailData));

            return response()->json([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            echo ($e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }

            $contact = Contact::create($input);

            if($contact && $file) {
                $destinationPath = public_path('upload/contact');
                $file->move($destinationPath, $file_name);
            }

            return response()->json([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            echo ($e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
        
    }

    public function agency_approve(Request $request) {
        $input = $request->all();
        $user_email = DB::table('users')->where('id', $input['user_id'])->value('email');
        try {
            DB::table('users')->where('id', $input['user_id'])->update(['permission' => 'approved']);

            $emailData['status'] = 'approve';
            $emailData['message'] = 'Your registration request approved.';

            \Mail::to($user_email)
                    ->send(new Agency_email_send($emailData));

            return response()->json([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            echo ($e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
        
    }

    public function cancel($id) {
        $user_email = DB::table('users')->where('id', $id)->value('email');
        try {
            DB::table('users')->where('id', $input['user_id'])->update(['permission' => 'deny']);

            $emailData['status'] = 'reject';
            $emailData['message'] = 'Your registration request rejected';

            \Mail::to($user_email)
                    ->send(new Agency_email_send($emailData));

            return response()->json([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            echo ($e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
        
    }

    public function dealer_customer(Request $request) {
        $input = $request->all();
        $user_email = DB::table('users')->where('id', $input['user_id'])->value('email');
        try {
            DB::table('users')->where('id', $input['user_id'])->update(['permission' => 'approved']);

            $emailData['status'] = 'approve';
            $emailData['message'] = 'Your registration request approved.';

            \Mail::to($user_email)
                    ->send(new Agency_email_send($emailData));

            return response()->json([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            echo ($e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
        
    }

    public function balance_load($id) {
        $user_email = DB::table('users')->where('id', $id)->value('email');
        try {
            DB::table('users')->where('id', $input['user_id'])->update(['permission' => 'deny']);

            $emailData['status'] = 'reject';
            $emailData['message'] = 'Your registration request rejected';

            \Mail::to($user_email)
                    ->send(new Agency_email_send($emailData));

            return response()->json([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            echo ($e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
        
    }
}
