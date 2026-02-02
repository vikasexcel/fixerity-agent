<?php

namespace App\Classes;

use Illuminate\Http\Request;

/**
 * Plain-PHP stub for AuthAlertClass when SourceGuardian loader is not installed.
 * The original class is protected by SourceGuardian; this stub allows local dev
 * (signup, login, buyer agent) without the loader.
 *
 * Original backup: AuthAlertClass.php.sourceguardian
 */
class AuthAlertClass
{
    /**
     * Authorization check used by customer/on-demand register and login.
     * Returns success so requests proceed; no app-key validation in local stub.
     */
    public function checkAuthorizationApp(Request $request)
    {
        return response()->json(['status' => 1]);
    }

    /**
     * FCM push notification – stub returns empty token so FCM calls don’t crash.
     * Push won’t be sent without real FCM config.
     */
    public function fetchFCMBearerToken()
    {
        return '';
    }

    /**
     * Send push notification – stub no-op for local dev.
     * Call sites use 3, 4, or 5 arguments.
     */
    public function sendFlowNotification($device_token, $notification_data_array, $arg3 = 0, $arg4 = null, $arg5 = 0)
    {
        return (object) ['success' => true];
    }
}
