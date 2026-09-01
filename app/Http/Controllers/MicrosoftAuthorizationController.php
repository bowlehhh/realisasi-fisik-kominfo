<?php

namespace App\Http\Controllers;

use App\Services\MicrosoftGraphExcelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class MicrosoftAuthorizationController extends Controller
{
    public function redirect(MicrosoftGraphExcelService $microsoft): RedirectResponse
    {
        if (! $microsoft->isConfigured()) {
            return redirect()->route('dashboard.index');
        }

        $state = Str::random(64);

        request()->session()->put('microsoft_oauth_state', $state);

        return redirect()->away($microsoft->authorizationUrl($state));
    }

    public function callback(Request $request, MicrosoftGraphExcelService $microsoft): RedirectResponse
    {
        $expectedState = $request->session()->pull('microsoft_oauth_state');
        $receivedState = $request->query('state');

        if (! is_string($expectedState) || ! is_string($receivedState) || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->route('dashboard.index');
        }

        $code = $request->query('code');

        if (! is_string($code) || blank($code) || $request->query('error')) {
            return redirect()->route('dashboard.index');
        }

        try {
            $microsoft->storeAuthorizationCode($code);
        } catch (Throwable) {
            return redirect()->route('dashboard.index');
        }

        return redirect()->route('dashboard.index');
    }

    public function disconnect(MicrosoftGraphExcelService $microsoft): RedirectResponse
    {
        $microsoft->disconnect();

        return redirect()->route('dashboard.index');
    }
}
