<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NewinformationsController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\DealerCustomerController;
use App\Http\Controllers\AozoraController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/login', [FrontendController::class], 'index');

Route::group(['middleware' => 'auth:api'], function () {

    Route::post('logout', [LoginController::class, 'logout']);

    Route::get('user', [UserController::class, 'current']);

    Route::patch('settings/profile', [ProfileController::class, 'update']);
    Route::patch('settings/password', [PasswordController::class, 'update']);

    //fetch users
    Route::get('get/users', [AdminController::class, 'fetch_users']);
    Route::get('get/user/{id}', [AdminController::class, 'fetch_user_id']);

    //Application
    Route::get('get/applications', [AdminController::class, 'fetch_applications']);
    Route::get('get/application/{id}', [AdminController::class, 'fetch_application_by_id']);
    Route::post('application/create', [AdminController::class, 'application_create']);
    Route::post('application/update', [AdminController::class, 'application_update']);

    Route::post('user/role_update', [AdminController::class, 'user_role_update']);

    //Get others Datas
    Route::get('get/ias/cat_id', [FrontendController::class, 'get_ias_cat_id']);
    Route::get('get/checkout/category/{app_id}/{cat_id}', [FrontendController::class, 'get_checkout_category']);
    Route::get('get/dealer_checkout/category/{app_id}/{cat_id}', [FrontendController::class, 'get_dealer_checkout_category']);

    //get purchase appID and catID by user_id
    Route::get('get/purchase/appID_catID/{id}', [FrontendController::class, 'get_purchaseID_by_userID']);

    //payment and checkout apis
    Route::post('payment/creditcard/checkout/send', [PaymentController::class, 'creditcard_checkout']);
    Route::post('payment/furikomi/checkout/send', [PaymentController::class, 'furikomi_checkout']);
    Route::post('payment/dealer/checkout/application/send', [DealerCustomerController::class, 'dealer_application_checkout']);

    //routes after checkout    
    Route::post('payment/checkout/transaction/save', [PaymentController::class,  'transaction_save']);

    //payment confirm data get route
    Route::get('get/checkout/confirmData/{id}', [PaymentController::class,  'get_payment_confirmData']);

    //Request to application again for password
    Route::post('post/requestToApp', [PaymentController::class, 'request_to_app']);

    //route transaction history get by user id    
    Route::get('get/purchase/customer/transaction/{id}', [FrontendController::class,  'get_customer_transaction_by_userid']);

    //route purchase list get by user id    
    Route::get('get/purchase/customer/purchase_list/{id}', [FrontendController::class,  'get_customer_purchaselist_by_userid']);

    //route purchase get registered customers
    Route::get('get/purchase/admin/registered_customers', [FrontendController::class,  'get_registered_customers']);


    

    //new information post route    
    Route::post('new_information/create', [NewinformationsController::class, 'new_information_create']);
    Route::post('post/information/update', [NewinformationsController::class, 'information_update']);

    //comment routes
    Route::post('comment/post', [CommentController::class, 'comment_post']);
    Route::post('comment/edit', [CommentController::class, 'comment_edit_post']);
    Route::post('reply/post', [CommentController::class, 'reply_post']);
    Route::get('comment/delete/{info_id}/{comment_id}', [CommentController::class, 'delete_comment_byid']);

    //registered agency manage routes
    Route::post('user/agency/rejection', [AgencyController::class, 'agency_rejection']);
    Route::post('user/agency/approve', [AgencyController::class, 'agency_approve']);
    Route::post('user/agency/pending', [AgencyController::class, 'agency_pending']);

    //process dealer's customer info route
    Route::post('agency/dealer_customer_register', [DealerCustomerController::class, 'dealerCustomerRegister']);
    
    //process stripe transaction save to database
    Route::post('payment/stripe/checkout/transaction/save', [StripeController::class, 'transaction_save']);

});

Route::group(['middleware' => 'guest:api'], function () {
    
    Route::post('login', [LoginController::class, 'login']);
    Route::post('customerregister', [RegistrationController::class, 'customerRegister']);
    Route::post('agencyregister', [RegistrationController::class, 'agencyRegister']);

    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
    Route::post('password/reset', [ResetPasswordController::class, 'reset']);

    Route::post('email/verify/{user}', [VerificationController::class, 'verify'])->name('verification.verify');
    Route::post('email/resend', [VerificationController::class, 'resend']);

    Route::post('oauth/{driver}', [OAuthController::class, 'redirect']);
    Route::get('oauth/{driver}/callback', [OAuthController::class, 'handleCallback'])->name('oauth.callback');
});

//dealer page new informations get
Route::get('get/new_informations/dealer/{lang}/{number}', [NewinformationsController::class, 'get_dealer_informations']);


//Routes New Informations
Route::get('get/new_informations/{lang}/{role}/{number}', [NewinformationsController::class, 'get_informations']);
Route::get('get/information/detail/{id}', [NewinformationsController::class, 'get_info_detail_data']);

//Contact Route
Route::post('contact/send', [ContactController::class, 'contact_store_send']);

//comment get routes
Route::get('get/comments/{id}', [CommentController::class, 'get_comments_by_infoid']);
Route::get('get/answers/{comment_id}', [CommentController::class, 'get_answers_get_commentid']);

//stripe create payment
Route::post('cardpay', [StripeController::class, 'cardpayment']);
Route::post('bankpay', [StripeController::class, 'bankpayment']);

//dealer customer save info route
Route::post('agency/dealer_customer_save', [DealerCustomerController::class, 'dealer_customer_save']);
// dealer customersend email route
Route::post('agency/dealer_customer_send_mail', [DealerCustomerController::class, 'dealer_customer_send']);

//test route to view email template
Route::get('/test', function () {
    // return view('email.dealer_customer_email_view', ['contact_name' => '123',
    //                                                  'contact_email' => '123@gmail.com',
    //                                                  'contact_phone' => '12312313',
    //                                                  'contact_message' => 'message'
    //                                                 ]
    // );
    return view('email.transaction_email_view', ['app_name' => 'IAS',
                                                 'cat_tab' => 'xxxx',
                                                 'period_data' => '30days',
                                                 'capacity' => '400',
                                                 'capacity_unit' => 'GB',
                                                 'price' => 36000
                                                ]
    );
}); 
//test route to study laravel/cashier
Route::get('/test', [StripeController::class, 'getPaymentIntent']);
//DB query unit test
Route::get('/test1', [StripeController::class, 'metaquery']);
// test subscribe
Route::get('/user/subscribe', [StripeController::class, 'testSubscribe']);
Route::get('/gmo', [AozoraController::class, 'test']);

