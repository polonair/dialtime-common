<?php

namespace Polonairs\Dialtime\CommonBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

class SystemSettingsService
{
	private $doctrine = null;

	public function __construct(Doctrine $doctrine)
	{
		$this->doctrine = $doctrine;
	}
	public function get($parameterName, $default = null)
	{
		$p = $this->doctrine->getManager()->getRepository("ModelBundle:Parameter")->loadValue($parameterName);
		return ($p === null)?($default):$p;
	}
}