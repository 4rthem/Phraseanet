<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;
use Alchemy\Phrasea\Media\SubdefGenerator;
use Alchemy\Phrasea\Media\SubdefGeneratorMock;

class SubdefServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {
        $app['subdef.generator'] = $app->share(function (SilexApplication $app) {
            return new SubdefGenerator($app, $app['media-alchemyst'], $app['filesystem'], $app['mediavorus'], isset($app['task-manager.logger']) ? $app['task-manager.logger'] : $app['monolog']);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(SilexApplication $app)
    {
    }
}
