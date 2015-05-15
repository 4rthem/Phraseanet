<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Symfony\Component\HttpFoundation\Request;

class MoveCollectionController extends Controller
{

    public function displayForm(Application $app, Request $request)
    {
        $records = RecordsRequest::fromRequest($app, $request, false, ['candeleterecord']);

        $sbas_ids = array_map(function (\databox $databox) {
            return $databox->get_sbas_id();
        }, $records->databoxes());

        $collections = $app['acl']->get($app['authentication']->getUser())
            ->get_granted_base(['canaddrecord'], $sbas_ids);

        $parameters = [
            'records'     => $records,
            'message'     => '',
            'collections' => $collections,
        ];

        return $app['twig']->render('prod/actions/collection_default.html.twig', $parameters);
    }

    public function apply(Application $app, Request $request)
    {
        $records = RecordsRequest::fromRequest($app, $request, false, ['candeleterecord']);

        $datas = [
            'success' => false,
            'message' => '',
        ];

        try {
            if (null === $request->request->get('base_id')) {
                $datas['message'] = $app->trans('Missing target collection');

                return $app->json($datas);
            }

            if (!$app['acl']->get($app['authentication']->getUser())->has_right_on_base($request->request->get('base_id'), 'canaddrecord')) {
                $datas['message'] = $app->trans("You do not have the permission to move records to %collection%", ['%collection%', \phrasea::bas_labels($request->request->get('base_id'), $app)]);

                return $app->json($datas);
            }

            try {
                $collection = \collection::get_from_base_id($app, $request->request->get('base_id'));
            } catch (\Exception_Databox_CollectionNotFound $e) {
                $datas['message'] = $app->trans('Invalid target collection');

                return $app->json($datas);
            }

            foreach ($records as $record) {
                $record->move_to_collection($collection, $app['phraseanet.appbox']);

                if ($request->request->get("chg_coll_son") == "1") {
                    foreach ($record->get_children() as $child) {
                        if ($app['acl']->get($app['authentication']->getUser())->has_right_on_base($child->get_base_id(), 'candeleterecord')) {
                            $child->move_to_collection($collection, $app['phraseanet.appbox']);
                        }
                    }
                }
            }

            $ret = [
                'success' => true,
                'message' => $app->trans('Records have been successfuly moved'),
            ];
        } catch (\Exception $e) {
            $ret = [
                'success' => false,
                'message' => $app->trans('An error occured'),
            ];
        }

        return $app->json($ret);
    }
}
