<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Entities\Feed;
use Entities\FeedPublisher;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Publications implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAccessToModule('admin')
                ->requireRight('bas_chupub');
        });

        $controllers->get('/list/', function(PhraseaApplication $app) {

            $feeds = $app["EM"]->getRepository("Entities\Feed")->getAllForUser(
                $app['authentication']->getUser()
            );

            return $app['twig']
                    ->render('admin/publications/list.html.twig', array('feeds' => $feeds));
        })->bind('admin_feeds_list');

        $controllers->post('/create/', function(PhraseaApplication $app, Request $request) {

            $publisher = new FeedPublisher($app['authentication']->getUser(), true);

            $feed = new Feed($publisher, $request->request->get('title'), $request->request->get('subtitle'));

            if ($request->request->get('public') == '1') {
                $feed->setPublic(true);
            } elseif ($request->request->get('base_id')) {
                $feed->setCollection(\collection::get_from_base_id($app, $request->request->get('base_id')));
            }

            $publisher->setFeed($feed);

            $app["EM"]->persist($feed);
            $app["EM"]->persist($publisher);

            $app["EM"]->flush();

            return $app->redirectPath('admin_feeds_list');
        })->bind('admin_feeds_create');

        $controllers->get('/feed/{id}/', function(PhraseaApplication $app, Request $request, $id) {
            $feed = $app["EM"]->getRepository("Entities\Feed")->find($id);

            return $app['twig']
                    ->render('admin/publications/fiche.html.twig', array('feed'  => $feed, 'error' => $app['request']->query->get('error')));
        })
            ->bind('admin_feeds_feed')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/update/', function(PhraseaApplication $app, Request $request, $id) {

            $feed = $app["EM"]->getRepository("Entities\Feed")->find($id);

            try {
                $collection = \collection::get_from_base_id($app, $request->request->get('base_id'));
            } catch (\Exception $e) {
                $collection = null;
            }
            $feed->setTitle($request->request->get('title'));
            $feed->setDescription($request->request->get('subtitle'));
            $feed->setCollection($collection);
            $feed->setPublic($request->request->get('public'));

            $app["EM"]->persist($feed);
            $app["EM"]->flush();

            return $app->redirectPath('admin_feeds_list');
        })->before(function(Request $request) use ($app) {
             $feed = $app["EM"]->getRepository("Entities\Feed")->find($request->attributes->get('id'));

            if (!$feed->getOwner($app['authentication']->getUser())) {
                return $app->redirectPath('admin_feeds_feed', array('id' => $request->attributes->get('id'), 'error' =>  _('You are not the owner of this feed, you can not edit it')));
            }
        })
            ->bind('admin_feeds_feed_update')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/iconupload/', function(PhraseaApplication $app, Request $request, $id) {
            $datas = array(
                'success' => false,
                'message' => '',
            );
            $feed = $app["EM"]->getRepository("Entities\Feed")->find($id);

            $request = $app["request"];

            if (!$feed->getOwner($app['authentication']->getUser())) {
                $datas['message'] = 'You are not allowed to do that';

                return $app->json($datas);
            }

            try {
                if (!$request->files->get('files')) {
                    throw new BadRequestHttpException('Missing file parameter');
                }

                if (count($request->files->get('files')) > 1) {
                    throw new BadRequestHttpException('Upload is limited to 1 file per request');
                }

                $file = current($request->files->get('files'));

                if (!$file->isValid()) {
                    throw new BadRequestHttpException('Uploaded file is invalid');
                }

                $media = $app['mediavorus']->guess($file->getPathname());

                if ($media->getType() !== \MediaVorus\Media\MediaInterface::TYPE_IMAGE) {
                    throw new BadRequestHttpException('Bad filetype');
                }

                $spec = new \MediaAlchemyst\Specification\Image();

                $spec->setResizeMode(\MediaAlchemyst\Specification\Image::RESIZE_MODE_OUTBOUND);
                $spec->setDimensions(32, 32);
                $spec->setStrip(true);
                $spec->setQuality(72);

                $tmpname = tempnam(sys_get_temp_dir(), 'feed_icon').'.png';

                try {
                    $app['media-alchemyst']->turnInto($media->getFile()->getPathname(), $tmpname, $spec);
                } catch (\MediaAlchemyst\Exception\ExceptionInterface $e) {
                    throw new \Exception_InternalServerError('Error while resizing');
                }

                //$feed->set_icon($tmpname);

                unset($media);

                $app['filesystem']->remove($tmpname);

                $datas['success'] = true;
            } catch (\Exception $e) {
                $datas['message'] = _('Unable to add file to Phraseanet');
            }

            return $app->json($datas);
        })
            ->bind('admin_feeds_feed_icon')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/addpublisher/', function(PhraseaApplication $app, $id) {
            $error = '';
            try {
                $request = $app['request'];
                $user = \User_Adapter::getInstance($request->request->get('usr_id'), $app);
                $feed = $app["EM"]->getRepository("Entities\Feed")->find($id);

                $publisher = new FeedPublisher($user, false);
                $publisher->setFeed($feed);

                $feed->addPublisher($publisher);

                $app["EM"]->persist($feed);
                $app["EM"]->persist($publisher);

                $app["EM"]->flush();
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }

            return $app->redirectPath('admin_feeds_feed', array('id' => $id, 'error' => $error));
        })
            ->bind('admin_feeds_feed_add_publisher')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/removepublisher/', function(PhraseaApplication $app, $id) {
            try {
                $request = $app['request'];

                $feed = $app["EM"]->getRepository("Entities\Feed")->find($id);

                $publisher = $app["EM"]->getRepository("Entities\FeedPublisher")->find($request->request->get('publisher_id'));
                $user = $publisher->getUser($app);
                if ($feed->isPublisher($user) === true && $feed->isOwner($user) === false) {
                    $feed->removePublisher($publisher);

                    $app["EM"]->remove($publisher);
                    $app["EM"]->flush();
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }

            return $app->redirectPath('admin_feeds_feed', array('id' => $id, 'error' => $error));
        })
            ->bind('admin_feeds_feed_remove_publisher')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/delete/', function(PhraseaApplication $app, $id) {
            $feed = $app["EM"]->getRepository("Entities\Feed")->find($id);
            $publishers = $feed->getPublishers();
            foreach ($publishers as $publisher) {
                $app["EM"]->remove($publisher);
            }
            $app["EM"]->remove($feed);
            $app["EM"]->flush();

            return $app->redirectPath('admin_feeds_list');
        })
            ->bind('admin_feeds_feed_delete')
            ->assert('id', '\d+');

        return $controllers;
    }
}
