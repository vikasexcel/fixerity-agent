<?php

namespace App\Http\Controllers\Api;

use App\Classes\NotificationClass;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwimlException;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;
use Twilio\Rest\Client;
use Twilio\Twiml;

class VoiceCallController extends Controller
{

    /**
     * VoiceCallController constructor.
     */
    public function __construct()
    {
        config(['logging.channels.my_custom.path' => storage_path('logs/my_custom/' . 'MakeCall' . '.log')]);
    }
    public function getAccessToken(Request $request)
    {
        $twilioAccountSid = config('services.twilio.account_sid');
        $twilioApiKey = config('services.twilio.api_key');
        $twilioApiSecret = config('services.twilio.api_secret');
        $outgoingApplicationSid = config('services.twilio.app_sid');
        $identity = $request->get('identity', 'abc');

// Create access token, which we will serialize and send to the client
        $token = new AccessToken(
            $twilioAccountSid,
            $twilioApiKey,
            $twilioApiSecret,
            3600,
            $identity
        );

// Create Voice grant
        $voiceGrant = new VoiceGrant();
        $voiceGrant->setOutgoingApplicationSid($outgoingApplicationSid);

// Optional: add to allow incoming calls
        $voiceGrant->setIncomingAllow(true);

// Add grant to token
        $token->addGrant($voiceGrant);

// render token to string
        echo $token->toJWT();
    }

    public function getMakeCall(Request $request)
    {
        $ACCOUNT_SID = config('services.twilio.account_sid');
        $API_KEY = config('services.twilio.api_key');
        $API_KEY_SECRET = config('services.twilio.api_secret');

//        $callerId = 'client:quick_start';
//        $to = isset($_POST["to"]) ? $_POST["to"] : "";
//        if (!isset($to) || empty($to)) {
//            $to = isset($_GET["to"]) ? $_GET["to"] : "";
//        }
        /*
         * Use a valid Twilio number by adding to your account via https://www.twilio.com/console/phone-numbers/verified
         */
//        $callerNumber = '+911234567890';
//        $callerNumber = 'test';
//        try {
//            $response = new Twiml();
//        } catch (TwimlException $e) {
//            return "TwimlException  " . $e;
//        }
//        if (!isset($to) || empty($to)) {
//            $response->say('Congratulations! You have just made your first call! Good bye.');
//        } else if (is_numeric($to)) {
//            $dial = $response->dial(
//                array(
//                    'callerId' => $callerNumber
//                ));
//            $dial->number($to);
//        } else {
//            $dial = $response->dial(
//                array(
//                    'callerId' => $callerId
//                ));
//            $dial->client($to);
//        }
//        return $response;

        $identity = 'alice';
        $callerNumber = config('services.twilio.caller_number', '+911234567890');

        $to = $request->get('to');
        $callerId = $request->get('callerId');


        if ($to == Null) {
            $to = $identity;
        }

        if ($callerId == Null) {
            $callerId = 'client:quick_start';
        }

        /*$to = isset($_GET["to"]) ? $_GET["to"] : "";

        if (!isset($to) || empty($to)) {
            $to = isset($POST["to"]) ? $_POST["to"] : "";
        }
        $callerId = isset($_GET["callerId"]) ? 'client:' . $_GET["callerId"] : "";
        if (!isset($callerId) || empty($callerId)) {
            $callerId = isset($POST["callerId"]) ? 'client:' . $_POST["callerId"] : "";
        }

        if ($callerId == Null) {
            $callerId = 'client:quick_start';
        }*/
//        $client = new Client($API_KEY, $API_KEY_SECRET, $ACCOUNT_SID);

        try {
            $client = new Client($API_KEY, $API_KEY_SECRET, $ACCOUNT_SID);
        } catch (ConfigurationException $e) {
            return $e;
        }
        $call = NULL;
        if (!isset($to) || empty($to)) {
            $call = $client->calls->create(
                'client:alice', // Call this number
                $callerId,      // From a valid Twilio number
                array(
                    'url' => route('post:call_incoming', [$to, $callerId])
                )
            );
        } else if (is_numeric($to)) {
            $call = $client->calls->create(
                $to,           // Call this number
                $callerNumber, // From a valid Twilio number
                array(
                    'url' => route('post:call_incoming', [$to, $callerId])
                )
            );
        } else {
            $call = $client->calls->create(
                'client:' . $to, // Call this number
                $callerId,     // From a valid Twilio number
                array(
                    'url' => route('post:call_incoming', [$to, $callerId])
                )
            );
        }
        return $call->sid;
    }

    public function getIncoming(Request $request, $to, $caller_id)
    {
//        try {
//            $response = new Twiml();
//        } catch (TwimlException $e) {
//        }
//        $response->say('Congratulations! You have received your first inbound call! Good bye.');
//        return $response;

        $ACCOUNT_SID = config('services.twilio.account_sid');
        $API_KEY = config('services.twilio.api_key');
        $API_KEY_SECRET = config('services.twilio.api_secret');

//        $callerId = 'client:quick_start';
        $to = isset($to) ? $to : "";
        if (!isset($to) || empty($to)) {
            $to = isset($to) ? $to : "";
        }
        $caller_id = isset($caller_id) ? "client:" . $caller_id : "";
        if (!isset($to) || empty($to)) {
            $caller_id = isset($caller_id) ? "client:" . $caller_id : "";
        }
        /*
         * Use a valid Twilio number by adding to your account via https://www.twilio.com/console/phone-numbers/verified
         */
        $callerNumber = config('services.twilio.caller_number', '+911234567890');
        try {
            $response = new Twiml();
        } catch (TwimlException $e) {
            return "TwimlException  " . $e;
        }
        if (!isset($to) || empty($to)) {
            $response->say('Congratulations! You have just made your first call! Good bye.');
        } elseif (is_numeric($to)) {
            $dial = $response->dial(
                array(
                    'callerId' => $callerNumber
                ));
            $dial->number($to);
        } else {
            $dial = $response->dial(
                array(
                    'callerId' => $caller_id
                ));
            $dial->client($to);
        }
        return $response;
    }
}
