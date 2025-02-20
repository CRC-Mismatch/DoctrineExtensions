<?php

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Category;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class InMemoryUpdatesTest extends BaseTestCaseORM
{
    public const CATEGORY = Category::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testInMemoryTreeInserts()
    {
        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $repo = $this->em->getRepository(self::CATEGORY);

        $root = new Category();
        $this->em->persist($root);
        $root->setTitle('Root');

        $child = new Category();
        $this->em->persist($child);
        $child->setTitle('child');

        $child2 = new Category();
        $this->em->persist($child2);
        $child2->setTitle('child2');

        $child2->setParent($root);
        $child->setParent($root);

        $this->em->flush();

        $childsChild = new Category();
        $this->em->persist($childsChild);
        $childsChild->setTitle('childs_child');
        $childsChild->setParent($child);

        $this->em->flush();
        $this->em->clear();

        $node = $repo->find(2);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);
        static::assertEquals(2, $left);
        static::assertEquals(5, $right);

        $node = $repo->find(3);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);
        static::assertEquals(6, $left);
        static::assertEquals(7, $right);

        $node = $repo->find(4);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);
        static::assertEquals(3, $left);
        static::assertEquals(4, $right);

        /*print "Tree:\n";
        for ($i=1; $i < 5; $i++) {
            $node = $this->em->getRepository(self::CATEGORY)->find($i);
            $left = $meta->getReflectionProperty('lft')->getValue($node);
            $right = $meta->getReflectionProperty('rgt')->getValue($node);
            $level = $meta->getReflectionProperty('level')->getValue($node);
            print $node->getTitle()." - $left - $right - $level\n";
        }
        print "\n\n";*/
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
        ];
    }
}
