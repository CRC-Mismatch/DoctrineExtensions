<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Document\Handler\TreeSlug;
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
final class TreeSlugHandlerDocumentTest extends BaseTestCaseMongoODM
{
    public const SLUG = TreeSlug::class;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockDocumentManager($evm);
    }

    public function testSlugGeneration()
    {
        $this->populate();
        $repo = $this->dm->getRepository(self::SLUG);

        $food = $repo->findOneBy(['title' => 'Food']);
        static::assertEquals('food', $food->getSlug());

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        static::assertEquals('food/fruits', $fruits->getSlug());

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        static::assertEquals('food/fruits/oranges', $oranges->getSlug());

        $citrons = $repo->findOneBy(['title' => 'Citrons']);
        static::assertEquals('food/fruits/citrons', $citrons->getSlug());
    }

    public function testSlugUpdates()
    {
        $this->populate();
        $repo = $this->dm->getRepository(self::SLUG);

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        $fruits->setTitle('Fructis');

        $this->dm->persist($fruits);
        $this->dm->flush();

        static::assertEquals('food/fructis', $fruits->getSlug());

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        static::assertEquals('food/fructis/oranges', $oranges->getSlug());

        $citrons = $repo->findOneBy(['title' => 'Citrons']);
        static::assertEquals('food/fructis/citrons', $citrons->getSlug());

        $food = $repo->findOneBy(['title' => 'Food']);
        $food->setTitle('Foodissimo');

        $this->dm->persist($food);
        $this->dm->flush();

        static::assertEquals('foodissimo', $food->getSlug());
        static::assertEquals('foodissimo/fructis/oranges', $oranges->getSlug());
        static::assertEquals('foodissimo/fructis/citrons', $citrons->getSlug());
    }

    private function populate()
    {
        $food = new TreeSlug();
        $food->setTitle('Food');
        $this->dm->persist($food);

        $fruits = new TreeSlug();
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);
        $this->dm->persist($fruits);

        $vegitables = new TreeSlug();
        $vegitables->setTitle('Vegitables');
        $vegitables->setParent($food);
        $this->dm->persist($vegitables);

        $milk = new TreeSlug();
        $milk->setTitle('Milk');
        $milk->setParent($food);
        $this->dm->persist($milk);

        $meat = new TreeSlug();
        $meat->setTitle('Meat');
        $meat->setParent($food);
        $this->dm->persist($meat);

        $oranges = new TreeSlug();
        $oranges->setTitle('Oranges');
        $oranges->setParent($fruits);
        $this->dm->persist($oranges);

        $citrons = new TreeSlug();
        $citrons->setTitle('Citrons');
        $citrons->setParent($fruits);
        $this->dm->persist($citrons);

        $this->dm->flush();
    }
}
