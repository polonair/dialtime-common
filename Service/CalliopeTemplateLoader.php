<?php

namespace Polonairs\Dialtime\CommonBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

class CalliopeTemplateLoader implements \Twig_LoaderInterface
{
    protected $doctrine;
    protected $template;

    public function __construct(Doctrine $doctrine)
    {
    	$this->doctrine = $doctrine;
    }
    public function getSource($name)
    {
    	$t = $this->getValue($name);
    	if (!$t) throw new \Twig_Error_Loader(sprintf('Template "%s" does not exist.', $name));
        return $t->getSource();
    }
    public function exists($name)
    {
    	if($this->getValue($name)) return true;
    	return false;
    }
    public function getCacheKey($name)
    {
        return $name;
    }
    public function isFresh($name, $time)
    {
    	$t = $this->getValue($name);
        if ($t) return $t->getCreatedAt()->getTimestamp() <= $time;
        return false;
    }
    protected function getValue($name)
    {
        if ($t = $this->doctrine->getRepository("ModelBundle:Template")->loadOneByName($name))
        {
            return $this->template = $t;
        }
        return false;
    }
}
