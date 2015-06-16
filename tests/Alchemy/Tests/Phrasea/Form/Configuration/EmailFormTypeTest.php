<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\EmailFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

/**
 * @group functional
 * @group legacy
 */
class EmailFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new EmailFormType();
    }
}
