<?php

namespace MiniOrange;

use MiniOrange\Classes\Actions\SendAuthnRequest;
use MiniOrange\Helper\Utilities;

include_once 'autoload.php';

final class Login
{
    public function __construct()
    {   

        try {
            SendAuthnRequest::execute();
        } catch (\Exception $e) {
            Utilities::showErrorMessage($e->getMessage());
        }
    }
}
new Login();