<?php

namespace Gedmo\Tests\Loggable;

use Doctrine\Common\EventManager;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Tests\Loggable\Fixture\Entity\Address;
use Gedmo\Tests\Loggable\Fixture\Entity\Article;
use Gedmo\Tests\Loggable\Fixture\Entity\Comment;
use Gedmo\Tests\Loggable\Fixture\Entity\Geo;
use Gedmo\Tests\Loggable\Fixture\Entity\GeoLocation;
use Gedmo\Tests\Loggable\Fixture\Entity\RelatedArticle;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class LoggableEntityTest extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;
    public const COMMENT = Comment::class;
    public const RELATED_ARTICLE = RelatedArticle::class;
    public const COMMENT_LOG = \Gedmo\Tests\Loggable\Fixture\Entity\Log\Comment::class;

    private $articleId;
    private $LoggableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->LoggableListener = new LoggableListener();
        $this->LoggableListener->setUsername('jules');
        $evm->addEventSubscriber($this->LoggableListener);

        $this->em = $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldHandleClonedEntity()
    {
        $art0 = new Article();
        $art0->setTitle('Title');

        $this->em->persist($art0);
        $this->em->flush();

        $art1 = clone $art0;
        $art1->setTitle('Cloned');
        $this->em->persist($art1);
        $this->em->flush();

        $logRepo = $this->em->getRepository(LogEntry::class);
        $logs = $logRepo->findAll();
        static::assertCount(2, $logs);
        static::assertSame('create', $logs[0]->getAction());
        static::assertSame('create', $logs[1]->getAction());
        static::assertNotSame($logs[0]->getObjectId(), $logs[1]->getObjectId());
    }

    public function testLoggable()
    {
        $logRepo = $this->em->getRepository(LogEntry::class);
        $articleRepo = $this->em->getRepository(self::ARTICLE);
        static::assertCount(0, $logRepo->findAll());

        $art0 = new Article();
        $art0->setTitle('Title');

        $this->em->persist($art0);
        $this->em->flush();

        $log = $logRepo->findOneBy(['objectId' => $art0->getId()]);

        static::assertNotNull($log);
        static::assertEquals('create', $log->getAction());
        static::assertEquals(get_class($art0), $log->getObjectClass());
        static::assertEquals('jules', $log->getUsername());
        static::assertEquals(1, $log->getVersion());
        $data = $log->getData();
        static::assertCount(1, $data);
        static::assertArrayHasKey('title', $data);
        static::assertEquals('Title', $data['title']);

        // test update
        $article = $articleRepo->findOneBy(['title' => 'Title']);

        $article->setTitle('New');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $log = $logRepo->findOneBy(['version' => 2, 'objectId' => $article->getId()]);
        static::assertEquals('update', $log->getAction());

        // test delete
        $article = $articleRepo->findOneBy(['title' => 'New']);
        $this->em->remove($article);
        $this->em->flush();
        $this->em->clear();

        $log = $logRepo->findOneBy(['version' => 3, 'objectId' => 1]);
        static::assertEquals('remove', $log->getAction());
        static::assertNull($log->getData());
    }

    public function testVersionControl()
    {
        $this->populate();
        $commentLogRepo = $this->em->getRepository(self::COMMENT_LOG);
        $commentRepo = $this->em->getRepository(self::COMMENT);

        $comment = $commentRepo->find(1);
        static::assertEquals('m-v5', $comment->getMessage());
        static::assertEquals('s-v3', $comment->getSubject());
        static::assertEquals(2, $comment->getArticle()->getId());

        // test revert
        $commentLogRepo->revert($comment, 3);
        static::assertEquals('s-v3', $comment->getSubject());
        static::assertEquals('m-v2', $comment->getMessage());
        static::assertEquals(1, $comment->getArticle()->getId());
        $this->em->persist($comment);
        $this->em->flush();

        // test get log entries
        $logEntries = $commentLogRepo->getLogEntries($comment);
        static::assertCount(6, $logEntries);
        $latest = $logEntries[0];
        static::assertEquals('update', $latest->getAction());
    }

    public function testLogEmbedded()
    {
        $address = $this->populateEmbedded();

        $logRepo = $this->em->getRepository(LogEntry::class);

        $logEntries = $logRepo->getLogEntries($address);

        static::assertCount(4, $logEntries);
        static::assertCount(1, $logEntries[0]->getData());
        static::assertCount(2, $logEntries[1]->getData());
        static::assertCount(3, $logEntries[2]->getData());
        static::assertCount(5, $logEntries[3]->getData());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::COMMENT,
            self::COMMENT_LOG,
            self::RELATED_ARTICLE,
            LogEntry::class,
            Address::class,
            Geo::class,
        ];
    }

    private function populateEmbedded()
    {
        $address = new Address();
        $address->setCity('city-v1');
        $address->setStreet('street-v1');

        $geo = new Geo(1.0000, 1.0000, new GeoLocation('Online'));

        $address->setGeo($geo);

        $this->em->persist($address);
        $this->em->flush();

        $geo2 = new Geo(2.0000, 2.0000, new GeoLocation('Offline'));
        $address->setGeo($geo2);

        $this->em->persist($address);
        $this->em->flush();

        $address->getGeo()->setLatitude(3.0000);
        $address->getGeo()->setLongitude(3.0000);

        $this->em->persist($address);
        $this->em->flush();

        $address->setStreet('street-v2');

        $this->em->persist($address);
        $this->em->flush();

        return $address;
    }

    private function populate()
    {
        $article = new RelatedArticle();
        $article->setTitle('a1-t-v1');
        $article->setContent('a1-c-v1');

        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setMessage('m-v1');
        $comment->setSubject('s-v1');

        $this->em->persist($article);
        $this->em->persist($comment);
        $this->em->flush();

        $comment->setMessage('m-v2');
        $this->em->persist($comment);
        $this->em->flush();

        $comment->setSubject('s-v3');
        $this->em->persist($comment);
        $this->em->flush();

        $article2 = new RelatedArticle();
        $article2->setTitle('a2-t-v1');
        $article2->setContent('a2-c-v1');

        $comment->setArticle($article2);
        $this->em->persist($article2);
        $this->em->persist($comment);
        $this->em->flush();

        $comment->setMessage('m-v5');
        $this->em->persist($comment);
        $this->em->flush();
        $this->em->clear();
    }
}
