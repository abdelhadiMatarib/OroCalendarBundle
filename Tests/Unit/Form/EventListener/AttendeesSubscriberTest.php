<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CalendarBundle\Form\EventListener\AttendeesSubscriber;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class AttendeesSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /** @var AttendeesSubscriber */
    protected $attendeesSubscriber;

    /** @var SecurityFacade */
    protected $securityFacade;

    public function setUp()
    {
        $this->attendeeRelationManager = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attendeesSubscriber = new AttendeesSubscriber($this->attendeeRelationManager, $this->securityFacade);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SUBMIT  => ['fixSubmittedData', 100],
                FormEvents::POST_SUBMIT => ['postSubmit', -100],
            ],
            $this->attendeesSubscriber->getSubscribedEvents()
        );
    }

    /**
     * @dataProvider preSubmitProvider
     */
    public function testPreSubmit($eventData, $formData, $expectedData)
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($formData));

        $event = new FormEvent($form, $eventData);
        $this->attendeesSubscriber->fixSubmittedData($event);
        $this->assertEquals($expectedData, $event->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function preSubmitProvider()
    {
        return [
            'empty attendees' => [
                [
                    'displayName' => 'existing',
                    'email' => 'existing@example.com',
                ],
                [],
                [
                    'displayName' => 'existing',
                    'email' => 'existing@example.com',
                ],
            ],
            'empty data' => [
                [],
                [
                    'displayName' => 'existing',
                    'email' => 'existing@example.com',
                ],
                [],
            ],
            'missing email and displayName in attendees' => [
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayName' => 'new',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayName' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
                new ArrayCollection([
                    (new Attendee())
                        ->setDisplayName('existing')
                        ->setEmail('existing@example.com'),
                    (new Attendee()),
                ]),
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayName' => 'new',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayName' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
            ],
            'missing email in data' => [
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayName' => 'new',
                    ],
                    [
                        'displayName' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
                new ArrayCollection([
                    (new Attendee())
                        ->setDisplayName('existing')
                        ->setEmail('existing@example.com'),
                    (new Attendee())
                        ->setDisplayName('toBeRemoved')
                        ->setEmail('toBeRemoved@example.com'),
                ]),
                [
                    0 => [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    2 => [
                        'displayName' => 'new',
                    ],
                    3 => [
                        'displayName' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
            ],
            'valid data' => [
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayName' => 'new',
                        'email' => 'new@example.com',
                    ],
                    [
                        'displayName' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
                new ArrayCollection([
                    (new Attendee())
                        ->setDisplayName('existing')
                        ->setEmail('existing@example.com'),
                    (new Attendee())
                        ->setDisplayName('toBeRemoved')
                        ->setEmail('toBeRemoved@example.com'),
                ]),
                [
                    0 => [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    2 => [
                        'displayName' => 'new',
                        'email' => 'new@example.com',
                    ],
                    3 => [
                        'displayName' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
            ],
        ];
    }

    public function testPostSubmit()
    {
        $attendees = new ArrayCollection([new Attendee(1)]);

        $event = new FormEvent(
            $this->getMock('Symfony\Component\Form\FormInterface'),
            $attendees
        );

        $this->attendeeRelationManager->expects($this->once())
            ->method('bindAttendees')
            ->with($attendees);

        $this->attendeesSubscriber->postSubmit($event);
    }

    public function testPostSubmitWithNullData()
    {
        $this->attendeeRelationManager->expects($this->never())
            ->method('bindAttendees');

        $this->attendeesSubscriber->postSubmit(
            new FormEvent($this->getMock('Symfony\Component\Form\FormInterface'), null)
        );
    }
}
