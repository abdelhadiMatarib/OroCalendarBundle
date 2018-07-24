<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\CalendarBundle\Autocomplete\UserCalendarHandler;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserCalendarHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $attachmentManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $treeProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $aclVoter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $aclHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityNameResolver;

    /** @var  UserCalendarHandler */
    protected $handler;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachmentManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclVoter = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->handler = new UserCalendarHandler(
            $this->em,
            $this->attachmentManager,
            'Oro\Bundle\CalendarBundle\Autocomplete\UserCalendarHandler',
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->treeProvider,
            $this->aclHelper
        );
        $this->entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->handler->setEntityNameResolver($this->entityNameResolver);
        $this->handler->setProperties([
            'avatar',
            'firstName',
            'fullName',
            'id',
            'lastName',
            'middleName',
            'namePrefix',
            'nameSuffix',
        ]);
    }

    public function testConvertItem()
    {
        $user = new User();
        ReflectionUtil::setId($user, 1);
        $user->setFirstName('testFirstName');
        $user->setLastName('testLastName');

        $calendar = new Calendar();
        ReflectionUtil::setId($calendar, 2);
        $calendar->setOwner($user);

        $result = $this->handler->convertItem($calendar);
        $this->assertEquals($result['id'], $calendar->getId());
        $this->assertEquals($result['userId'], $calendar->getOwner()->getId());
    }
}
