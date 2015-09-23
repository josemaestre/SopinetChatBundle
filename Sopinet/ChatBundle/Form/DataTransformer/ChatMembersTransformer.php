<?php
// src/AppBundle/Form/DataTransformer/IssueToNumberTransformer.php
namespace Sopinet\ChatBundle\Form\DataTransformer;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Security\Acl\Exception\Exception;

class ChatMembersTransformer implements DataTransformerInterface
{
    private $container;
    private $em;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();
    }

    public function transform($chatMembersArrayObject)
    {
        if($chatMembersArrayObject === null || count($chatMembersArrayObject) == 0) {
            return;
        }

        $uIDArray = array();
        foreach($chatMembersArrayObject as $userObject) {
            $uIDArray[] = $userObject->getId();
        }

        return implode(',', $uIDArray);
    }

    public function reverseTransform($chatMembersArrayString)
    {
        $uIDArray = explode(',',$chatMembersArrayString);
        $reUser = $this->em->getRepository('ApplicationSonataUserBundle:User');

        $uObjectArray = array();
        foreach($uIDArray as $uID) {
            $user = $reUser->find($uID);
            if ($user) {
                $uObjectArray[] = $user;
            } else {
                throw new Exception("User not found");
            }
        }

        return $uObjectArray;
    }
}