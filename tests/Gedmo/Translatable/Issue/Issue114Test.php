<?php

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Issue114\Article;
use Gedmo\Tests\Translatable\Fixture\Issue114\Category;
use Gedmo\Translatable\Entity\Translation;
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
final class Issue114Test extends BaseTestCaseORM
{
    public const CATEGORY = Category::class;
    public const ARTICLE = Article::class;
    public const TRANSLATION = Translation::class;

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
    }

    public function testIssue114()
    {
        $repo = $this->em->getRepository(self::TRANSLATION);

        //Categories
        $category1 = new Category();
        $category1->setTitle('en category1');

        $category2 = new Category();
        $category2->setTitle('en category2');

        $this->em->persist($category1);
        $this->em->persist($category2);
        $this->em->flush();

        //Articles
        $article1 = new Article();
        $article1->setTitle('en article1');
        $article1->setCategory($category1);

        $article2 = new Article();
        $article2->setTitle('en article2');
        $article2->setCategory($category1);

        $article3 = new Article();
        $article3->setTitle('en article3');
        $article3->setCategory($category1);

        $this->em->persist($article1);
        $this->em->persist($article2);
        $this->em->persist($article3);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('es');

        // Setting es_ES title.
        $article1->setTitle('es article1');
        $article2->setTitle('es article2');
        $article3->setTitle('es article3');

        $this->em->persist($article1);
        $this->em->persist($article2);
        $this->em->persist($article3);

        $this->em->flush();

        // Updating articles' category
        $article1->setCategory($category2);
        $article2->setCategory($category2);
        $article3->setCategory($category2);

        $this->em->persist($article1);
        $this->em->persist($article2);
        $this->em->persist($article3);

        $this->em->flush();

        // Removing $category1
        $this->em->remove($category1);
        $this->em->flush();

        $trans = $repo->findTranslations($article2);
        static::assertCount(1, $trans);

        $trans = $repo->findTranslations($article3);
        static::assertCount(1, $trans);

        $trans = $repo->findTranslations($article1);
        static::assertCount(1, $trans);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
            self::ARTICLE,
            self::TRANSLATION,
        ];
    }
}
