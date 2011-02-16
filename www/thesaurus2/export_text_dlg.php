<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0

phrasea::headers();
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "piv"
					, "id"
					, "typ"
					, "dlg"
				);

$lng = isset($session->locale)?$session->locale:GV_default_lng;
if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
}
else
{
	header("Location: /login/?error=auth&lng=".$lng);
	exit();
}

if($parm["dlg"])
{
	$opener = "window.dialogArguments.win";
}
else
{
	$opener = "opener";
}

?>
<html lang="<?php echo $session->usr_i18n;?>">
<head>
	<title><?php echo p4string::MakeString(_('thesaurus:: export au format texte'))?></title>
	
	<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />
	<script type="text/javascript">
		var format = "text";
		
		function clkBut(button)
		{
			switch(button)
			{
				case "submit":
					document.forms[0].action = "export_" + format + ".php";
					document.forms[0].submit();
					break;
				case "cancel":
					self.returnValue = null;
					self.close();
					break;
			}
		}
		function loaded()
		{
//			document.forms[0].t.focus();
			chgFormat();
		}
		function ckis()
		{
			document.getElementById("submit_button").disabled = document.forms[0].t.value=="";
		}
		function enable_inputs(o, stat)
		{
			if(o.nodeType==1)	// element
			{
				if(o.nodeName=='INPUT')
				{
					if(stat)
						o.removeAttribute('disabled');
					else
						o.setAttribute('disabled', true);
				}
				for(var oo=o.firstChild; oo; oo=oo.nextSibling)
					enable_inputs(oo, stat)
			}
		}
		function chgFormat()
		{
			var i, f;
			/*
			for(i=0; i<document.forms[0].ofm.length; i++)
			{
				f = document.forms[0].ofm[i].value;
				if(document.forms[0].ofm[i].checked)
				{
					enable_inputs(document.getElementById('subform_'+f), true);
					format = f;
				}
				else
				{
					enable_inputs(document.getElementById('subform_'+f), false);
				}
			}
			*/
			url = "./export_"+format+".php?bid=<?php echo $parm["bid"]?>&piv=<?php echo $parm["piv"]?>&id=<?php echo $parm["id"]?>&typ=<?php echo $parm["typ"]?>&dlg=0&smp=1";
			/*
			if(format == "text")
			{
			*/
				url += "&osl=" + (document.forms[0].osl[0].checked ? "1" : "0");
				url += "&iln=" + (document.forms[0].iln.checked ? "1" : "0");
				url += "&hit=" + (document.forms[0].hit.checked ? "1" : "0");
				url += "&ilg=" + (document.forms[0].ilg.checked ? "1" : "0");
			/*	
			}
			else
			{
				url += "&obr=<?php echo $parm['obr']?>";
			}
			*/
//			alert(url);
			document.getElementById("ifrsample").src = url;
		}
	</script>
</head>
<body onload="loaded();" class="dialog">
	<center>
	<br/>
	<form onsubmit="clkBut('submit');return(false);" action="export_topics.php" target="EXPORT2">
		<input type="hidden" name="bid" value="<?php echo $parm["bid"]?>" >
		<input type="hidden" name="piv" value="<?php echo $parm["piv"]?>" >
		<input type="hidden" name="id" value="<?php echo $parm["id"]?>" >
		<input type="hidden" name="typ" value="<?php echo $parm["typ"]?>" >
		<input type="hidden" name="dlg" value="<?php echo $parm["dlg"]?>" >
		<table>
			<thead>
				<tr>
					<th><?php echo p4string::MakeString(_('thesaurus:: options d\'export : ')) ?></th>
					<th><?php echo p4string::MakeString(_('thesaurus:: example')) ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td valign="top" style="padding:5px">
						<div style="white-space:nowrap">
							<input type='radio' name='ofm' checked value='text'> <!-- onclick="chgFormat();"> -->
							<?php echo p4string::MakeString(_('thesaurus:: export au format texte')) /* format texte */ ?>
						</div>
						<div id='subform_text' style="margin-left:10px;">
							<div style="white-space:nowrap">
								<input type='radio' name='osl' checked value='1' onclick="chgFormat();">
								<?php echo p4string::MakeString(_('thesaurus:: exporter avec les synonymes sur la meme ligne')) /* Les synonymes sur la m�me ligne */ ?>
							</div>
							<div style="white-space:nowrap">
								<input type='radio' name='osl' value='0' onclick="chgFormat();">
								<?php echo p4string::MakeString(_('thesaurus:: exporter avec une ligne par synonyme')) /* Une ligne par synonyme */ ?>
							</div>
							<div style="white-space:nowrap">
								<input type='checkbox' name='iln' value='1' onclick="chgFormat();">
								<?php echo p4string::MakeString(_('thesaurus:: export : numeroter les lignes ')) /* Num�roter les lignes */ ?>
							</div>
							<div style="white-space:nowrap">
								<input type='checkbox' name='ilg' value='1' onclick="chgFormat();">
								<?php echo p4string::MakeString(_('thesaurus:: export : inclure la langue')) /* Inclure la langue */ ?>
							</div>
							<div style="white-space:nowrap">
								<input type='checkbox' name='hit' value='1' onclick="chgFormat();">
								<?php echo p4string::MakeString(_('thesaurus:: export : inclure les hits')) /* Inclure les 'hits' */ ?>
							</div>
						</div>
						<!--	
						<div style="white-space:nowrap">
							<input type='radio' name='ofm' value='topics' onclick="chgFormat();">
							<?php echo p4string::MakeString(_('thesaurus:: export : format topics')) /* format topics */ ?>
						</div>
						<div id='subform_topics' style="margin-left:10px;">
						</div>
						-->
					</td>
					<td valign="top" style="padding:10px">
			 			<iframe id="ifrsample" frameborder="0" scrolling="No" style="width:400px;height:150px;overflow:hidden;border: 0px solid #b0b0b0; " ></iframe>
					</td>
				</tr>
			</tbody>
		</table>
		<br/>
		<br/>
		<input type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler'))?>" onclick="clkBut('cancel');" style="width:100px;">
		&nbsp;&nbsp;&nbsp;
		<input type="button" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider'))?>" onclick="clkBut('submit');" style="width:100px;">
	</form>
	</center>
</body>
</html>
