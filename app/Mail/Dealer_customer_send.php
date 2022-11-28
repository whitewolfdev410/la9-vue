<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\DealerCustomer;

class Dealer_customer_send extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The Contact instanced
     * 
     * @var App\Models\DealerCustomer
     */

    protected $dealerCustomer;

    /**
     * Create a new message instance.
     *
     * @param App\Models\DealerCustomer $contact
     * @return void
     */
    public function __construct($email_data)
    {
        $this->data = $email_data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // dd($this->dealerCustomer);
        // return $this->from($this->dealerCustomer->email, $this->dealerCustomer->name)
        return $this->from("lifeanalytics@gmail.com", "Life Analytics")
                    ->view('email.dealer_customer_email_view')
                    ->with([
                        'app_name' => $this->data['app_name'],
                        'cat_tab' => $this->data['category_tab'],
                        'period_date' => $this->data['period_date'],
                        'capacity' => $this->data['capacity'],
                        'capacity_unit' => $this->data['capacity_unit'],
                        'price' => $this->data['price'],
                        'discount_price' => $this->data['discount_price'],
                        'order_number' => $this->data['order_number'],
                        'dealer_name' => $this->data['dealer_name'],
                        'customer_name' => $this->data['customer_name'],
                        'customer_facility' => $this->data['customer_facility'],
                        'customer_department' => $this->data['customer_department'],
                        'customer_city' => $this->data['customer_city'],
                        'customer_prefecture' => $this->data['customer_prefecture'],
                        'customer_country' => $this->data['customer_country'],
                        'created_at' => $this->data['created_at'],
                    ]);
    }
}
