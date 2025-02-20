<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Document\Article;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SluggableDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    public function testSlugGeneration()
    {
        // test insert
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneBy(['title' => 'My Title']);

        static::assertEquals('my-title-the-code', $article->getSlug());

        // test update
        $article->setTitle('New Title');

        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->findOneBy(['title' => 'New Title']);
        static::assertEquals('new-title-the-code', $article->getSlug());
    }

    public function testUniqueSlugGeneration()
    {
        for ($i = 0; $i < 12; ++$i) {
            $article = new Article();
            $article->setTitle('My Title');
            $article->setCode('The Code');

            $this->dm->persist($article);
            $this->dm->flush();
            $this->dm->clear();
            static::assertEquals('my-title-the-code-'.($i + 1), $article->getSlug());
        }
    }

    public function testGithubIssue57()
    {
        // slug matched by prefix
        $article = new Article();
        $article->setTitle('my');
        $article->setCode('slug');
        $this->dm->persist($article);

        $article2 = new Article();
        $article2->setTitle('my');
        $article2->setCode('s');
        $this->dm->persist($article2);

        $this->dm->flush();
        static::assertEquals('my-s', $article2->getSlug());
    }

    private function populate()
    {
        $art0 = new Article();
        $art0->setTitle('My Title');
        $art0->setCode('The Code');

        $this->dm->persist($art0);
        $this->dm->flush();
        $this->dm->clear();
    }
}
