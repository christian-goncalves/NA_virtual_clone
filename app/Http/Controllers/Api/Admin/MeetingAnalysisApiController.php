<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\NaVirtualMeetingAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MeetingAnalysisApiController extends Controller
{
    public function index(Request $request, NaVirtualMeetingAnalysisService $service): JsonResponse
    {
        try {
            $result = $service->search($request->query());
            $datatableMeta = data_get($result, 'meta.datatables', []);

            if ((bool) data_get($datatableMeta, 'enabled', false)) {
                return response()->json([
                    'ok' => true,
                    'draw' => (int) data_get($datatableMeta, 'draw', 0),
                    'recordsTotal' => (int) data_get($datatableMeta, 'records_total', 0),
                    'recordsFiltered' => (int) data_get($datatableMeta, 'records_filtered', 0),
                    'data' => data_get($result, 'rows', []),
                    'summary' => data_get($result, 'summary', []),
                    'applied_filters' => data_get($result, 'applied_filters', []),
                ]);
            }

            return response()->json([
                'ok' => true,
                'data' => data_get($result, 'rows', []),
                'summary' => data_get($result, 'summary', []),
                'meta' => data_get($result, 'meta', []),
                'applied_filters' => data_get($result, 'applied_filters', []),
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'ok' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        }
    }
}
