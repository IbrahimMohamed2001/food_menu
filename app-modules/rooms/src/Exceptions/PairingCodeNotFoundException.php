<?php

namespace Modules\Rooms\Exceptions;

use Exception;

final class PairingCodeNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('The pairing code is invalid.');
    }
}
