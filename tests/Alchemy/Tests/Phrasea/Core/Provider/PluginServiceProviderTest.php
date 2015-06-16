<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Core\Provider\PluginServiceProvider
 */
class PluginServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.json-validator',
                'JsonSchema\Validator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.manager',
                'Alchemy\Phrasea\Plugin\PluginManager'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.plugins-validator',
                'Alchemy\Phrasea\Plugin\Schema\PluginValidator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.manifest-validator',
                'Alchemy\Phrasea\Plugin\Schema\ManifestValidator'
            ],
        ];
    }
}
