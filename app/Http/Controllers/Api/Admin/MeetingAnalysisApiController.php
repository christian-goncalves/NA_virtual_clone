<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\NaVirtualMeetingAnalysisPresetService;
use App\Services\NaVirtualMeetingAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

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

    public function exportCsv(Request $request, NaVirtualMeetingAnalysisService $service): Response
    {
        try {
            $csv = $service->exportCsv($request->query());

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="meeting-analysis.csv"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
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

    public function listPresets(Request $request, NaVirtualMeetingAnalysisPresetService $service): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'ok' => false,
                'code' => 'UNAUTHENTICATED',
                'message' => 'Authentication required.',
            ], 401);
        }

        return response()->json([
            'ok' => true,
            'data' => $service->listForUser($user),
        ]);
    }

    public function storePreset(Request $request, NaVirtualMeetingAnalysisPresetService $service): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'ok' => false,
                'code' => 'UNAUTHENTICATED',
                'message' => 'Authentication required.',
            ], 401);
        }

        try {
            $preset = $service->saveForUser($user, $request->all());

            return response()->json([
                'ok' => true,
                'data' => $preset,
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

    public function destroyPreset(Request $request, int $presetId, NaVirtualMeetingAnalysisPresetService $service): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'ok' => false,
                'code' => 'UNAUTHENTICATED',
                'message' => 'Authentication required.',
            ], 401);
        }

        $deleted = $service->deleteForUser($user, $presetId);

        if (! $deleted) {
            return response()->json([
                'ok' => false,
                'code' => 'NOT_FOUND',
                'message' => 'Preset not found.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
        ]);
    }
}
