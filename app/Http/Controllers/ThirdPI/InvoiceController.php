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

class CustomerBank extends Controller
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

    public function get_ias_cat_id() {
        $data = DB::table('applications')->where('app_name', 'IAS')->select('cat_id')->get();
        return response()->json($data);
    }

    public function get_checkout_category($app_id, $cat_id) {
        switch ($app_id) {
            case '1':
                $app_name = 'IAS';
                break;
        }
        $category_data = DB::table('applications')->where('app_name', $app_name)->where('cat_id', $cat_id)->first();
        return response()->json($category_data);
    }

    public function get_informations($lang) {
        $data = DB::table('new_informations')->where('lang_page', $lang)->leftJoin('users', 'new_informations.user_id', '=', 'users.id')->select('new_informations.*', 'users.name', 'users.nikename', 'users.email', 'users.photo_url')->get();
        
        return response()->json($data);
    }

    public function get_info_detail_data($id) {
        $data = DB::table('new_informations')->where('id', $id)->first();
        return response()->json($data);
    }

    public function price_conversion(Request $request) {
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

    public function spreadForPeriod(Period $period)
    {
        $day = Carbon::make($period->getStart());

        $data = collect([]);

        while ($day <= $period->getEnd()) {
            $data[$day->toDateString()] = 0;

            $day->addDay();
        }

        foreach ($this as $model) {
            $data[$model->created_at->toDateString()] += 1;
        }

        return $data;
    }
}
