<?php

namespace Polonairs\Dialtime\CommonBundle\Service\UserService;

class UserHaveAnotherRoleException extends \InvalidArgumentException
{
	public function __construct($username){}
}
