<?php

namespace Gedmo\Tests\Loggable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="articles")
 * @Gedmo\Loggable
 */
class Article
{
    /** @ODM\Id */
    private $id;

    /**
     * @Gedmo\Versioned
     * @ODM\Field(type="string")
     */
    private $title;

    /**
     * @ODM\EmbedOne(targetDocument="Gedmo\Tests\Loggable\Fixture\Document\Author")
     * @Gedmo\Versioned
     */
    private $author;

    public function __toString()
    {
        return $this->title;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getAuthor()
    {
        return $this->author;
    }
}
