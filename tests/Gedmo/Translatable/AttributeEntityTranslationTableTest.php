<?php

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Attribute\File;
use Gedmo\Tests\Translatable\Fixture\Attribute\Person;
use Gedmo\Tests\Translatable\Fixture\Attribute\PersonTranslation;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @requires PHP >= 8.0
 */
final class AttributeEntityTranslationTableTest extends BaseTestCaseORM
{
    public const PERSON = Person::class;
    public const TRANSLATION = PersonTranslation::class;
    public const FILE = File::class;

    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testFixtureGeneratedTranslations(): void
    {
        $person = new Person();
        $person->setName('name in en');

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        static::assertTrue($repo instanceof TranslationRepository);

        $translations = $repo->findTranslations($person);
        // As Translate locale and Default locale are the same, no records should be present in translations table
        static::assertCount(0, $translations);

        // test second translations
        $person = $this->em->find(self::PERSON, $person->getId());
        $this->translatableListener->setTranslatableLocale('de_de');
        $person->setName('name in de');

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $translations = $repo->findTranslations($person);
        // Only one translation should be present
        static::assertCount(1, $translations);
        static::assertArrayHasKey('de_de', $translations);

        static::assertArrayHasKey('name', $translations['de_de']);
        static::assertEquals('name in de', $translations['de_de']['name']);

        $this->translatableListener->setTranslatableLocale('en_us');
    }

    public function testFixtureWithAttributeMappingAndAnnotations(): void
    {
        $file = new File();
        $file->setTitle('title in en');

        $this->em->persist($file);
        $this->em->flush();
        $this->em->clear();

        $file = $this->em->find(self::FILE, $file->getId());

        $file->locale = 'de';
        $file->setTitle('title in de');

        $this->em->flush();
        $this->em->clear();

        $file = $this->em->find(self::FILE, $file->getId());

        static::assertEquals('title in en', $file->getTitle());
        $file->locale = 'de';
        $this->em->refresh($file);
        static::assertEquals('title in de', $file->getTitle());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::PERSON,
            self::TRANSLATION,
            self::FILE,
        ];
    }
}
