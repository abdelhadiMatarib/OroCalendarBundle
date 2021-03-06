<?php

namespace Oro\Bundle\CalendarBundle\Ownership;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerAssignmentChecker;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class CalendarOwnerAssignmentChecker extends OwnerAssignmentChecker
{
    /**
     * {@inheritdoc}
     */
    protected function getHasAssignmentsQueryBuilder($ownerId, $entityClassName, $ownerFieldName, EntityManager $em)
    {
        $qb = parent::getHasAssignmentsQueryBuilder($ownerId, $entityClassName, $ownerFieldName, $em);

        $qbParam = $em->getRepository('OroCalendarBundle:CalendarEvent')
            ->createQueryBuilder('calendarEvents')
            ->innerJoin('calendarEvents.calendar', 'calendar')
            ->where('calendar.id = entity.id');

        QueryBuilderUtil::checkParameter($qbParam);

        // if a default calendar (its name is NULL) has no events assume that it can be deleted
        // without any confirmation and as result we can remove such calendar from assignment list
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNotNull('entity.name'),
                $qb->expr()->andX(
                    $qb->expr()->isNull('entity.name'),
                    $qb->expr()->exists(
                        $qbParam
                    )
                )
            )
        );

        return $qb;
    }
}
