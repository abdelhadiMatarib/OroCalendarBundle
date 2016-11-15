<?php

namespace Oro\Bundle\CalendarBundle\Form\DataTransformer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class AttendeesToViewTransformer extends ContextsToViewTransformer
{
    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /**
     * @param EntityManager $entityManager
     * @param ConfigManager $configManager
     * @param TranslatorInterface $translator
     * @param TokenStorageInterface $securityTokenStorage
     * @param EventDispatcherInterface $dispatcher
     * @param AttendeeRelationManager $attendeeRelationManager
     * @param EntityNameResolver $entityNameResolver
     */
    public function __construct(
        EntityManager $entityManager,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        TokenStorageInterface $securityTokenStorage,
        EventDispatcherInterface $dispatcher,
        AttendeeRelationManager $attendeeRelationManager,
        EntityNameResolver $entityNameResolver
    ) {
        parent::__construct(
            $entityManager,
            $configManager,
            $translator,
            $securityTokenStorage,
            $dispatcher,
            $entityNameResolver
        );

        $this->attendeeRelationManager = $attendeeRelationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        return parent::transform($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $entities = parent::reverseTransform($value);
        if (!$entities) {
            return $entities;
        }

        return array_map(
            function ($entity) {
                return $this->attendeeRelationManager->createAttendee($entity) ?: $entity;
            },
            $entities
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getClassLabel($className)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param $object Attendee
     */
    protected function getResult($text, $object)
    {
        $result = parent::getResult($text, $object);
        $result['text'] = $this->attendeeRelationManager->getRelatedDisplayName($object);
        $result['hidden'] = !$this->attendeeRelationManager->getRelatedEntity($object);
        $result['displayName'] = $object->getDisplayName();
        $result['email'] = $object->getEmail();
        $result['type'] = $object->getType() ? $object->getType()->getId() : null;
        $result['status'] = $object->getStatusCode();

        return $result;
    }
}
