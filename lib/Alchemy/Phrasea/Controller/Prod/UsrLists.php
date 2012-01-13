<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application,
    Silex\ControllerProviderInterface,
    Silex\ControllerCollection;
use Alchemy\Phrasea\Helper\Record as RecordHelper,
    Alchemy\Phrasea\Out\Module\PDF as PDFExport,
    Alchemy\Phrasea\Controller\Exception as ControllerException;
use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class UsrLists implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    /**
     * Get all lists
     */
    $controllers->get('/list/all/', function(Application $app)
            {
              $em = $app['Core']->getEntityManager();

              $repository = $em->getRepository('\Entities\UsrList');

              $lists = $repository->findUserLists($app['Core']->getAuthenticatedUser());

              $datas = array('lists' => array());

              foreach ($lists as $list)
              {
                $owners = $entries = array();

                foreach ($list->getOwners() as $owner)
                {
                  $owners[] = array(
                      'usr_id' => $owner->getUser()->get_id(),
                      'display_name' => $owner->getUser()->get_display_name(),
                      'position' => $owner->getUser()->get_position(),
                      'job' => $owner->getUser()->get_job(),
                      'company' => $owner->getUser()->get_company(),
                      'email' => $owner->getUser()->get_email(),
                      'role' => $owner->getRole()
                  );
                }

                foreach ($list->getEntries() as $entry)
                {
                  $entries[] = array(
                      'usr_id' => $owner->getUser()->get_id(),
                      'display_name' => $owner->getUser()->get_display_name(),
                      'position' => $owner->getUser()->get_position(),
                      'job' => $owner->getUser()->get_job(),
                      'company' => $owner->getUser()->get_company(),
                      'email' => $owner->getUser()->get_email(),
                  );
                }


                /* @var $list \Entities\UsrList */
                $datas['lists'][] = array(
                    'name' => $list->getName(),
                    'created' => $list->getCreated()->format(DATE_ATOM),
                    'updated' => $list->getUpdated()->format(DATE_ATOM),
                    'owners' => $owners,
                    'users' => $entries
                );
              }

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );

    /**
     * Creates a list
     */
    $controllers->post('/list/', function(Application $app)
            {
              $request = $app['request'];
              
              $list_name = $request->get('name');
                
              $datas = array(
                  'success' => false
                  , 'message' => sprintf(_('Unable to create list %s'), $list_name)
              );

              try
              {
                if(!$list_name)
                {
                  throw new ControllerException(_('List name is required'));
                }
                
                $em = $app['Core']->getEntityManager();

                $List = new \Entities\UsrList();

                $Owner = new \Entities\UsrListOwner();
                $Owner->setRole(\Entities\UsrListOwner::ROLE_ADMIN);
                $Owner->setUser($app['Core']->getAuthenticatedUser());
                $Owner->setList($List);

                $List->setName($list_name);
                $List->addUsrListOwner($Owner);

                $em->persist($Owner);
                $em->persist($List);
                $em->flush();

                $datas = array(
                    'success' => true
                    , 'message' => sprintf(_('List %s has been created'), $list_name)
                );
              }
              catch (ControllerException $e)
              {
                $datas = array(
                    'success' => false
                    , 'message' => $e->getMessage()
                );
              }

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );

    /**
     * Gets a list
     */
    $controllers->get('/list/{list_id}/', function(Application $app, $list_id)
            {
              $user = $app['Core']->getAuthenticatedUser();
              $em = $app['Core']->getEntityManager();

              $repository = $em->getRepository('\Entities\UsrList');

              $list = $repository->findUserListByUserAndId($user, $list_id);
              
              $owners = $entries = $lists = array();

              foreach ($list->getOwners() as $owner)
              {
                $owners[] = array(
                    'usr_id' => $owner->getUser()->get_id(),
                    'display_name' => $owner->getUser()->get_display_name(),
                    'position' => $owner->getUser()->get_position(),
                    'job' => $owner->getUser()->get_job(),
                    'company' => $owner->getUser()->get_company(),
                    'email' => $owner->getUser()->get_email(),
                    'role' => $owner->getRole()
                );
              }

              foreach ($list->getEntries() as $entry)
              {
                $entries[] = array(
                    'usr_id' => $owner->getUser()->get_id(),
                    'display_name' => $owner->getUser()->get_display_name(),
                    'position' => $owner->getUser()->get_position(),
                    'job' => $owner->getUser()->get_job(),
                    'company' => $owner->getUser()->get_company(),
                    'email' => $owner->getUser()->get_email(),
                );
              }


              /* @var $list \Entities\UsrList */
              $datas = array('list' => array(
                      'name' => $list->getName(),
                      'created' => $list->getCreated()->format(DATE_ATOM),
                      'updated' => $list->getUpdated()->format(DATE_ATOM),
                      'owners' => $owners,
                      'users' => $entries
                  )
              );

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );

    /**
     * Update a list
     */
    $controllers->post('/list/{list_id}/update/', function(Application $app, $list_id)
            {
              $request = $app['request'];

              $datas = array(
                  'success' => false
                  , 'message' => _('Unable to update list')
              );
                
              try
              {
                $list_name = $request->get('name');
              
                if(!$list_name)
                {
                  throw new ControllerException(_('List name is required'));
                }
                
                $user = $app['Core']->getAuthenticatedUser();
                $em = $app['Core']->getEntityManager();

                $repository = $em->getRepository('\Entities\UsrList');

                $list = $repository->findUserListByUserAndId($user, $list_id);

                $list->setName($list_name);

                $em->merge($list);
                $em->flush();

                $datas = array(
                    'success' => true
                    , 'message' => _('List has been updated')
                );
              }
              catch (ControllerException $e)
              {
                $datas = array(
                    'success' => false
                    , 'message' => $e->getMessage()
                );
              }

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );

    /**
     * Delete a list
     */
    $controllers->post('/list/{list_id}/delete/', function(Application $app, $list_id)
            {
              $em = $app['Core']->getEntityManager();

              try
              {
                $repository = $em->getRepository('\Entities\UsrList');
                
                $user = $app['Core']->getAuthenticatedUser();
                
                $list = $repository->findUserListByUserAndId($user, $list_id);

                $em->remove($list);
                $em->flush();

                $datas = array(
                    'success' => true
                    , 'message' => sprintf(_('List has been deleted'))
                );
              }
              catch (\Exception $e)
              {

                $datas = array(
                    'success' => false
                    , 'message' => sprintf(_('Unable to delete list'))
                );
              }

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );


    /**
     * Remove a usr_id from a list
     */
    $controllers->post('/list/{list_id}/remove/{entry_id}/', function(Application $app, $list_id, $entry_id)
            {
              $em = $app['Core']->getEntityManager();

              try
              {
                $repository = $em->getRepository('\Entities\UsrList');
                
                $user = $app['Core']->getAuthenticatedUser();

                $list = $repository->findUserListByUserAndId($user, $list_id);
                /* @var $list \Entities\UsrList */
                
                $entry_repository = $em->getRepository('\Entities\UsrListEntry');
                
                $user_entry = $entry_repository->findEntryByListAndEntryId($list, $entry_id);

                $em->remove($user_entry);
                $em->flush();
                
                $datas = array(
                    'success' => true
                    , 'message' => _('Entry removed from list')
                );
              }
              catch (\Exception $e)
              {

                $datas = array(
                    'success' => false
                    , 'message' => _('Unable to remove entry from list')
                );
              }

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );

    /**
     * Adds a usr_id to a list
     */
    $controllers->post('/list/{list_id}/add/{usr_id}/', function(Application $app, $list_id, $usr_id)
            {
              $em = $app['Core']->getEntityManager();

              try
              {
                $repository = $em->getRepository('\Entities\UsrList');

                $list = $repository->findUserListByUserAndId($user, $list_id);
                /* @var $list \Entities\UsrList */
                $user_entry = \User_Adapter::getInstance($usr_id, appbox::get_instance());

                $entry = new \Entities\UsrListEntry();
                $entry->setUser($user_entry);
                $entry->setList($list);

                $list->addUsrListEntry($entry);

                $em->persist($entry);
                $em->merge($list);

                $em->flush();

                $datas = array(
                    'success' => true
                    , 'message' => _('Usr added to list')
                );
              }
              catch (\Exception $e)
              {

                $datas = array(
                    'success' => false
                    , 'message' => _('Unable to add usr to list')
                );
              }

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );

    /**
     * Share a list to a user with an optionnal role
     */
    $controllers->post('/list/{list_id}/share/{usr_id}/', function(Application $app, $list_id, $usr_id)
            {
              $em = $app['Core']->getEntityManager();
              $user = $app['Core']->getAuthenticatedUser();

              try
              {
                $repository = $em->getRepository('\Entities\UsrList');

                $list = $repository->findUserListByUserAndId($user, $list_id);
                /* @var $list \Entities\UsrList */
                
                if($list->getOwner($user)->getList() < \Entities\UsrListOwner::ROLE_EDITOR)
                {
                  throw new \Exception('You are not authorized to do this');
                }
                
                $new_owner = \User_Adapter::getInstance($usr_id, appbox::get_instance());
                
                if($list->hasAccess($new_owner))
                {
                  $owner = $list->getOwner($new_owner);
                }
                else
                {
                  $owner = new \Entities\UsrListOwner();
                  $owner->setList($list);
                  $owner->setUser($new_owner);
                  
                  $list->addUsrListOwner($owner);
                  
                  $em->persist($owner);
                  $em->merge($list);
                }
                
                $role = $app['request']->get('role', \Entities\UsrListOwner::ROLE_USER);
                
                $owner->setRole($role);
                
                $em->merge($owner);
                $em->flush();
                
                $datas = array(
                    'success' => true
                    , 'message' => _('List shared to user')
                );
              }
              catch (\Exception $e)
              {

                $datas = array(
                    'success' => false
                    , 'message' => _('Unable to share the list with the usr')
                );
              }
              
              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );
    /**
     * UnShare a list to a user 
     */
    $controllers->post('/list/{list_id}/unshare/{owner_id}/', function(Application $app, $list_id, $owner_id)
            {
              $em = $app['Core']->getEntityManager();
              $user = $app['Core']->getAuthenticatedUser();

              try
              {
                $repository = $em->getRepository('\Entities\UsrList');

                $list = $repository->findUserListByUserAndId($user, $list_id);
                /* @var $list \Entities\UsrList */
                
                if($list->getOwner($user)->getList() < \Entities\UsrListOwner::ROLE_ADMIN)
                {
                  throw new \Exception('You are not authorized to do this');
                }
                
                $owners_repository = $em->getRepository('\Entities\UsrListOwner');
                
                $owner = $owners_repository->findByListAndOwner($list, $owner_id);
                
                $em->remove($owner);
                $em->flush();
                
                $datas = array(
                    'success' => true
                    , 'message' => _('Owner removed from list')
                );
              }
              catch (\Exception $e)
              {

                $datas = array(
                    'success' => false
                    , 'message' => _('Unable to add usr to list')
                );
              }
              
              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );


    return $controllers;
  }

}
