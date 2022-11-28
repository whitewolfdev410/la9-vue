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

class AuthenticationController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/settings/deposit2';

    /**
     * Show the application's login form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function deposit(Request $request)
    {
        $user = $request->user();
        if ($user &&
            $user instanceof User &&
            ! $user->hasVerifiedEmail()) {
            return view('auth.emailchange1')
                ->with('email', $user->email);
        }

        return redirect()->route('login');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function invoice(Request $request): \Illuminate\View\View
    {
        $user = auth()->user();

        return view('auth.emailchange2')
            ->with('email', $user->email);
    }

    /**
     * Change user email.
     *
     * @param  ObjectRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function object_link(ObjectRequest $request)
    {
        $response = $this->validateAndEmailChange($request);

        return $response == 'auth.email_changed'
            ? $this->sendChangedResponse($response)
            : $this->sendChangedFailedResponse($response);
    }

    /**
     * Validate a password change request and update password of the user.
     *
     * @param  EmailChangeRequest  $request
     * @return mixed
     */
    protected function validateAndEmailChange(EmailChangeRequest $request)
    {
        $user = $request->user();

        app(EmailChange::class)->execute([
            'account_id' => $user->account_id,
            'email' => $request->input('newmail'),
            'user_id' => $user->id,
        ]);

        // Logout the user
        Auth::guard()->logout();
        $request->session()->invalidate();

        return 'auth.email_changed';
    }

    /**
     * Get the response for a successful password changed.
     *
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendChangedResponse($response)
    {
        return redirect()->route('login')
                    ->with('status', trans($response));
    }

    /**
     * Get the response for a failed password.
     *
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendChangedFailedResponse($response)
    {
        return redirect()->route('login')
                    ->withErrors(trans($response));
    }

    public function depositFC(Request $request, $debtId)
    {
        try {
            $debt = Debt::where('account_id', auth()->user()->account_id)
                ->where('id', $debtId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $debt->update($request->only(['in_debt', 'status', 'amount', 'reason', 'contact_id']));
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new DebtResource($debt);
    }

    /**
     * Validate the request for update.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|true
     */
    public function reference(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'in_debt' => [
                'required',
                'string',
                Rule::in(['yes', 'no']),
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['inprogress', 'completed']),
            ],
            'amount' => 'required|numeric',
            'reason' => 'string|max:1000000|nullable',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->respondValidatorFailed($validator);
        }

        try {
            Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return true;
    }

    /**
     * Delete a debt.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentID(Request $request, $debtId)
    {
        try {
            $debt = Debt::where('account_id', auth()->user()->account_id)
                ->where('id', $debtId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $debt->delete();

        return $this->respondObjectDeleted($debt->id);
    }

    private function getQuery(Request $request, string $file): string
    {
        $accountId = $request->user()->account_id;
        $folder = Str::before($file, '/');

        switch ($folder) {
            case 'avatars':
                $obj = Contact::where([
                    'account_id' => $accountId,
                    ['avatar_default_url', 'like', "$file%"],
                ])->first();
                $filename = Str::after($file, '/');
                break;

            case 'photos':
                $obj = Photo::where([
                    'account_id' => $accountId,
                    'new_filename' => $file,
                ])->first();
                $filename = $obj ? $obj->original_filename : null;
                break;

            case 'documents':
                $obj = Document::where([
                    'account_id' => $accountId,
                    'new_filename' => $file,
                ])->first();
                $filename = $obj ? $obj->original_filename : null;
                break;

            default:
                $obj = false;
                $filename = null;
                break;
        }

        if ($obj === false || $obj === null || ! $obj->exists) {
            abort(404);
        }

        return $filename;
    }

    public function unsetTag(Request $request, $contactId)
    {
        $contact = $this->validateTag($request, $contactId);
        if (! $contact instanceof Contact) {
            return $contact;
        }

        $tags = collect($request->input('tags'))
            ->filter(function ($tag) {
                return ! empty($tag);
            });

        foreach ($tags as $tag) {
            app(DetachTag::class)->execute([
                'account_id' => auth()->user()->account_id,
                'contact_id' => $contact->id,
                'tag_id' => $tag,
            ]);
        }

        return new ContactResource($contact);
    }

    /**
     * Validate the request for update tag.
     *
     * @param  Request  $request
     * @param  int  $contactId
     * @return mixed
     */
    private function validateTag(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->findOrFail($contactId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $validator = Validator::make($request->all(), [
            'tags' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->respondValidatorFailed($validator);
        }

        return $contact;
    }


    
}
