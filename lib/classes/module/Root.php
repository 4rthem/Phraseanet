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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Silex\Application;

return call_user_func(function()
                {
                  $app = new Application();

                  if (!setup::is_installed())
                  {
                    return $app->redirect("/setup/")->send();
                  }

                  $app->get('/', function() use ($app)
                          {
                            $browser = Browser::getInstance();
                            if ($browser->isMobile())
                              return $app->redirect("/login/?redirect=/lightbox");
                            elseif ($browser->isNewGeneration())
                              return $app->redirect("/login/?redirect=/prod");
                            else
                              return $app->redirect("/login/?redirect=/client");
                          });

                  $app->get('robots.txt', function() use ($app)
                          {
                            require dirname(__FILE__) . "/../lib/bootstrap.php";
                            $appbox = appbox::get_instance();

                            $registry = $appbox->get_registry();

                            if ($registry->get('GV_allow_search_engine') === true)
                            {
                              $buffer = "User-Agent: *\n"
                                      . "Allow: /\n";
                            }
                            else
                            {
                              $buffer = "User-Agent: *\n"
                                      . "Disallow: /\n";
                            }

                            $response = new Response($buffer, 200, array('Content-Type: text/plain'));
                            $response->setCharset('UTF-8');

                            return $response;
                          });

                  $app->mount('/feeds/', new Controller_RSSFeeds());

                  /**
                   * Mount all aps
                   */
                  return $app;
                }
);