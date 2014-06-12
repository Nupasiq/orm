<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * FlushEventTest
 *
 * @author robo
 */
class FlushEventTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
    }

    /**
     * @group DDC-3160
     */
    public function testNoUpdateOnInsert()
    {
        $listener = new OnFlushListener();
        $this->_em->getEventManager()->addEventListener(Events::onFlush, $listener);

        $user = new CmsUser;
        $user->username = 'romanb';
        $user->name = 'Roman';
        $user->status = 'Dev';

        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->refresh($user);

        $this->assertEquals('romanc', $user->username);
        $this->assertEquals(1, $listener->inserts);
        $this->assertEquals(0, $listener->updates);
    }
}

class OnFlushListener
{
    public $inserts = 0;
    public $updates = 0;

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->inserts++;
            if ($entity instanceof CmsUser) {
                $entity->username = 'romanc';
                $cm = $em->getClassMetadata(get_class($entity));
                $uow->recomputeSingleEntityChangeSet($cm, $entity);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->updates++;
        }
    }
}

