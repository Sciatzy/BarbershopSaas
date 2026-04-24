<?php

namespace App\Http\Controllers;

use App\Support\SystemVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemVersionController extends Controller
{
    public function __invoke(Request $request, SystemVersion $systemVersion): JsonResponse
    {
        $resolved = $systemVersion->resolve();

        if ($request->boolean('full')) {
            return response()->json($resolved)
                ->header('X-System-Version', (string) $resolved['version']);
        }

        return response()->json([
            'app_name' => $resolved['app_name'],
            'version' => $resolved['version'],
            'status' => $resolved['status'],
            'branch' => $resolved['branch'],
            'commit' => $resolved['short_commit'],
            'generated_at' => $resolved['generated_at'],
        ])->header('X-System-Version', (string) $resolved['version']);
    }
}
