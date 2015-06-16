<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 */
class ManipulatorServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.task',
                'Alchemy\Phrasea\Model\Manipulator\TaskManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.acl',
                'Alchemy\Phrasea\Model\Manipulator\ACLManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.registration',
                'Alchemy\Phrasea\Model\Manipulator\RegistrationManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.token',
                'Alchemy\Phrasea\Model\Manipulator\TokenManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.api-application',
                'Alchemy\Phrasea\Model\Manipulator\ApiApplicationManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.api-account',
                'Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.api-log',
                'Alchemy\Phrasea\Model\Manipulator\ApiLogManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.api-oauth-token',
                'Alchemy\Phrasea\Model\Manipulator\ApiOauthTokenManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.api-oauth-code',
                'Alchemy\Phrasea\Model\Manipulator\ApiOauthCodeManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.api-oauth-refresh-token',
                'Alchemy\Phrasea\Model\Manipulator\ApiOauthRefreshTokenManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.webhook-event',
                'Alchemy\Phrasea\Model\Manipulator\WebhookEventManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.webhook-delivery',
                'Alchemy\Phrasea\Model\Manipulator\WebhookEventDeliveryManipulator'
            ],
        ];
    }
}
