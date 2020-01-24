<?php

namespace Miniorange\helper\Exception;

use Miniorange\helper\Messages;

/**
 * Exception denotes that the SAML Issuer value is missing.
 */
class MissingIssuerValueException extends \Exception
{
	public function __construct() 
	{
		$message 	= Messages::parse('MISSING_ISSUER_VALUE');
		$code 		= 123;		
        parent::__construct($message, $code, NULL);
    }

    public function __toString() 
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}