<?php

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Query;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Issue138\Article;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Issue138Test extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;
    public const TRANSLATION = Translation::class;
    public const TREE_WALKER_TRANSLATION = TranslationWalker::class;

    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $this->translatableListener->setTranslationFallback(true);
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testIssue138()
    {
        $this->populate();
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $dql .= " WHERE a.title LIKE '%foo%'";
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        //die($q->getSQL());
        $result = $q->getArrayResult();
        static::assertCount(1, $result);
        static::assertEquals('Food', $result[0]['title']);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
        ];
    }

    private function populate()
    {
        $repo = $this->em->getRepository(self::ARTICLE);

        $food = new Article();
        $food->setTitle('Food');
        $food->setTitleTest('about food');

        $citron = new Article();
        $citron->setTitle('Citron');
        $citron->setTitleTest('something citron');

        $this->em->persist($food);
        $this->em->persist($citron);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $food->setTitle('Maistas');
        $food->setTitleTest('apie maista');

        $citron->setTitle('Citrina');
        $citron->setTitleTest('kazkas citrina');

        $this->em->persist($food);
        $this->em->persist($citron);
        $this->em->flush();
        $this->em->clear();
    }
}
