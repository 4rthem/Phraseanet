<?php

namespace Alchemy\Tests\Phrasea\Media\Type;

use Alchemy\Phrasea\Media\Type\Image;
use Alchemy\Phrasea\Media\Type\Type;

/**
 * @group functional
 * @group legacy
 */
class ImageTest extends \PhraseanetTestCase
{

    /**
     * @covers Alchemy\Phrasea\Media\Type\Image::getType
     */
    public function testGetType()
    {
        $object = new Image();
        $this->assertEquals(Type::TYPE_IMAGE, $object->getType());
    }
}
