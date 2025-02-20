<?php

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Query;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Article;
use Gedmo\Tests\Translatable\Fixture\Comment;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\Hydrator\ORM\ObjectHydrator;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translation query walker
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Issue109Test extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;
    public const COMMENT = Comment::class;
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
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testIssue109()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            ObjectHydrator::class
        );
        $query = $this->em->createQueryBuilder();
        $query->select('a')
            ->from(self::ARTICLE, 'a')
            ->add('where', $query->expr()->not($query->expr()->eq('a.title', ':title')))
            ->setParameter('title', 'NA')
        ;

        $this->translatableListener->setTranslatableLocale('es');
        $this->translatableListener->setDefaultLocale('en');
        $this->translatableListener->setTranslationFallback(true);
        $query = $query->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $result = $query->getResult();
        static::assertCount(3, $result);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
            self::COMMENT,
        ];
    }

    public function populate()
    {
        $text0 = new Article();
        $text0->setTitle('text0');

        $this->em->persist($text0);

        $text1 = new Article();
        $text1->setTitle('text1');

        $this->em->persist($text1);

        $na = new Article();
        $na->setTitle('NA');

        $this->em->persist($na);

        $out = new Article();
        $out->setTitle('Out');

        $this->em->persist($out);
        $this->em->flush();
        $this->translatableListener->setTranslatableLocale('es');

        $text1->setTitle('texto1');
        $text0->setTitle('texto0');
        $this->em->persist($text1);
        $this->em->persist($text0);
        $this->em->flush();
    }
}
