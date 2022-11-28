<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDealerCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //         array:15 [
        //   "order_number" => null
        //   "dealer_name" => null
        //   "dealer_surname" => null
        //   "dealer_middlename" => null
        //   "customer_name" => null
        //   "customer_surname" => null
        //   "customer_middle_name" => null
        //   "customer_facility" => null
        //   "customer_department" => null
        //   "customer_city" => null
        //   "customer_prefecture" => null
        //   "customer_country" => null
        //   "user_id" => 2
        //   "app_id" => "1"
        //   "cat_id" => "4"
        // ].
        Schema::create('dealer_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('order_number');
            $table->string('dealer_name');
            $table->string('dealer_surname');
            $table->string('dealer_middlename');
            $table->string('customer_email');
            $table->string('customer_name');
            $table->string('customer_surname');
            $table->string('customer_middle_name');
            $table->string('customer_facility');
            $table->string('customer_department');
            $table->string('customer_city');
            $table->string('customer_prefecture');
            $table->string('customer_country');
            $table->integer('cat_id');
            $table->integer('app_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dealer_customers');
    }
}
