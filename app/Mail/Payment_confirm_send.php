<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Payment_confirm_send extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($payment_email_data)
    {
        $this->data = $payment_email_data;
    }
    
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {   
        return $this->from("life@example.com", "life_support")                    
                    ->view('email.stripe_bank_email_view')
                    ->with('customer_name', $this->data['customer_name'])
                    ->with('address1', $this->data['address1'])
                    ->with('address2', $this->data['address2'])
                    ->with('app_name', $this->data['app_name'])
                    ->with('order_number', $this->data['order_number'])
                    ->with('period_date', $this->data['period_date'])
                    ->with('cat_tab', $this->data['cat_tab'])
                    ->with('price', $this->data['price'])
                    ->with('capacity', $this->data['capacity'])
                    ->with('capacity_unit', $this->data['capacity_unit'])
                    ->with('created_date', $this->data['created_date']);
    }
}
