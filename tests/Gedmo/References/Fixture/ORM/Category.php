<?php

namespace Gedmo\Tests\References\Fixture\ORM;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
class Category
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=128)
     */
    private $name;

    /**
     * @Gedmo\ReferenceManyEmbed(class="Gedmo\Tests\References\Fixture\ODM\MongoDB\Product", identifier="metadatas.categoryId")
     */
    private $products;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getProducts()
    {
        return $this->products;
    }
}
