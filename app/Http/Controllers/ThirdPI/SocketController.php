<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\Agency_email_send;
use DB;
use Exception;
use Mail;

class SocketController extends Controller
{
    public function agency_rejection(Request $request) {
        $input = $request->all();
        Validator::make($input, [
            'message'=> 'required',
            'name'=> 'required|string',
            'email'=> 'required|email',
            'phone'=> 'required',
        ])->validate();

        $file = $request->file('file');
        if($file) {
            $file_name = time()."_".$file->getClientOriginalName();
            $input['file'] = $file_name;
        } else {

        }

        $contact = Contact::create($input);

        if($contact && $file) {
            $destinationPath = public_path('upload/contact');
            $file->move($destinationPath, $file_name);
        }

        //Send mail to admin
        try{
            // $mail_check = \Mail::to('support@lifeanalytics.org', 'Daisukekubota')
            $mail_check = \Mail::to('goldendriver0731@gmail.com', 'golden')
            ->send(new Contact_send($contact));
            return response()->json([
                'success' => true,
                'mail' => $mail_check,
            ]);
        }
        catch(\Exception $e){
            echo ($e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
        
    }

    public function host_send(Request $request) {
        $input = $request->all();
        Validator::make($input, [
            'message'=> 'required',
            'name'=> 'required|string',
            'email'=> 'required|email',
            'phone'=> 'required',
        ])->validate();

        $file = $request->file('file');
        if($file) {
            $file_name = time()."_".$file->getClientOriginalName();
            $input['file'] = $file_name;
        } else {

        }

        $contact = Contact::create($input);

        if($contact && $file) {
            $destinationPath = public_path('upload/contact');
            $file->move($destinationPath, $file_name);
        }

        //Send mail to admin
        try{
            // $mail_check = \Mail::to('support@lifeanalytics.org', 'Daisukekubota')
            $mail_check = \Mail::to('goldendriver0731@gmail.com', 'golden')
            ->send(new Contact_send($contact));
            return response()->json([
                'success' => true,
                'mail' => $mail_check,
            ]);
        }
        catch(\Exception $e){
            echo ($e->getMessage());
            return response()->json([
                'success' => false,
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

    public function agency_pending(Request $request) {
        $input = $request->all();
        $user_email = DB::table('users')->where('id', $input['user_id'])->value('email');
        try {
            DB::table('users')->where('id', $input['user_id'])->update(['transaction_con' => $input['transaction_con']], ['deposit_amount' => $input['deposit_amount']]);

            $emailData['status'] = 'pending';
            $emailData['message'] = 'Your transaction condition and deposit amount are modified.';
            $emailData['transaction_con'] = $input['transaction_con'];
            $emailData['deposit_amount'] = $input['deposit_amount'];
            $emailData['user_id'] = $input['user_id'];

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
