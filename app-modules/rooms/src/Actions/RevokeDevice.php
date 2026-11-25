<?php

namespace Modules\Rooms\Actions;

use Modules\Rooms\Models\Device;

final class RevokeDevice
{
    public function execute(Device $device): void
    {
        $device->revoke();
    }
}
