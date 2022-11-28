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

class SimulController extends Controller
{
    public function index()
    {
        $tasks = auth()->user()->tasks();
        return view('dashboard', compact('tasks'));
    }
    public function add()
    {
    	return view('add');
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'description' => 'required'
        ]);
    	$task = new Task();
    	$task->description = $request->description;
    	$task->user_id = auth()->user()->id;
    	$task->save();
    	return redirect('/dashboard'); 
    }

    public function getUuidAttribute(): string
    {
        if (! isset($this->attributes['uuid']) || empty($this->attributes['uuid']) || $this->attributes['uuid'] == null) {
            return (string) tap(Str::uuid()->toString(), function ($uuid) {
                $this->forceFill([
                    'uuid' => $uuid,
                ]);
                $this->save(['timestamps' => false]);
            });
        }

        return (string) $this->attributes['uuid'];
    }

    public function getChanges($calendarId, $syncToken): ?array
    {
        $token = null;
        $timestamp = null;
        if (! empty($syncToken)) {
            $token = $this->getSyncToken($calendarId, $syncToken);

            if (is_null($token)) {
                // syncToken is not recognized
                return null;
            }

            $timestamp = $token->timestamp;
        }

        $objs = $this->getObjects($calendarId);

        $modified = $objs->filter(function ($obj) use ($timestamp) {
            return ! is_null($timestamp) &&
                   $obj->updated_at > $timestamp &&
                   $obj->created_at < $timestamp;
        });
        $added = $objs->filter(function ($obj) use ($timestamp) {
            return is_null($timestamp) ||
                   $obj->created_at >= $timestamp;
        });
        $deleted = $this->getDeletedObjects($calendarId)
            ->filter(function ($obj) use ($timestamp) {
                $d = $obj->deleted_at;

                return is_null($timestamp) ||
                       $obj->deleted_at >= $timestamp;
            });

        return [
            'syncToken' => $this->refreshSyncToken($calendarId)->id,
            'added' => $added->map(function ($obj) {
                return $this->encodeUri($obj);
            })->values()->toArray(),
            'modified' => $modified->map(function ($obj) {
                $this->refreshObject($obj);

                return $this->encodeUri($obj);
            })->values()->toArray(),
            'deleted' => $deleted->map(function ($obj) {
                return $this->encodeUri($obj);
            })->values()->toArray(),
        ];
    }
}
