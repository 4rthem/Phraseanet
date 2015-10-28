<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Plugin\PluginManager;
use Alchemy\Phrasea\Plugin\Schema\ManifestValidator;
use Alchemy\Phrasea\Plugin\Schema\PluginValidator;
use ArrayObject;
use JsonSchema\Validator as JsonValidator;
use Pimple;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Twig_Environment;
use Twig_SimpleFunction;

class PluginServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['plugins.schema'] = realpath(__DIR__ . '/../../../../conf.d/plugin-schema.json');

        $app['plugins.json-validator'] = $app->share(function (Application $app) {
            return new JsonValidator();
        });

        $app['plugins.manifest-validator'] = $app->share(function (Application $app) {
            return ManifestValidator::create($app);
        });

        $app['plugins.plugins-validator'] = $app->share(function (Application $app) {
            return new PluginValidator($app['plugins.manifest-validator']);
        });

        $app['plugins.manager'] = $app->share(function (Application $app) {
            return new PluginManager($app['plugin.path'], $app['plugins.plugins-validator'], $app['conf']);
        });
        $app['plugin.workzone.basket.actionbar'] = $app->share(function () {
            return new Pimple();
        });
        $app['plugin.actionbar'] = $app->share(function () {
            return new Pimple();
        });
        $app['plugin.workzone'] = $app->share(function () {
            return new Pimple();
        });

        $app['plugin.locale.textdomains'] = new \ArrayObject();

        // Routes will be bound after all others
        // Add a new controller provider can be added as follows
        // $app['plugin.controller_providers'][] = array('/prefix', 'controller_provider_service_key');
        $app['plugin.controller_providers.root'] = new ArrayObject();

        // Routes will be bound after all others
        // Add a new controller provider can be added as follows
        // $app['plugin.controller_providers'][] = array('/prefix', 'controller_provider_service_key');
        $app['plugin.controller_providers.api'] = new \ArrayObject();
    }

    public function boot(Application $app)
    {
        foreach ($app['plugin.locale.textdomains'] as $textdomain => $dir) {
            bind_textdomain_codeset($textdomain, 'UTF-8');
            bindtextdomain($textdomain, $dir);
        }

        $app['twig'] = $app->share(
            $app->extend('twig', function (Twig_Environment $twig) {
                $function = new Twig_SimpleFunction('plugin_asset', array('Alchemy\Phrasea\Plugin\Management\AssetsManager', 'twigPluginAsset'));
                $twig->addFunction($function);

                return $twig;
            })
        );
    }
}
