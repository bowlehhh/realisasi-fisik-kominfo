<?php

namespace App\Http\Controllers;

use App\Exceptions\MicrosoftAuthorizationRequiredException;
use App\Exceptions\MicrosoftReauthorizationRequiredException;
use App\Services\MicrosoftGraphExcelService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class DashboardExcelValuesController extends Controller
{
    public function __invoke(MicrosoftGraphExcelService $excel): JsonResponse
    {
        if (! $excel->isConfigured() || ! $excel->hasAuthorization()) {
            return response()->json([
                'status' => 'authorization_required',
            ], 401);
        }

        try {
            return response()->json([
                'status' => 'ok',
                ...$excel->values(),
            ]);
        } catch (MicrosoftAuthorizationRequiredException) {
            return response()->json([
                'status' => 'authorization_required',
            ], 401);
        } catch (MicrosoftReauthorizationRequiredException) {
            return response()->json([
                'status' => 'reauthorization_required',
            ], 401);
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            report($exception);

            return response()->json([
                'status' => 'unavailable',
            ], 502);
        }
    }
}
