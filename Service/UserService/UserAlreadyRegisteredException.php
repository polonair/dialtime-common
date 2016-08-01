<?php

namespace Polonairs\Dialtime\CommonBundle\Service\UserService;

class UserAlreadyRegisteredException extends \InvalidArgumentException
{
	public function __construct($username){}
}

