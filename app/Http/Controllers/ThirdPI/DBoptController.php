<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Mail\Contact_send;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Exception;
use Mail;

class DBoptController extends Controller
{
    /**
     * Contact email
     * 
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Response
     */    
    public function contact_store_send(Request $request) {
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
                $input = $request->all();
                Validator::make($input, [
                    'message'=> 'required',
                    'name'=> 'required|string',
                    'email'=> 'required|email',
                    'phone'=> 'required',
                ])->validate();
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
        }
        catch(\Exception $e){
            echo ($e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function match_mode(Request $request) {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $jsonStr = file_get_contents('php://input');
            $data = json_decode($jsonStr);

            // $customer = \Stripe\Customer::create([
            //     'email' => $data->email,
            //     'name' => $data->name,
            //     'description' => "test customer",
            // ]);

            $customer = \Stripe\Customer::retrieve("cus_M0ZF0obkL0GKXe", []);

            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $data->amount,
                'currency' => 'jpy',
                'customer' => $customer->id,
                'payment_method_types' => ['customer_balance'],
                'payment_method_data' => [
                    'type' => 'customer_balance',
                ],
                'payment_method_options' => [
                    'customer_balance' => [
                        'funding_type' => 'bank_transfer',
                        'bank_transfer' => [
                            'type' => 'jp_bank_transfer',
                        ],
                    ],
                ],
            ]);

            $output = [
                    'clientSecret' => $paymentIntent->client_secret,
            ];
            return response()->json($output);
        } catch (\Exception $e) {
            http_response_code(500);
            echo ($e->getMessage());
                return response()->json([
                    'error' => $e->getMessage(),
                ]);
        }
    }

    public function db_send(Request $request) {
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

    public Account $account;

    public bool $newTransactionModalOpen = false;

    public $state = [
        'asset_id' => null,
        'asset_price' => 0,
        'amount_in_tokens' => 0,
        'amount_in_dollars' => 0
    ];

    public function getChanges($calendarId, $syncToken): ?array
    {
        $token = null;
        $timestamp = null;
        if (! empty($syncToken)) {
            $token = $this->getSyncToken($calendarId, $syncToken);

            if (is_null($token)) {
                // syncToken is not recognized
                return null;
            }

            $timestamp = $token->timestamp;
        }

        $objs = $this->getObjects($calendarId);

        $modified = $objs->filter(function ($obj) use ($timestamp) {
            return ! is_null($timestamp) &&
                   $obj->updated_at > $timestamp &&
                   $obj->created_at < $timestamp;
        });
        $added = $objs->filter(function ($obj) use ($timestamp) {
            return is_null($timestamp) ||
                   $obj->created_at >= $timestamp;
        });
        $deleted = $this->getDeletedObjects($calendarId)
            ->filter(function ($obj) use ($timestamp) {
                $d = $obj->deleted_at;

                return is_null($timestamp) ||
                       $obj->deleted_at >= $timestamp;
            });

        return [
            'syncToken' => $this->refreshSyncToken($calendarId)->id,
            'added' => $added->map(function ($obj) {
                return $this->encodeUri($obj);
            })->values()->toArray(),
            'modified' => $modified->map(function ($obj) {
                $this->refreshObject($obj);

                return $this->encodeUri($obj);
            })->values()->toArray(),
            'deleted' => $deleted->map(function ($obj) {
                return $this->encodeUri($obj);
            })->values()->toArray(),
        ];
    }

    private function checkConditions(Request $request, Carbon $lastModified): bool
    {
        if (! $request->header('If-None-Match') && ($ifModifiedSince = $request->header('If-Modified-Since'))) {
            // The If-Modified-Since header contains a date. We will only
            // return the entity if it has been changed since that date.
            $date = Carbon::parse($ifModifiedSince);

            if ($lastModified->lessThanOrEqualTo($date)) {
                return false;
            }
        }

        if ($ifUnmodifiedSince = $request->header('If-Unmodified-Since')) {
            // The If-Unmodified-Since will allow the request if the
            // entity has not changed since the specified date.
            $date = Carbon::parse($ifUnmodifiedSince);

            // We must only check the date if it's valid
            if ($lastModified->greaterThan($date)) {
                abort(412, 'An If-Unmodified-Since header was specified, but the entity has been changed since the specified date.');
            }
        }

        return true;
    }
}
