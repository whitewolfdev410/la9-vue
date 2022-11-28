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

    public function contact_store_send(Request $request) {
        $item = Cart::get($id);

        Cart::remove($id);

        $duplicates = Cart::instance('saveForLater')->search(function ($cartItem, $rowId) use ($id) {
            return $rowId === $id;
        });

        if ($duplicates->isNotEmpty()) {
            return redirect()->route('cart.index')->with('success_message', 'Item is already Saved For Later!');
        }

        Cart::instance('saveForLater')->add($item->id, $item->name, 1, $item->price)
            ->associate('App\Product');

        return redirect()->route('cart.index')->with('success_message', 'Item has been Saved For Later!');


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

    public function show(Request $request, int $id)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        UpdateLastConsultedDate::dispatch($contact);

        return new ContactResource($contact);
    }

    /**
     * Store the contact.
     *
     * @param  Request  $request
     * @return ContactResource|JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $contact = app(CreateContact::class)->execute(
                $request->except(['account_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                        'author_id' => auth()->user()->id,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ContactResource($contact);
    }

    /**
     * Update the contact.
     *
     * @param  Request  $request
     * @return ContactResource|JsonResponse
     */
    public function update(Request $request, $contactId)
    {
        try {
            $contact = app(UpdateContact::class)->execute(
                $request->except(['account_id', 'contact_id'])
                    +
                    [
                        'contact_id' => $contactId,
                        'account_id' => auth()->user()->account_id,
                        'author_id' => auth()->user()->id,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ContactResource($contact);
    }

    /**
     * Delete a contact.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Set exchange value.
     *
     * @return void
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = MoneyHelper::parseInput($value, $this->currency);
    }

    /**
     * Get exchange value.
     *
     * @return string|null
     */
    public function getAmountAttribute(): ?string
    {
        if (! ($amount = Arr::get($this->attributes, 'amount', null))) {
            return null;
        }

        return MoneyHelper::exchangeValue($amount, $this->currency);
    }

    /**
     * Get value of amount (without currency).
     *
     * @return string
     */
    public function getValueAttribute(): string
    {
        if (! ($amount = Arr::get($this->attributes, 'amount', null))) {
            return '';
        }

        return MoneyHelper::getValue($amount, $this->currency);
    }

    /**
     * Get display value: amount with currency.
     *
     * @return string
     */
    public function getDisplayValueAttribute(): string
    {
        if (! ($amount = Arr::get($this->attributes, 'amount', null))) {
            return '';
        }

        return MoneyHelper::format($amount, $this->currency);
    }

    
}
