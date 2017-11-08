<?php

namespace Sopinet\ChatBundle\Service\Consumer;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Sopinet\ChatBundle\Entity\Device;
use Sopinet\ChatBundle\Entity\MessagePackage;
use Sopinet\ChatBundle\Service\MessageHelper;
use Symfony\Component\DependencyInjection\Dump\Container;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SendMessagePackageConsumer
 * @package Sopinet\ChatBundle\Service\Consumer
 */
class SendMessagePackageConsumer implements ConsumerInterface
{
    protected $request;

    public function setRequest(RequestStack $request_stack)
    {
        $this->request = $request_stack->getCurrentRequest();
    }

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Process the message
     *
     * @param AMQPMessage $msg
     */
    public function execute(AMQPMessage $msg)
    {

        $logger = $this->container->get("logger");

        $jsonBody = json_decode($msg->body);
        if ($jsonBody == null){
            $logger->error("JSON NULL");
            return false;
        }
        $messagePackageId = $jsonBody->messagePackageId;

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.default_entity_manager');
        /** @var MessageHelper $messageHelper */
        $messageHelper = $this->container->get('sopinet_chatbundle_messagehelper');
        $reMessagePackage = $em->getRepository('SopinetChatBundle:MessagePackage');
        /** @var MessagePackage $messagePackage */
        $messagePackage = $reMessagePackage->findOneById($messagePackageId);
        if ($messagePackage == null || $messagePackage->getMessage() == null) {
            return false;
        }
        $messagePackage->setProcessed($messagePackage->getToDevice()->getDeviceType() == Device::TYPE_ANDROID);
        try {
            $response = $messageHelper->sendRealMessageToDevice($messagePackage->getMessage(), $messagePackage->getToDevice(), $messagePackage->getToUser(), $this->request, true);
        } catch(\Exception $e) {
            throw $e;
        }
        $messagePackage->setStatus($response ?
            MessagePackage::STATUS_OK
            : MessagePackage::STATUS_KO);
        $em->persist($messagePackage);
        $em->flush();
        return true;
    }
}