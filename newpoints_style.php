<?php

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("newpoints_start", "newpoints_style_page");
$plugins->add_hook("newpoints_default_menu", "newpoints_style_menu");
$plugins->add_hook("forumdisplay_start", "newpoints_style_expire");
$plugins->add_hook("forumdisplay_thread", "newpoints_style_affect");


function newpoints_style_info()
{
	return array(
		"name"			=> "Style your thread title",
		"description"	=> "Pay with credits to style your thread's title.",
		"website"		=> "https://developement.design/",
		"author"		=> "AmazOuz, D&D Team",
		"authorsite"	=> "https://developement.design/",
		"version"		=> "1.0",
		"guid" 			=> "",
		"compatibility" => "*"
	);
}


function newpoints_style_install()
{
	global $db;
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD `styled_threads` TEXT NOT NULL;");
	
	newpoints_add_setting('newpoints_style_price_without', 'newpoints_style', 'Price (Normal)', 'Price to pay for styling a thread title.', 'text', '10', 1);
    newpoints_add_setting('newpoints_style_price_with', 'newpoints_style', 'Price (Special)', 'Price to pay for styling a thread title with special items.', 'text', '20', 2);
	newpoints_add_setting('newpoints_style_duration', 'newpoints_style', 'Duration', 'How many days will remain the thread as highlighted.', 'text', '7', 3);
    newpoints_add_setting('newpoints_style_types_without', 'newpoints_style', 'Normal Types', 'Style types separated by comma', 'textarea', '<strong style="color:red;">{$title}</strong>, <strong style="color:#fabc00;">{$title}</strong>, <strong style="color:#0F5579;">{$title}</strong>', 4);
    newpoints_add_setting('newpoints_style_types_with', 'newpoints_style', 'Special Types', 'Style types (with special items) separated by comma', 'textarea', '<strong style="color:red;">{$title}</strong> <img style="float: right;" src="https://i.imgur.com/ngoAuYN.gif" />, <strong style="color:red;">{$title}</strong> <img style="float: right;" src="https://i.imgur.com/3igJDJh.gif" />', 5);
	
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."newpoints_style` (
	  `id` bigint(30) UNSIGNED NOT NULL auto_increment,
	  `expiry` DATETIME NOT NULL,
	  `tid` int(10) NOT NULL default '0',
      `type` int(10) NOT NULL default '0',
      `special` int(10) NOT NULL default '0',
	  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM");
	
	rebuild_settings();
}


function newpoints_style_is_installed()
{
	global $db;
	if($db->field_exists('styled_threads', 'users'))
	{
		return true;
	}
	return false;
}


function newpoints_style_uninstall()
{
	global $db;
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP `styled_threads`;");
	
	newpoints_remove_settings("'newpoints_style_price_with', 'newpoints_style_price_without','newpoints_style_duration','newpoints_style_types_with','newpoints_style_types_without'");
	rebuild_settings();
	
	if($db->table_exists('newpoints_style'))
	{
		$db->drop_table('newpoints_style');
	}
	
}


