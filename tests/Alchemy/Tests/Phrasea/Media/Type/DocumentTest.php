<?php

namespace Alchemy\Tests\Phrasea\Media\Type;

use Alchemy\Phrasea\Media\Type\Document;
use Alchemy\Phrasea\Media\Type\Type;

/**
 * @group functional
 * @group legacy
 */
class DocumentTest extends \PhraseanetTestCase
{

    /**
     * @covers Alchemy\Phrasea\Media\Type\Document::getType
     */
    public function testGetType()
    {
        $object = new Document();
        $this->assertEquals(Type::TYPE_DOCUMENT, $object->getType());
    }
}
