<?php

namespace Modules\Rooms\Exceptions;

use Exception;

final class PairingCodeExpiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('The pairing code has expired.');
    }
}
