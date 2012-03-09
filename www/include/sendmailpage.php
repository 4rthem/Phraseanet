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
require_once dirname(__FILE__) . '/../../lib/bootstrap.php';
$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();

ob_start(null, 0);

$request = http_request::getInstance();
$parm = $request->get_parms("lst", "obj", "destmail", "subjectmail", "reading_confirm", "textmail", "ssttid", "type");


$gatekeeper = gatekeeper::getInstance();
$gatekeeper->require_session();

phrasea::headers();

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

$from = array('name' => $user->get_display_name(), 'email' => $user->get_email());

if ($parm["type"] == "title")
  $titre = true;
else
  $titre=false;

$exportname = "Export_" . date("Y-n-d") . '_' . mt_rand(100, 999);

if ($parm["ssttid"] != "")
{
  $basket = basket_adapter::getInstance($appbox, $parm['ssttid'], $session->get_usr_id());
  $exportname = str_replace(' ', '_', $basket->get_name()) . "_" . date("Y-n-d");
}

$download = new set_export($parm['lst'], $parm['ssttid']);

$list = $download->prepare_export($parm['obj'], $titre);

$list['export_name'] = $exportname . '.zip';
$list['email'] = $parm["destmail"];

$endate_obj = new DateTime('+1 day');
$endDate = $endate_obj;

$token = random::getUrlToken(\random::TYPE_EMAIL, false, $endDate, serialize($list));

$emails = explode(',', $parm["destmail"]);

$dest = array();

foreach ($emails as $email)
  $dest = array_merge($dest, explode(';', $email));

$res = $dest;
if ($token)
{
  $url = $registry->get('GV_ServerName') . 'mail-export/' . $token . '/';



  $reading_confirm_to = false;
  if ($parm['reading_confirm'] == '1')
  {
    $reading_confirm_to = $user->get_email();
  }

  foreach ($dest as $key => $email)
  {
    if (($result = mail::send_documents(trim($email), $url, $from, $endate_obj, $parm["textmail"], $reading_confirm_to)) === true)
      unset($res[$key]);
  }
}

if (count($res) == 0)
  echo "<script type='text/javascript'>parent.$('#sendmail .close_button:first').trigger('click');</script>";
else
{
  echo "<script type='text/javascript'>alert('" . str_replace("'", "\'", sprintf(_('export::mail: erreur lors de l\'envoi aux adresses emails %s'), implode(', ', $res))) . "');</script>";
}

echo ob_get_clean();
ob_flush();
flush();

set_time_limit(0);
session_write_close();
ignore_user_abort(true);

$zipFile = $registry->get('GV_RootPath') . 'tmp/download/' . $token . '.zip';

set_export::build_zip($token, $list, $zipFile);



