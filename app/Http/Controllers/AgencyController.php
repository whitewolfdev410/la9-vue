<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\Agency_email_send;
use DB;
use Exception;
use Mail;

class AgencyController extends Controller
{
    public function agency_rejection(Request $request) {
        $input = $request->all();
        $user_email = DB::table('users')->where('id', $input['user_id'])->value('email');
        try {
            DB::table('users')->where('id', $input['user_id'])->update(['permission' => 'deny']);

            $emailData['status'] = 'reject';
            $emailData['message'] = 'Your registration request rejected';

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

    public function agency_pending(Request $request) {
                
        $params = ['page' => 'page-basket-index'];

        foreach (app('config')->get('shop.page.basket-index') as $name) {
            $params['aiheader'][$name] = Shop::get($name)->header();
            $params['aibody'][$name] = Shop::get($name)->body();
        }

        return Response::view(Shop::template('basket.index'), $params)
            ->header('Cache-Control', 'no-store, , max-age=0');
        
    }

    public function accept($id) {
        if (Auth::check() === false
            || $request->user()->can('admin', [AdminController::class, ['admin', 'editor']]) === false
        ) {
            return redirect()->guest(airoute('login', ['locale' => app()->getLocale()]));
        }

        $context = app('aimeos.context')->get(false);
        $siteManager = \Aimeos\MShop::create($context, 'locale/site');
        $siteId = current(array_reverse(explode('.', trim($request->user()->siteid, '.'))));
        $siteCode = ($siteId ? $siteManager->get($siteId)->getCode() : config('shop.mshop.locale.site', 'default'));
        $locale = $request->user()->langid ?: config('app.locale', 'en');

        $param = array(
            'resource' => 'dashboard',
            'site' => Route::input('site', Request::get('site', $siteCode)),
            'locale' => Route::input('locale', Request::get('locale', $locale)),
        );

        return redirect()->route('aimeos_shop_jqadm_search', $param);
    }

    public function cancel($id) {
        $user_email = DB::table('users')->where('id', $id)->value('email');
        try {
            DB::table('users')->where('id', $id)->update(['permission' => 'deny']);

            $emailData['status'] = 'cancel';
            $emailData['message'] = 'Your registration request approved.';

            \Mail::to('support@lifeanalytics.org')
                    ->send(new Agency_email_send($emailData));
            
            return true;
        } catch (\Exception $e) {
            echo ($e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
        
    }
}
