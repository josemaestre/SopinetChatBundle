<?php

namespace Sopinet\ChatBundle\Form;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sopinet\ChatBundle\Entity\Device;
use Sopinet\ChatBundle\Form\DataTransformer\DeviceTransformer;
use Sopinet\ChatBundle\Form\DataTransformer\LoginMultipleTransformer;
use Sopinet\ChatBundle\Form\DataTransformer\LoginTransformer;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToTimestampTransformer;

class DeviceType extends AbstractType
{
    protected $container;
    protected $request;
    protected $device;

    public function __construct(Container $container, Request $request, Device $device)
    {
        $this->container = $container;
        $this->request = $request;
        $this->device = $device;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('deviceId')
            ->add('deviceGCMId', null, array(
                'required' => false
            ))
            ->add('user')
            ->add('deviceType', 'choice', array(
                'choices' => array(
                    Device::TYPE_ANDROID => "Android",
                    Device::TYPE_IOS => "iOS",
					Device::TYPE_WEB => "Web",
                    Device::TYPE_VIRTUALNONE => "VirtualNone"
                ),
            ))
        ;

        $builder->get('user')
            ->addModelTransformer(new LoginMultipleTransformer($this->container, $this->request, $this->device));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Sopinet\ChatBundle\Entity\Device',
            'csrf_protection'   => false
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sopinet_chatbundle_type';
    }
}
