<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture\Story;

use Alchemy\Phrasea\Model\Entities\StoryWZ;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadOneStory extends \PhraseaFixture\AbstractWZ implements FixtureInterface
{
    /**
     *
     * @var StoryWZ
     */
    public $story;

    public function load(ObjectManager $manager)
    {
        $story = new StoryWZ();

        if (null === $this->record) {
            throw new \LogicException('Fill a record to store a new story');
        }

        if (null === $this->user) {
            throw new \LogicException('Fill a user to store a new story');
        }

        $story->setRecord($this->record);
        $story->setUser($this->user);

        $manager->persist($story);
        $manager->flush();

        $this->story = $story;

        $this->addReference('one-story', $story);
    }
}
