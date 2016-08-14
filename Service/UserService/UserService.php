<?php

namespace Polonairs\Dialtime\CommonBundle\Service\UserService;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Polonairs\Dialtime\ModelBundle\Entity\User;
use Polonairs\Dialtime\ModelBundle\Entity\Master;
use Polonairs\Dialtime\ModelBundle\Entity\Partner;
use Polonairs\Dialtime\ModelBundle\Entity\Phone;
use Polonairs\Dialtime\ModelBundle\Entity\Account;
use Polonairs\Dialtime\ModelBundle\Entity\Auth;	
use Polonairs\Dialtime\ModelBundle\Entity\Schedule;
use Polonairs\Dialtime\ModelBundle\Entity\Interval;				

class UserService extends DefaultAuthenticationSuccessHandler
{
	private $doctrine = null;
	private $encoder = null;
	protected $httpUtils = null;

	public function __construct(Doctrine $doctrine, $encoder, HttpUtils $httpUtils)
	{
		$this->doctrine = $doctrine;
		$this->encoder = $encoder;
		$this->httpUtils = $httpUtils; 
	}
	public static function normalizeLogin($username)
	{
		if ($username === null) return null;
		$phone = str_replace(["+", "-", "(", ")", " ", ".", "/", "\\", "*"], "", $username);
        if (preg_match("#[78]?(9[0-9]{9})#", $phone, $matches)) return "7".$matches[1];
        return $username;
	}
	public function normalizeUsername($username)
	{
		return UserService::normalizeLogin($username);
	}
    public function createPassword($length, $source)
    {
        $result = "";
        $count = strlen($source);
        for ($i = 0; $i < $length; $i++) $result .= substr($source, rand(0, $count), 1);
        return $result;
    }
    public function resetMasterPassword($username, $password)
	{

	}
	public function registerMaster($username, $password, $timezone, $ip = "unknown")
	{
		$em = $this->doctrine->getManager();

		$roles = $em->getRepository("ModelBundle:User")->loadUserRoles($username);

		if (count($roles) === 0)
		{
	        $em->getConnection()->beginTransaction();

			$user = (new User())
				->setUsername($username);
			$master = (new Master())
				->setUser($user);
			$phone = (new Phone())
				->setNumber($username)
				->setOwner($user);
			$account = (new Account())
				->setBalance(0)
				->setCurrency(Account::CURRENCY_RUR)
				->setOwner($user)
				->setState(Account::STATE_ACTIVE);
			$rate = (new Account())
				->setBalance(0)
				->setCurrency(Account::CURRENCY_TCR)
				->setOwner($user)
				->setState(Account::STATE_ACTIVE);
	        $encoded = $this->encoder->encodePassword($master, $password);
    		$schedule = (new Schedule())
    			->setOwner($user)
    			->setTimezone($timezone);
    		for ($i = 0; $i < 5; $i++)
    		{
    			$int = (new Interval())
    				->setSchedule($schedule)
    				->setFrom(10*60 + 1440*$i)
    				->setTo(18*60 + 1440*$i - 1);
    			$em->persist($int);
    		}
			$user
				->setMainAccount($account)
				->setRateAccount($rate)
				->setPassword($encoded)
				->setMainSchedule($schedule);
			$auth = (new Auth())
				->setType(Auth::TYPE_REGISTRATION)
				->setUser($user)
				->setIp($ip);

    		$em->persist($schedule);
			$em->persist($user);
			$em->persist($master);
			$em->persist($phone);
			$em->persist($account);

			$em->flush();
			$em->getConnection()->commit();
		}
		else
		{
			if (array_key_exists("master", $roles)) throw new UserAlreadyRegisteredException($username);
			else throw new UserHaveAnotherRoleException($username);
		}
	}
	public function registerPartner($username, $password, $timezone, $ip = "unknown")
	{
		$em = $this->doctrine->getManager();

		$roles = $em->getRepository("ModelBundle:User")->loadUserRoles($username);

		if (count($roles) === 0)
		{
	        $em->getConnection()->beginTransaction();

			$user = (new User())
				->setUsername($username);
			$partner = (new Partner())
				->setUser($user);
			$phone = (new Phone())
				->setNumber($username)
				->setOwner($user);
			$account = (new Account())
				->setBalance(0)
				->setCurrency(Account::CURRENCY_RUR)
				->setOwner($user)
				->setState(Account::STATE_ACTIVE);
	        $encoded = $this->encoder->encodePassword($partner, $password);
			$user
				->setMainAccount($account)
				->setPassword($encoded);
			$auth = (new Auth())
				->setType(Auth::TYPE_REGISTRATION)
				->setUser($user)
				->setIp($ip);

			$em->persist($user);
			$em->persist($partner);
			$em->persist($phone);
			$em->persist($account);

			$em->flush();
			$em->getConnection()->commit();
		}
		else
		{
			if (array_key_exists("partner", $roles)) throw new UserAlreadyRegisteredException($username);
			else throw new UserHaveAnotherRoleException($username);
		}
	}
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
    	$user = $token->getUser();

		$em = $this->doctrine->getManager();
        if ($user->getUser()->getState() === User::STATE_JUST_REGISTERED) 
        {
			$user->getUser()->setState(User::STATE_ACTIVE);
			$phones = $em->getRepository("ModelBundle:Phone")->loadByOwner($user->getUser());
			$phones[0]->setMain(true)->setConfirmed(true);
			dump($phones);
			$em->persist($user->getUser());
			
			$em->persist($phones[0]);
        }
        $em->persist(
        	(new Auth())
        	->setType(Auth::TYPE_LOGIN)
        	->setUser($user->getUser())
        	->setIp($request->getClientIp()));
        $em->flush();
        return $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl($request));
	}
}
