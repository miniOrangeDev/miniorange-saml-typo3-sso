<?php

namespace Miniorange;

use Miniorange\classes\actions\SendAuthnRequest;
use Miniorange\helper\Utilities;

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