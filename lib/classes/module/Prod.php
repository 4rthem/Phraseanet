<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use Alchemy\Phrasea\Controller\Prod as Controller;

return call_user_func(function()
                {
                  $twig = new \supertwig();

                  $app = new Application();
                  $app['Kernel'] = bootstrap::getKernel();

                  $app->mount('/baskets', new Controller\Basket());
                  $app->mount('/records/edit', new Controller\Edit());
                  $app->mount('/records/movecollection', new Controller\MoveCollection());
                  $app->mount('/bridge/', new Controller\Bridge());
                  $app->mount('/feeds', new Controller\Feed());
                  $app->mount('/tooltip', new Controller\Tooltip());

                  $app->error(function (\Exception $e, $code) use ($app, $twig)
                          {
                            if ($e instanceof \Bridge_Exception)
                            {
                              $request = $app['request'];

                              $params = array(
                                  'message' => $e->getMessage()
                                  , 'file' => $e->getFile()
                                  , 'line' => $e->getLine()
                                  , 'r_method' => $request->getMethod()
                                  , 'r_action' => $request->getRequestUri()
                                  , 'r_parameters' => ($request->getMethod() == 'GET' ? array() : $request->request->all())
                              );

                              if ($e instanceof \Bridge_Exception_ApiConnectorNotConfigured)
                              {
                                $params = array_merge($params, array('account' => $app['current_account']));

                                return new response($twig->render('/prod/actions/Bridge/notconfigured.twig', $params), 200);
                              }
                              elseif ($e instanceof \Bridge_Exception_ApiConnectorNotConnected)
                              {
                                $params = array_merge($params, array('account' => $app['current_account']));

                                return new response($twig->render('/prod/actions/Bridge/disconnected.twig', $params), 200);
                              }
                              elseif ($e instanceof \Bridge_Exception_ApiConnectorAccessTokenFailed)
                              {
                                $params = array_merge($params, array('account' => $app['current_account']));

                                return new response($twig->render('/prod/actions/Bridge/disconnected.twig', $params), 200);
                              }
                              elseif ($e instanceof Bridge_Exception_ApiDisabled)
                              {
                                $params = array_merge($params, array('api' => $e->get_api()));

                                return new response($twig->render('/prod/actions/Bridge/deactivated.twig', $params), 200);
                              }
                              return new response($twig->render('/prod/actions/Bridge/error.twig', $params), 200);
                            }
                          });


                  return $app;
                });