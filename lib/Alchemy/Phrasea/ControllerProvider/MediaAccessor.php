<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\ControllerProvider;

use Alchemy\Phrasea\Controller\MediaAccessorController;
use Alchemy\Phrasea\Model\Entities\Secret;
use Alchemy\Phrasea\Model\Provider\DefaultSecretProvider;
use Doctrine\ORM\EntityManagerInterface;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class MediaAccessor implements ServiceProviderInterface, ControllerProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['repo.secrets'] = $app->share(function (Application $app) {
            /** @var EntityManagerInterface $manager */
            $manager = $app['orm.em'];
            return $manager->getRepository(Secret::class);
        });

        $app['provider.secrets'] = $app->share(function (Application $app) {
            return new DefaultSecretProvider($app['repo.secrets'], $app['random.medium']);
        });

        $app['controller.media_accessor'] = $app->share(function (Application $app) {
            return (new MediaAccessorController($app))
                ->setAllowedAlgorithms(['HS256'])
                ->setKeyStorage($app['repo.secrets']);
        });

        $app['controller.media_accessor.route_prefix'] = '/medias';
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        $controllers = $this->createCollection($app);
        $controllers->get('/{token}', 'controller.media_accessor:showAction')
            ->bind('media_accessor');

        return $controllers;
    }
}
