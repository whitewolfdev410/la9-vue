<?php

class TransactionMigrateController extends Controller
{
    public function ajax_get(Request $request, $id)
    {
        try {
            app(DestroyTag::class)->execute([
                'tag_id' => $id,
                'account_id' => auth()->user()->account_id,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        try {
            $notes = auth()->user()->account->notes()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        try {
            $note = Note::where('account_id', auth()->user()->account_id)
                ->where('id', $noteId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $note->update($request->only(['body', 'contact_id', 'is_favorited']));
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        if ($request->input('is_favorited')) {
            $note->favorited_at = now();
        } else {
            $note->favorited_at = null;
        }
        $note->save();

        return new NoteResource($note);
    }

    public function suggestAction()
    {
        $params = ['page' => 'page-catalog-suggest'];

        foreach (app('config')->get('shop.page.catalog-suggest') as $name) {
            $params['aiheader'][$name] = Shop::get($name)->header();
            $params['aibody'][$name] = Shop::get($name)->body();
        }

        return Response::view(Shop::template('catalog.suggest'), $params)
            ->header('Cache-Control', 'private, max-age=' . config('shop.cache_maxage', 30))
            ->header('Content-Type', 'application/json');
    }

    /**
     * Show all the contacts for a given tag.
     *
     * @param  Request  $request
     * @param  int  $tagId
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function transaction_save(Request $request)
    {
        $input = $request->all();
        switch ($input['app_id']) {
            case '1':
                $app_name = 'IAS';
                break;
        }
        $application_id = DB::table('applications')->where('app_name', $app_name)->where('cat_id', $input['cat_id'])->value('id');
        $transaction = Transaction::create([
            'user_id' => $input['user_id'],
            'app_id' => $application_id,
            'transaction_id' => $input['transaction']['id'],
            'transaction_status' => $input['transaction']['status'],
            'transaction_price' => $input['transaction']['amount']['value'],
            'payee_email' => $input['payee']['email_address'],
            'payee_id' => $input['payee']['merchant_id'],
            'payer_email' => $input['payer']['email_address'],
            'payer_id' => $input['payer']['payer_id'],
            'created_at' => date('Y-m-d h:i:s', strtotime($input['transaction']['create_time'])),
            'updated_at' => date('Y-m-d h:i:s', strtotime($input['transaction']['update_time'])),
        ]);

        if ($transaction) {
            $user = DB::table('users')->where('id', $input['user_id'])->select('email', 'name')->first();
            $application = DB::table('applications')->where('id', $transaction->app_id)->select('app_name', 'category_tab', 'period_date', 'capacity', 'capacity_unit')->first();

            $payment_email_data['app_name'] = $application->app_name;
            $payment_email_data['cat_tab'] = $application->category_tab;
            $payment_email_data['period_date'] = $application->period_date;
            $payment_email_data['capacity'] = $application->capacity;
            $payment_email_data['capacity_unit'] = $application->capacity_unit;
            $payment_email_data['price'] = $transaction->transaction_price;
            $payment_email_data['app_url'] = "";
            $payment_email_data['user_email'] = $user->email;

            //web app send data has to be noted in here 
            // $web_app_pass = $this->transfer_email_pass($payment_email_data);
            //$payment_email_data['user_pass'] = $transaction->transaction_price; 

            //if web app request and response is success, save some data on customer_purchases table
            if (DB::table('customer_purchases')->where('user_id', $input['user_id'])->where('app_name', $application->app_name)->first()) {
                $period_date = DB::table('customer_purchases')->where('user_id', $input['user_id'])->where('app_name', $application->app_name)->value('period_date');
                $payment_email_data['period_date'] = $application->period_date + $period_date;
                Customer_purchase::where('user_id', $input['user_id'])->where('app_name', $application->app_name)->update([
                    'cat_tab' => $payment_email_data['cat_tab'],
                    'period_date' => $payment_email_data['period_date'],
                    'capacity' => $payment_email_data['capacity'],
                    'capacity_unit' => $payment_email_data['capacity_unit']

                ]);
            } else {
                Customer_purchase::create([
                    'user_id' => $input['user_id'],
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

                return response()->json($transaction->transaction_id);
            } catch (\Exception $e) {
                echo ($e->getMessage());
                return response()->json([
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            return false;
        }
    }

    public function sessionAction()
    {
        $params = ['page' => 'page-catalog-session'];

        foreach (app('config')->get('shop.page.catalog-session') as $name) {
            $params['aiheader'][$name] = Shop::get($name)->header();
            $params['aibody'][$name] = Shop::get($name)->body();
        }

        return Response::view(Shop::template('catalog.session'), $params)
            ->header('Cache-Control', 'no-cache');
    }

    public function homeAction()
    {
        $params = ['page' => 'page-catalog-home'];

        foreach (app('config')->get('shop.page.catalog-home') as $name) {
            $params['aiheader'][$name] = Shop::get($name)->header();
            $params['aibody'][$name] = Shop::get($name)->body();
        }

        return Response::view(Shop::template('catalog.home'), $params)
            ->header('Cache-Control', 'private, max-age=' . config('shop.cache_maxage', 30));
    }

    public function indexAction()
    {
        $params = ['page' => 'page-basket-index'];

        foreach (app('config')->get('shop.page.basket-index') as $name) {
            $params['aiheader'][$name] = Shop::get($name)->header();
            $params['aibody'][$name] = Shop::get($name)->body();
        }

        return Response::view(Shop::template('basket.index'), $params)
            ->header('Cache-Control', 'no-store, , max-age=0');
    }

    public function detailAction()
    {
        try {
            $params = ['page' => 'page-catalog-detail'];

            foreach (app('config')->get('shop.page.catalog-detail') as $name) {
                $params['aiheader'][$name] = Shop::get($name)->header();
                $params['aibody'][$name] = Shop::get($name)->body();
            }

            return Response::view(Shop::template('catalog.detail'), $params)
                ->header('Cache-Control', 'private, max-age=3600');
        } catch (\Exception $e) {
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                abort($e->getCode());
            }
            throw $e;
        }
    }
}
