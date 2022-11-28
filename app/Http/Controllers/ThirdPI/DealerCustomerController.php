<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClinet;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Order;
use Mail;
use App\Mail\Payment_confirm_send;
use App\Models\Transaction;
use App\Models\Customer_purchase;
use DB;
use GuzzleHttp\Client;

class DealerCustomerController extends Controller
{
    public function bankpayment(Request $request) {
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

    public function getCurrentSyncToken($collectionId): ?SyncToken
    {
        $tokens = SyncToken::where([
            'account_id' => $this->user->account_id,
            'user_id' => $this->user->id,
            'name' => $collectionId ?? $this->backendUri(),
        ])
            ->orderBy('created_at')
            ->get();

        return $tokens->count() > 0 ? $tokens->last() : null;
    }

    /**
     * Create or refresh the token if a change happened.
     *
     * @param  string|null  $collectionId
     * @return SyncToken
     */
    public function refreshSyncToken($collectionId): SyncToken
    {
        $token = $this->getCurrentSyncToken($collectionId);

        if (! $token || $token->timestamp < $this->getLastModified($collectionId)) {
            $token = $this->createSyncTokenNow($collectionId);
        }

        return $token;
    }

    /**
     * Get SyncToken by token id.
     *
     * @param  string|null  $collectionId
     * @param  string  $syncToken
     * @return SyncToken|null
     */
    protected function getSyncToken($collectionId, $syncToken)
    {
        /** @var SyncToken|null */
        return SyncToken::where([
            'account_id' => $this->user->account_id,
            'user_id' => $this->user->id,
            'name' => $collectionId ?? $this->backendUri(),
        ])
            ->find($syncToken);
    }
    
}
