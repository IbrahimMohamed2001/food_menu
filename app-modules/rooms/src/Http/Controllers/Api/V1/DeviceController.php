<?php

namespace Modules\Rooms\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Rooms\Actions\PairDevice;
use Modules\Rooms\Exceptions\PairingCodeExpiredException;
use Modules\Rooms\Exceptions\PairingCodeNotFoundException;
use Modules\Rooms\Exceptions\PairingCodeUsedException;
use Modules\Rooms\Http\Requests\PairDeviceRequest;
use Modules\Rooms\Http\Resources\DeviceResource;

class DeviceController extends Controller
{
    /**
     * POST /api/v1/devices/pair
     *
     * Exchange a one-time pairing code for a device token.
     */
    public function pair(PairDeviceRequest $request, PairDevice $pairDevice): JsonResponse
    {
        try {
            $device = $pairDevice->execute(
                code: $request->string('code')->toString(),
                deviceName: $request->string('device_name')->toString() ?: null,
                deviceIdentifier: $request->string('device_identifier')->toString() ?: null,
            );
        } catch (PairingCodeNotFoundException | PairingCodeExpiredException | PairingCodeUsedException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'code' => [$e->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'data' => [
                'token' => $device->plainTextToken,
                'device' => new DeviceResource($device),
            ],
        ], 201);
    }

    /**
     * GET /api/v1/devices/me
     *
     * Return the authenticated tablet's name and room.
     */
    public function me(Request $request): JsonResponse
    {
        $device = $request->user();

        return response()->json([
            'data' => [
                'device' => new DeviceResource($device->load('room')),
            ],
        ]);
    }
}
