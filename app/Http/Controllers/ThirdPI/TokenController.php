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

class TokenController extends Controller
{
    public function token_create(Request $request) {
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

    public function token_valid_check(Request $request) {
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

    public function detailAction()
	{
		try
		{
			$params = ['page' => 'page-catalog-detail'];

			foreach( app( 'config' )->get( 'shop.page.catalog-detail' ) as $name )
			{
				$params['aiheader'][$name] = Shop::get( $name )->header();
				$params['aibody'][$name] = Shop::get( $name )->body();
			}

			return Response::view( Shop::template( 'catalog.detail' ), $params )
				->header( 'Cache-Control', 'private, max-age=' . config( 'shop.cache_maxage', 30 ) );
		}
		catch( \Exception $e )
		{
			if( $e->getCode() >= 400 && $e->getCode() < 600 ) { abort( $e->getCode() ); }
			throw $e;
		}
	}

    public function get_dealer_checkout_category($app_id, $cat_id) {
        switch ($app_id) {
            case '1':
                $app_name = 'IAS';
                break;
        }
        $category_data = DB::table('applications')->where('app_name', $app_name)->where('cat_id', $cat_id)->first();
        return response()->json($category_data);
    }
    
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

    /**
     * Create a token with now timestamp.
     *
     * @param  string|null  $collectionId
     * @return SyncToken
     */
    private function createSyncTokenNow($collectionId)
    {
        return SyncToken::create([
            'account_id' => $this->user->account_id,
            'user_id' => $this->user->id,
            'name' => $collectionId ?? $this->backendUri(),
            'timestamp' => now(),
        ]);
    }

    public function countAction()
	{
		$params = ['page' => 'page-catalog-count'];

		foreach( app( 'config' )->get( 'shop.page.catalog-count' ) as $name )
		{
			$params['aiheader'][$name] = Shop::get( $name )->header();
			$params['aibody'][$name] = Shop::get( $name )->body();
		}

		return Response::view( Shop::template( 'catalog.count' ), $params )
			->header( 'Content-Type', 'application/javascript' )
			->header( 'Cache-Control', 'public, max-age=300' );
	}


    
}
