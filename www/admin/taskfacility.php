<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					'cls'
					 );
					 
$conn = connection::getInstance();
if(!$conn)
{
	phrasea::headers(500);
}

$cls = 'task_' . $parm['cls'];

// $tskfile = $r . '/lib/classes/task/' . $parm['cls'] . '.class.php';
// require_once $tskfile;

$ztask = new $cls();

$ztask->facility();
?>