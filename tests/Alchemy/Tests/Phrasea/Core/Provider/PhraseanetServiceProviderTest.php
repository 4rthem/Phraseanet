<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 */
class PhraseanetServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'phraseanet.appbox',
                '\appbox'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'firewall',
                'Alchemy\Phrasea\Security\Firewall'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'events-manager',
                '\eventsmanager_broker'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'acl',
                'Alchemy\Phrasea\Authentication\ACLProvider'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'phraseanet.metadata-reader',
                'Alchemy\Phrasea\Metadata\PhraseanetMetadataReader'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'phraseanet.metadata-setter',
                'Alchemy\Phrasea\Metadata\PhraseanetMetadataSetter'
            ],
        ];
    }
}
