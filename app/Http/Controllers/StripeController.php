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

use Laravel\Cashier\Cashier;

class StripeController extends Controller
{
    public function cardpayment(Request $request)
    {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // retrieve JSON from POST body
            $jsonStr = file_get_contents('php://input');
            $data = json_decode($jsonStr);
            //Create or retrieve stripe customer whether stripe_id exists in users table or not
            $user = DB::table('users')->where('email', $data->email)->first();

            if ($user->stripe_id == null) {
                $customer = \Stripe\Customer::create([
                    'email' => $data->email,
                    'name' => $data->name,
                    'description' => "test customer",
                ]);

                // insert stripe_id in user table
                DB::table('users')
                    ->where('email', $data->email)
                    ->update(array('stripe_id' => $customer->id));
            } else {
                $customer = \Stripe\Customer::retrieve($user->stripe_id, []);
            }
            if ($data->currency != "JPY") {
                $data->amount *= 100;
            }
            // Create a PaymentIntent with amount and currency
            $paymentIntent = \Stripe\PaymentIntent::create([
                'customer' => $customer->id,
                'setup_future_usage' => 'off_session',
                'amount' => $data->amount,
                'currency' => $data->currency,
                // 'automatic_payment_methods' => [
                //     'enabled' => true,
                // ],
            ]);

            $output = [
                'clientSecret' => $paymentIntent->client_secret,
            ];
            echo json_encode($output);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function bankpayment(Request $request)
    {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $jsonStr = file_get_contents('php://input');
            $data = json_decode($jsonStr);

            // check if stripe_id exist
            $user = DB::table('users')->where('email', $data->email)->first();

            if ($user->stripe_id == null) {
                $customer = \Stripe\Customer::create([
                    'email' => $data->email,
                    'name' => $data->name,
                    'description' => "test customer",
                ]);

                // insert stripe_id in user table
                DB::table('users')
                    ->where('email', $data->email)
                    ->update(array('stripe_id' => $customer->id));
            } else {
                $customer = \Stripe\Customer::retrieve($user->stripe_id, []);
            }

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

    public function getPaymentIntent(Request $request)
    {

        // $paymentIntent = Cashier::stripe()->paymentIntents->retrieve('pi_3LJCASFBpmk8ToyI0WPS0nTy', []);
        // return $paymentIntent;
        // $user = Cashier::stripe()->customers->retrieve("cus_M0ZF0obkL0GKXe",[]);

        // $user = Cashier::findBillable("cus_M1L0IuWQmYRV6Q");
        $user = new User();
        $stripeCustomer = $user->createAsStripeCustomer();
        // $stripeCustomer = $user->asStripeCustomer();
        // $user->applyBalance(-500, 'Premium customer top-up.');
        // $user->applyBalance(300, 'Premium customer top-up.');

        // $transactions = $user->balanceTransactions();
        // foreach ($transactions as $transaction) {
        //     // Transaction amount...
        //     $amount = $transaction->amount(); // $2.31

        //     // Retrieve the related invoice when available...
        //     $invoice = $transaction->invoice();
        // }
        // return $user->balance();

        return $stripeCustomer;

        // $stripeCustomer = $user->createAsStripeCustomer(array(
        //     'description' => 'cashier customer',
        //     'email' => 'casher@customer.gmail.com'
        // ));
        // return $stripeCustomer;

    }

    public function transaction_save(Request $request)
    {
        //Avoid duplicate inserting
        $paymentIntentID = $request->transID;

        $trans_check = DB::table('transactions')->where('id', $paymentIntentID)->first();
        if ($trans_check == null) {
            $paymentIntent = Cashier::stripe()->paymentIntents->retrieve($paymentIntentID, []);
            $status = $paymentIntent->status;
            $stripe_id = $paymentIntent->customer;
            $price = $paymentIntent->amount;
            $email = $paymentIntent->charges->data[0]->billing_details->email;
            if ($email == null) {
                $email = $paymentIntent->receipt_email;
            }

            $user_id = DB::table('users')->where('stripe_id', $stripe_id)->value('id');
            $application_id = DB::table('applications')->where('price', $price / 100)->value('id');

            $transaction = Transaction::create([
                'user_id' => $user_id,
                'app_id' => $application_id,
                'transaction_id' => $paymentIntentID,
                'transaction_status' => $status,
                'transaction_price' => $price,
                'payee_email' => 'Stripe_lifeanalytics',
                'payee_id' => 'Stripe_lifeanalytics_bank_id',
                'payer_email' => $email,
                'payer_id' => $stripe_id,
                'created_at' => date('Y-m-d h:i:s'),
                'updated_at' => date('Y-m-d h:i:s')
            ]);

            if ($transaction) {
                $user = DB::table('users')->where('id', $user_id)->select('email', 'name')->first();
                $application = DB::table('applications')->where('id', $application_id)->select('app_name', 'category_tab', 'period_date', 'capacity', 'capacity_unit')->first();

                $payment_email_data['app_name'] = $application->app_name;
                $payment_email_data['cat_tab'] = $application->category_tab;
                $payment_email_data['period_date'] = $application->period_date;
                $payment_email_data['capacity'] = $application->capacity;
                $payment_email_data['capacity_unit'] = $application->capacity_unit;
                $payment_email_data['price'] = $price;
                $payment_email_data['app_url'] = "";
                $payment_email_data['user_email'] = $user->email;
                $payment_email_data['order_number'] = $paymentIntentID;
                $payment_email_data['created_date'] = date('Y-m-d h:i:s');

                //Query to get meta infos of customer
                $row = DB::table('customermetas')->where("value", $email)->first();
                $name = DB::table('customermetas')->where('user_id', $row->user_id)->where('key', 'name')->value("value");
                $address1 = DB::table('customermetas')->where('user_id', $row->user_id)->where('key', 'address_1')->value("value");
                $address2 = DB::table('customermetas')->where('user_id', $row->user_id)->where('key', 'address_2')->value("value");

                $payment_email_data['customer_name'] = $name;
                $payment_email_data['address1'] = $address1;
                $payment_email_data['address2'] = $address2;

                //web app send data has to be noted in here 
                // $web_app_pass = $this->transfer_email_pass($payment_email_data);
                //$payment_email_data['user_pass'] = $transaction->transaction_price; 
                // dd(DB::table('customer_purchases')->where('user_id', $user_id)->where('app_name', $application->app_name)->first());
                //if web app request and response is success, save some data on customer_purchases table
                if (DB::table('customer_purchases')->where('user_id', $user_id)->where('app_name', $application->app_name)->first()) {
                    $period_date = DB::table('customer_purchases')->where('user_id', $user_id)->where('app_name', $application->app_name)->value('period_date');
                    $payment_email_data['period_date'] = $application->period_date + $period_date;
                    Customer_purchase::where('user_id', $user_id)->where('app_name', $application->app_name)->update([
                        'cat_tab' => $payment_email_data['cat_tab'],
                        'period_date' => $payment_email_data['period_date'],
                        'capacity' => $payment_email_data['capacity'],
                        'capacity_unit' => $payment_email_data['capacity_unit']

                    ]);
                } else {
                    Customer_purchase::create([
                        'user_id' => $user_id,
                        'app_url' => $payment_email_data['app_url'],
                        'app_name' => $payment_email_data['app_name'],
                        'cat_tab' => $payment_email_data['cat_tab'],
                        'period_date' => $payment_email_data['period_date'],
                        'capacity' => $payment_email_data['capacity'],
                        'capacity_unit' => $payment_email_data['capacity_unit']
                    ]);
                }

                //if customer_purchase table save is success, send email to customer
                //sending data is payment_data, web_app url, user_email and user_pass
                try {
                    $payment_email_check = \Mail::to($user->email, $user->name)
                        ->send(new Payment_confirm_send($payment_email_data));

                    return response()->json($data->transaction_id);
                } catch (\Exception $e) {
                    echo ($e->getMessage());
                    return response()->json([
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function metaquery(Request $request)
    {

        // $email = "goldendriver0731@gmail.com";
        // $row = DB::table('customermetas')->where("value", $email)->first();
        // // $name = DB::table('customermetas')->where('user_id', $row->user_id)->where('key', 'name')->value("value");
        // $addr2 = DB::table('customermetas')->where('user_id', $row->user_id)->where('key', 'address_2')->value("value");
        // return $row->user_id;

        $dealer_customer_data = DB::table('dealer_customers')->where('order_number', 1424)->first();

        return $dealer_customer_data->customer_prefecture;
    }

    public function testschedule()
    {
        echo 'se';
    }

    public function testSubscribe(Request $request)
    {
        // $user = User::firstOrNew([
        //     'stripe_id' => 'cus_M6rWyKEMkyh41n'
        // ]);
        $user = Cashier::findBillable('cus_M6rWyKEMkyh41n');
        // return $user;
        $paymentMethods = $user->paymentMethods();
        // $paymentMethod = $user->defaultPaymentMethod();
        $subscription = $user->newSubscription(
            'default',
            'price_1LR9dpBc2bHS5nE0hsVuQXKZ'
        )->create($paymentMethods[0]->id, [
            'email' => $user->email
        ]);

        // return $paymentMethods[0]->id;
        return $subscription;
        // return $user->email;
    }

    public function sendInvoice()
    {
        $user = Cashier::findBillable('cus_M6rWyKEMkyh41n');
        // $subscription = $user->newSubscription(
        //     'default', 'price_1LR9dpBc2bHS5nE0hsVuQXKZ'
        // )->createAndSendInvoice(

        // );

        $subscription = $user->newSubscription(
            'default',
            'price_1LR9dpBc2bHS5nE0hsVuQXKZ'
        )->createAndSendInvoice();

        // $user->newSubscription('default', 'monthly')
        // ->create($paymentMethod, [
        //     'email' => $user->email, // <= customer’s email
        // ]);
        return $subscription;
    }
}