function newpoints_style_activate()
{
	global $db, $mybb;
	
	newpoints_add_template('newpoints_style', '
<html>
<head>
<title>{$lang->newpoints} - Style my thread title</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td valign="top" width="180">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>{$lang->newpoints_menu}</strong></td>
</tr>
{$options}
</table>
</td>
<td valign="top">
<form action="newpoints.php?action=style&style_action=buy" method="POST">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>Style my thread title</strong></td>
</tr>
<tr>
	<td class="trow1" colspan="{$colspan}">Pick your thread and get better presence in the forums. The duration of the styled thread is {$duration} days, the price is therefore {$price_without} Credits for normal styles and {$price_with} for special ones.<br><br>{$inline_errors}<strong>Select the thread you want to style:</strong><br><br>
		<select name="thread">
			{$own_threads}
		</select>
        <input type="hidden" name="special" value = "0">
		<input class="button" type="submit" name="submit" value="Buy">
        <br><br>Normal Styles :
        {$style_types_without}
        <br><br>Special Styles :
        {$style_types_with}
	</td>
</tr>
</table>
</form><br>
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>My styled threads</strong></td>
</tr>
<tr>
<td class="tcat" width="70%"><strong>Thread</strong></td>
<td class="tcat" width="30%"><strong>Expiry date</strong></td>
</tr>
{$styled_threads}
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>');
	
	newpoints_add_template('newpoints_style_thread', '<tr>
        <td class="trow1">{$styled_thread}</td>
        <td class="trow1">{$expiry_date}</td>
    </tr>');
	
	
	
	newpoints_add_template('newpoints_style_empty', '
<tr>
<td class="trow1" colspan="2">You currently have no styled thread title.</td>
</tr>');
    
    require MYBB_ROOT.'/inc/adminfunctions_templates.php';
    find_replace_templatesets(
        "forumdisplay_thread",
        "#" . preg_quote('{$thread[\'subject\']}') . "#i",
        '{$thread[\'styled\']}'
    );
	
}


function newpoints_style_deactivate()
{
	global $db, $mybb;
	
	newpoints_remove_templates("'newpoints_style','newpoints_style_thread','newpoints_style_empty'");
    
    require MYBB_ROOT.'/inc/adminfunctions_templates.php';
    find_replace_templatesets(
        "forumdisplay_thread",
        "#" . preg_quote('{$thread[\'styled\']}') . "#i",
        '{$thread[\'subject\']}'
    );
	
}


function newpoints_style_menu(&$menu)
{
	global $mybb;
	
	if ($mybb->input['action'] == 'style')
		$menu[] = "&raquo; <a href=\"{$mybb->settings['bburl']}/newpoints.php?action=style\">Highlight thread</a>";
	else
		$menu[] = "<a href=\"{$mybb->settings['bburl']}/newpoints.php?action=style\">Highlight thread</a>";
}


function newpoints_style_page()
{
	global $mybb, $db, $lang, $cache, $theme, $header, $templates, $plugins, $headerinclude, $footer, $options, $inline_errors;
	
	if (!$mybb->user['uid'])
		return;
		
	if ($mybb->input['action'] == "style")
	{	
		if (!empty($mybb->input['style_action']) and $mybb->input['style_action'] == 'buy')
        {
            if (isset($mybb->input['thread']) and isset($mybb->input['type']))
            {
                $query = $db->simple_select('threads', '*', 'uid=\''.intval($mybb->user['uid']).'\' and tid=\''.intval($mybb->input['thread']).'\'');
                while($t = $db->fetch_array($query))
                {
                    $own_thread = true;
                    $title = $t['subject'];
                    $tid = $t['tid'];
                }
                $query2 = $db->simple_select('newpoints_style', '*', 'tid=\''.intval($mybb->input['thread']).'\'');
                while($s = $db->fetch_array($query2))
                {
                    $already = true;
                }
                if ($own_thread == true & $already == false)
                {
                    
                    $special = intval($mybb->input['special']);
                    if ($special == 0)
                    {
                        $price = $mybb->settings['newpoints_style_price_without'];
                    }
                    elseif ($special == 1)
                    {
                        $price = $mybb->settings['newpoints_style_price_with'];
                    }
                    if ($mybb->user['newpoints'] >= $price)
                    {
                        $type = intval($mybb->input['type']);
                        if ($special == 0)
                        {
                            $mybb->user['newpoints'] = $mybb->user['newpoints'] - $mybb->settings['newpoints_style_price_without'];
                        }
                        elseif ($special == 1)
                        {
                            $mybb->user['newpoints'] = $mybb->user['newpoints'] - $mybb->settings['newpoints_style_price_with'];
                        }
                        $styled = unserialize($mybb->user['styled_threads']);
                        if (!is_array($styled))
                        {
                            $styled = array($tid);
                        }
                        else 
                        {
                            array_push($styled, $tid);
                            var_dump($styled);
                        }
                        $db->write_query("INSERT INTO ".TABLE_PREFIX."newpoints_style(type, tid, special, expiry) VALUES(".$type.", '".$tid."', '".$special."', NOW() + INTERVAL ".$mybb->settings['newpoints_style_duration']." DAY)");
                        $db->write_query("UPDATE ".TABLE_PREFIX."users SET newpoints = ".$mybb->user['newpoints']." WHERE uid = ".$mybb->user['uid']);
                        $db->write_query("UPDATE ".TABLE_PREFIX."users SET styled_threads = '".serialize($styled)."' WHERE uid = ".$mybb->user['uid']);
                        header('Location:newpoints.php?action=style&result=success');
                    }
                    else
                    {
                        header('Location:newpoints.php?action=style&result=credits');
                    }
                }
                else
                {
                    header('Location:newpoints.php?action=style&result=error');
                }
            }
            else
            {
                header('Location:newpoints.php?action=style&result=error');
            }
            
        }
        else
        {
            if (!empty($mybb->input['result']) and $mybb->input['result'] == 'credits')
            {
                $inline_errors = "<strong style='color:red;'>Oops! You do not have enough credits.</strong><br><br>";
            }
            if (!empty($mybb->input['result']) and $mybb->input['result'] == 'error')
            {
                $inline_errors = "<strong style='color:red;'>Ow! The thread is already highlighted, if you feel it's an error then please contact the support.</strong><br><br>";
            }
            elseif (!empty($mybb->input['result']) and $mybb->input['result'] == 'success')
            {
                $inline_errors = "<strong style='color:#2ecc71;'>Yay! Your thread was successfully highlighted.</strong><br><br>";;
            }
            
            $own_threads = "";
            $query = $db->simple_select('threads', '*', 'uid=\''.intval($mybb->user['uid']).'\'');
            while($t = $db->fetch_array($query))
            {
                $own_threads .= "<option value='".$t['tid']."'>".htmlspecialchars($t['subject'])."</option>";
            }
            $price_without = $mybb->settings['newpoints_style_price_without'];
            $price_with = $mybb->settings['newpoints_style_price_with'];
            $duration = $mybb->settings['newpoints_style_duration'];
            $types_without = explode(",", $mybb->settings['newpoints_style_types_without']);
            $style_types_without = "";
            foreach($types_without as $key => $type)
            {
                if (!empty($type))
                {
                    $type = str_replace('{$title}', "This is a thread title", $type);
                    $style_types_without .= '<br><input style="margin:10px" onclick="document.getElementsByName(\'special\')[0].value = 0;" type="radio" name="type" value="'.$key.'">'.$type;
                }
            }
            $types_with = explode(",", $mybb->settings['newpoints_style_types_with']);
            $style_types_with = "";
            foreach($types_with as $key => $type)
            {
                if (!empty($type))
                {
                    $type = str_replace('{$title}', "This is a thread title", $type);
                    $style_types_with .= '<br><input style="margin:10px" onclick="document.getElementsByName(\'special\')[0].value = 1;" type="radio" name="type" value="'.$key.'">'.$type;
                }
            }
            $styled = unserialize($mybb->user['styled_threads']);
            if (!is_array($styled))
            {
                $styled = array();
            }
            if (empty($styled) or !$styled)
            {
                eval("\$styled_threads = \"".$templates->get('newpoints_style_empty')."\";");
            }
            else
            {
                $styled_threads = "";
                foreach($styled as $tid)
                {
                    $query = $db->simple_select('threads', '*', 'tid=\''.intval($tid).'\'');
                    while($t = $db->fetch_array($query))
                    {
                        $styled_thread = htmlspecialchars($t['subject']);
                    }
                    $query2 = $db->simple_select('newpoints_style', '*', 'tid=\''.intval($tid).'\'');
                    while($s = $db->fetch_array($query2))
                    {
                        $expiry_date = $s['expiry'];
                        $type = $s['type'];
                        $special = $s['special'];
                    }
                    if ($special == 0)
                    {
                        $types = explode(',', $mybb->settings['newpoints_style_types_without']);
                        $type = $types[$type];
                        $styled_thread = str_replace('{$title}', $styled_thread, $type);
                    }
                    elseif ($special == 1)
                    {
                        $types = explode(',', $mybb->settings['newpoints_style_types_with']);
                        $type = $types[$type];
                        $styled_thread = str_replace('{$title}', $styled_thread, $type);
                    }
                    eval("\$styled_threads .= \"".$templates->get('newpoints_style_thread')."\";");
                }
            }
            eval("\$page = \"".$templates->get('newpoints_style')."\";");
            output_page($page);
        }
		
	}
}

function newpoints_style_expire()
{
    global $mybb, $db;
    $expired = array();
    $query = $db->simple_select('newpoints_style', '*', 'expiry < NOW()');
    while ($thread = $db->fetch_array($query))
    {
        array_push($expired, $thread['tid']);
    }
    foreach ($expired as $t)
    {
        $db->write_query("DELETE FROM ".TABLE_PREFIX."newpoints_style WHERE tid = ".$t);
        $db->write_query("UPDATE ".TABLE_PREFIX."threads SET styled = 0 WHERE tid = ".$t);
        $query2 = $db->simple_select('users', '*', 'styled_threads LIKE \'%"'.$t.'"%\'');
        while ($user = $db->fetch_array($query2))
        {
            $styled_threads = unserialize($user['styled_threads']);
            $styled_threads = array_diff($styled_threads, [$t]);
            $uid = $user['uid'];
        }
    }
    
}


function newpoints_style_affect()
{
    global $mybb, $db, $thread;
    $thread['styled'] = $thread['subject'];
    $query = $db->query("SELECT * FROM ".TABLE_PREFIX."users WHERE uid = {$thread['uid']}");
    while ($user = $db->fetch_array($query))
    {
        $styled_threads = unserialize($user['styled_threads']);
    }
    if (!is_array($styled_threads))
    {
        $styled_threads = array();
    }
    foreach ($styled_threads as $styled)
    {
        if ($styled == $thread['tid'])
        {
            $query = $db->simple_select('newpoints_style', '*', 'tid = '.$styled);
            while ($t = $db->fetch_array($query))
            {
                $type = $t['type'];
                $special = $t['special'];
            }
            if ($special == 0)
            {
                $types = explode(',', $mybb->settings['newpoints_style_types_without']);
            }
            elseif($special == 1)
            {
                $types = explode(',', $mybb->settings['newpoints_style_types_with']);
            }
            $type = $types[$type];
            $thread['styled'] = str_replace('{$title}', $thread['subject'], $type);
        }
    }
}





?>