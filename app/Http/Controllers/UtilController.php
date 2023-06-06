<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\{CronosElements};
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\ZohoOAuth;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class UtilController extends Controller
{
    public function takeWebhook()
	{
		$answer = '';

		$json_event = file_get_contents('php://input', true);
		$event = json_decode($json_event);

		if (!isset($event->type, $event->data) || !ctype_digit($event->data->id))
		{
			http_response_code(400);
			return;
		}

		$answer = $event;

		return($answer);
	}

    //te da una fecha en string, -x minutos
	public function getMinutesBefore($x)
	{
		$answer = '';

		$today = date('Y-m-d G:i:s');
		$minsBefore = date_sub(date_create($today),date_interval_create_from_date_string($x . " minutes"));
		$minsBefore = date_format($minsBefore,'Y-m-d  G:i:s');
		$answer = $minsBefore;

		return($answer);
	}

}