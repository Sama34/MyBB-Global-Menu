<?php
if(!defined('IN_MYBB')){
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

function mn_gmenu_info(){
	return array(
		'name'			=> 'Global Menu',
		'description'   => 'Add menu on forum global',
		'website'       => 'http://mybbhacks.zingaburga.com',
		'author'        => 'MyBB Hacks Community',
		'authorsite'    => 'http://mybbhacks.zingaburga.com',
		'version'       => '1.0',
		'guid'          => '',
		'compatibility' => '16*'
	);
}

function mn_gmenu_activate(){
	mn_gmenu_deactivate();
	$GLOBALS['db']->write_query('CREATE TABLE '.TABLE_PREFIX.'mn_gmenu (
		mngmid int unsigned not null auto_increment,
		mngmtitle varchar(20) not null default \'\',
		mngmfid int not null default 0,
		mngmfids text not null default \'\',
		mngmurl varchar(20) not null default \'\',
		mngmurls text not null default \'\',
		mngmugid text not null default \'\',
		mngmactive tinyint(1) not null default 0,
		mngmdo int not null default 1,
		mngmcm text not null default \'\',
		PRIMARY KEY (mngmid), KEY(mngmactive), KEY(mngmdo)
	) '.$GLOBALS['db']->build_create_table_collation().'');
	mn_gmenu_cache_update();
	$template = array(
		'title'		=> 'mn_gmenu',
		'template'	=> $GLOBALS['db']->escape_string('<div class="menu"><ul>{$mn_gmenu_menu}</ul></div>'),
		'sid'		=> -1
	);
	$GLOBALS['db']->insert_query('templates', $template);
	$template = array(
		'title'		=> 'mn_gmenu_menu',
		'template'	=> $GLOBALS['db']->escape_string('<li{$mn_gmenu_class}><a href="{$mn_gmenu_url}">{$mn_gmenu_title}</a></li>'),
		'sid'		=> -1
	);
	$GLOBALS['db']->insert_query('templates', $template);
	change_admin_permission('config', 'mn_gmenu', 0);
}

function mn_gmenu_deactivate(){
	if(is_object($GLOBALS['cache']->handler)){
		$GLOBALS['cache']->handler->delete('mn_gmenu');
	}
	$GLOBALS['db']->delete_query('datacache', 'title = "mn_gmenu"');
	$GLOBALS['db']->drop_table('mn_gmenu');
	$GLOBALS['db']->delete_query('templates', 'title IN("mn_gmenu", "mn_gmenu_menu")');
	change_admin_permission('config', 'mn_gmenu', -1);
}

$plugins->add_hook('admin_config_menu', 'mn_gmenu_menu');
function mn_gmenu_menu(&$sub_menu){
	$sub_menu[] = array('id' => 'mn_gmenu', 'title' => 'Global Menu', 'link' => 'index.php?module=config-mn_gmenu');
}

$plugins->add_hook('admin_config_action_handler', 'mn_gmenu_action');
function mn_gmenu_action(&$actions){
	$actions['mn_gmenu'] = array('active' => 'mn_gmenu', 'file' => 'mn_gmenu');
}

$plugins->add_hook('admin_config_permissions', 'mn_gmenu_permissions');
function mn_gmenu_permissions(&$admin_permissions){
	$admin_permissions['mn_gmenu'] = 'Can manage Global Menu?';
}

$plugins->add_hook('admin_load', 'mn_gmenu_manage');
function mn_gmenu_manage(){
	if($GLOBALS['run_module'] == 'config' && $GLOBALS['action_file'] == 'mn_gmenu'){
		global $db, $mybb, $page;
		$page->add_breadcrumb_item('Global Menu', 'index.php?module=config-mn_gmenu');
		if(!$mybb->input['action']){
			$page->output_header('Global Menu');
			$sub_tabs['manage_gmenu'] = array(
				'title'			=> 'Global Menu',
				'link'			=> 'index.php?module=config-mn_gmenu',
				'description'	=> 'Global Menu management'
			);
			$sub_tabs['add_gmenu'] = array(
				'title'			=> 'Add Menu',
				'link'			=> 'index.php?module=config-mn_gmenu&amp;action=add',
				'description'	=> 'Add a new Global Menu'
			);
			$page->output_nav_tabs($sub_tabs, 'manage_gmenu');
			$form = new Form('index.php?module=config-mn_gmenu&amp;action=update_gmenu', 'post');
			$table = new Table;
			$query = $db->simple_select('mn_gmenu', '*','', array('order_by' => 'mngmdo ASC'));
			if($db->num_rows($query)){
				$table->construct_header('Order', array('class' => 'align_center', 'width' => 40));
				$table->construct_header('Menu Title', array('class' => 'align_center', 'width' => 200));
				$table->construct_header('Status', array('class' => 'align_center', 'width' => 75));
				$table->construct_header('URL', array('class' => 'align_center'));
				$table->construct_header('Del', array('class' => 'align_center', 'width' => 50));
				while($mn_gmenu = $db->fetch_array($query)){
					if($mn_gmenu['mngmactive'] == 1){
						$mngmactive = '<span style="color: green"><strong>On</strong></span>';
					}else{
						$mngmactive = '<span style="color: red"><strong>Off</strong></span>';
					}
					if($mn_gmenu['mngmurl']){
						$mngmurl = htmlspecialchars_uni($mn_gmenu['mngmurl']);
					}else{
						$mngmurl = get_forum_link($mn_gmenu['mngmfid']);
					}
					$table->construct_cell($form->generate_text_box('mngmdo['.$mn_gmenu['mngmid'].']', intval($mn_gmenu['mngmdo']), array('style' => 'width: 80%', 'class' => 'align_center')), array('rowspan' => 2, 'class' => 'align_center'));
					$table->construct_cell('<strong><a href="index.php?module=config-mn_gmenu&amp;action=edit&amp;mngmid='.$mn_gmenu['mngmid'].'">'.htmlspecialchars_uni($mn_gmenu['mngmtitle']).'</a></strong>');
					$table->construct_cell($mngmactive, array('class' => 'align_center'));
					$table->construct_cell($mngmurl, array('class' => 'smalltext'));
					$table->construct_cell('<a href="index.php?module=config-mn_gmenu&amp;action=delete&amp;mngmid='.$mn_gmenu['mngmid'].'&amp;my_post_key='.$mybb->post_code.'" onclick="return AdminCP.deleteConfirmation(this, \'Are you sure you want to delete this menu?\')">Del</a>', array('class' => 'align_center'));
					$table->construct_row();
					if($mn_gmenu['mngmugid'] != '-1'){
						if(!is_array($usergroups)){
							$usergroups = $GLOBALS['cache']->read('usergroups');
						}
						$mngmugid = $mngmugid_sep = '';
						foreach(explode(',', $mn_gmenu['mngmugid']) as $gid){
							$mngmugid .= $mngmugid_sep.htmlspecialchars_uni($usergroups[$gid]['title']);
							if(!$mngmugid_sep){
								$mngmugid_sep = ', ';
							}
						}
					}else{
						$mngmugid = 'All Usergroups';
					}
					$table->construct_cell('Viewable by: '.$mngmugid.'', array('colspan' => 8, 'class' => 'smalltext'));
					$table->construct_row();
				}
			}else{
				$table->construct_cell('<strong>You don\'t have any Global Menu</strong>', array('class' => 'align_center'));
				$table->construct_row();
				$empty_news = true;
			}
			$table->output('Global Menu');
			if(!$empty_news){
				$buttons[] = $form->generate_submit_button('Update Menu');
				$form->output_submit_wrapper($buttons);
			}
			$form->end();
			$page->output_footer();
		}
		
		if($mybb->input['action'] == 'add'){
			if($mybb->request_method == 'post'){
				if(!trim($mybb->input['mngmtitle'])){
					$errors[] = 'Please enter a title for this menu.';
				}
				if(my_strlen($mybb->input['mngmtitle']) > 20){
					$errors[] = 'Maximum characters for the menu title is 20.';
				}
				if($mybb->input['mngmtype'] == 1){
					$mngmtype_checked[1] = ' checked="checked"';
				}else{
					$mngmtype_checked[2] = ' checked="checked"';
				}
				if($mybb->input['mngmcms'] == 1){
					$mngmcms_checked[1] = ' checked="checked"';
				}else{
					$mngmcms_checked[2] = ' checked="checked"';
				}
				if(!$errors){
					$add_gmenu = array(
						'mngmtitle' => $db->escape_string($mybb->input['mngmtitle']),
						'mngmurls'  => $db->escape_string($mybb->input['mngmurls']),
						'mngmactive'  => intval($mybb->input['mngmactive']),
						'mngmdo'  => intval($mybb->input['mngmdo'])
					);
					if(is_array($mybb->input['mngmugid'])){
						$ugchecked = array();
						foreach($mybb->input['mngmugid'] as $gid){
							$ugchecked[] = intval($gid);
						}
						$add_gmenu['mngmugid'] = implode(',', $ugchecked);
					}else{
						$add_gmenu['mngmugid'] = '-1';
					}
					if(is_array($mybb->input['mngmfids'])){
						$fchecked = array();
						foreach($mybb->input['mngmfids'] as $fid){
							$fchecked[] = intval($fid);
						}
						$add_gmenu['mngmfids'] = implode(',', $fchecked);
					}else{
						$add_gmenu['mngmfids'] = '-1';
					}
					if($mybb->input['mngmtype'] == 1){
						$add_gmenu['mngmfid'] = intval($mybb->input['mngmfid']);
						$add_gmenu['mngmurl'] = '';
					}else{
						$add_gmenu['mngmfid'] = '-1';
						$add_gmenu['mngmurl'] = $db->escape_string($mybb->input['mngmurl']);
					}
					if($mybb->input['mngmcms'] == 1){
						$add_gmenu['mngmcm'] = '';
					}else{
						$add_gmenu['mngmcm'] = $db->escape_string($mybb->input['mngmcm']);
					}
					$mngmid = $db->insert_query('mn_gmenu', $add_gmenu);
					mn_gmenu_cache_update();
					log_admin_action($mngmid);
					flash_message('The Menu Been Saved Successfully', 'success');
					admin_redirect('index.php?module=config-mn_gmenu');
				}
			}
			$page->add_breadcrumb_item('Add Menu');
			$page->output_header('Add A New Menu');
			$sub_tabs['manage_gmenu'] = array(
				'title'			=> 'Global Menu',
				'link'			=> 'index.php?module=config-mn_gmenu',
				'description'	=> 'Global Menu management'
			);
			$sub_tabs['add_gmenu'] = array(
				'title'			=> 'Add Menu',
				'link'			=> 'index.php?module=config-mn_gmenu&amp;action=add',
				'description'	=> 'Add a new Global Menu'
			);
			$page->output_nav_tabs($sub_tabs, 'add_gmenu');
			$form = new Form('index.php?module=config-mn_gmenu&amp;action=add', 'post', 'add');
			if($errors){
				$page->output_inline_error($errors);
			}else{
				$mybb->input['mngmtitle'] = '';
				$mybb->input['mngmfid'] = 0;
				$mybb->input['mngmfids'] = '';
				$mybb->input['mngmurl'] = '';
				$mybb->input['mngmurls'] = '';
				$mybb->input['mngmugid'] = '';
				$mybb->input['mngmcm'] = '';
				$mybb->input['mngmactive'] = 1;
				$mybb->input['mngmdo'] = 1;
				$mngmtype_checked[2] = '';
				$mngmtype_checked[1] = ' checked="checked"';
				$mngmcms_checked[2] = '';
				$mngmcms_checked[1] = ' checked="checked"';
			}
			$form_container = new FormContainer('Add Global Menu');
			$form_container->output_row('Title <em>*</em>', 'This menu title will be displayed as text display for this menu.', $form->generate_text_box('mngmtitle', $mybb->input['mngmtitle']));
			$form_container->output_row('Menu Type', 'Select menu type.', "<script type=\"text/javascript\">
    function checkAction(id)
    {
        var checked = '';
        
        $$('.'+id+'s_check').each(function(e)
        {
            if(e.checked == true)
            {
                checked = e.value;
            }
        });
        $$('.'+id+'s').each(function(e)
        {
        	Element.hide(e);
        });
        if($(id+'_'+checked))
        {
            Element.show(id+'_'+checked);
        }
    }    
</script>
	<div style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
		<table border=\"0\">
			<tr>
				<td valign=\"top\" style=\"border: 0px solid;\">
					<input type=\"radio\" name=\"mngmtype\" value=\"2\"{$mngmtype_checked[2]} class=\"mngmtypes_check\" onclick=\"checkAction('mngmtype');\" />
				</td>
				<td style=\"border: 0px solid;\">
					<strong>File</strong>
					<div class=\"smalltext\">Use file as menu URL</div>
					<div id=\"mngmtype_2\" class=\"mngmtypes\">
						".$form->generate_text_box('mngmurl', $mybb->input['mngmurl'])."
					</div>
				</td>
			</tr>
			<tr>
				<td valign=\"top\" style=\"border: 0px solid;\">
					<input type=\"radio\" name=\"mngmtype\" value=\"1\"{$mngmtype_checked[1]} class=\"mngmtypes_check\" onclick=\"checkAction('mngmtype');\" />
				</td>
				<td style=\"border: 0px solid;\">
					<strong>Forum</strong>
					<div class=\"smalltext\">Use a forum as menu URL.</div>
					<div id=\"mngmtype_1\" class=\"mngmtypes\">
						".$form->generate_forum_select('mngmfid', $mybb->input['mngmfid'])."
					</div>
				</td>
			</tr>
		</table>
	</div>
	<script type=\"text/javascript\">
		checkAction('mngmtype');
	</script>");
			$form_container->output_row('Covered Area', 'Active area for this menu.', '<div class="smalltext">
	<strong>Forum(s):</strong> Selected forum(s) where this menu will be actived.
</div>
'.$form->generate_forum_select('mngmfids[]', $mybb->input['mngmfids'], array('multiple' => true, 'size' => 5)).'
<br /><br />
<div class="smalltext">
	<strong>File(s):</strong> Selected file(s) where this menu will be actived. Separate each file with new line.
</div>
'.$form->generate_text_area('mngmurls', $mybb->input['mngmurls']).'');
			$form_container->output_row('Viewable By Usergroup', 'Don\'t select any usergroup if this menu is viewable for all uergroups.', $form->generate_group_select('mngmugid[]', $mybb->input['mngmugid'], array('multiple' => true, 'size' => 5)));
			$form_container->output_row('Menu Style', 'Select menu style.', "<div style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
		<table border=\"0\">
			<tr>
				<td valign=\"top\" style=\"border: 0px solid;\">
					<input type=\"radio\" name=\"mngmcms\" value=\"1\"{$mngmcms_checked[1]} class=\"mngmcmss_check\" onclick=\"checkAction('mngmcms');\" />
				</td>
				<td style=\"border: 0px solid;\">
					<strong>Default</strong>
					<div class=\"smalltext\">Use default menu</div>
				</td>
			</tr>
			<tr>
				<td valign=\"top\" style=\"border: 0px solid;\">
					<input type=\"radio\" name=\"mngmcms\" value=\"2\"{$mngmcms_checked[2]} class=\"mngmcmss_check\" onclick=\"checkAction('mngmcms');\" />
				</td>
				<td style=\"border: 0px solid;\">
					<strong>Custom</strong>
					<div class=\"smalltext\">Use a custom menu layout</div>
					<div id=\"mngmcms_2\" class=\"mngmcmss\">
						<br />
						<div class=\"smalltext\">
							Example:<br />
							<code>&lt;li{MN_GM_SELECTED}&gt;&lt;a href=&quot;{MN_GM_URL}&quot;&gt;{MN_GM_TITLE}&lt;/a&gt;&lt;/li&gt;</code>
						</div>
						".$form->generate_text_area('mngmcm', $mybb->input['mngmcm'])."
					</div>
				</td>
			</tr>
		</table>
	</div>
	<script type=\"text/javascript\">
		checkAction('mngmcms');
	</script>");
			$form_container->output_row('Active', 'Set <em>Yes</em> to activate this menu, <em>No</em> to disable this menu. If this menu is disabled, then this menu won\'t be displayed.', $form->generate_yes_no_radio('mngmactive', $mybb->input['mngmactive'], true));
			$form_container->output_row('Display Order', 'Display order for this menu.', $form->generate_text_box('mngmdo', $mybb->input['mngmdo']));
			$form_container->end();
			$buttons[] = $form->generate_submit_button('Add Menu');
			$form->output_submit_wrapper($buttons);
			$form->end();
			$page->output_footer();
		}
		
		if($mybb->input['action'] == 'edit'){
			$query = $db->simple_select('mn_gmenu', 'COUNT(mngmid) as gmenunum', 'mngmid="'.intval($mybb->input['mngmid']).'"');
			if($db->fetch_field($query, 'gmenunum') < 1){
				flash_message('Invalid Menu', 'error');
				admin_redirect('index.php?module=config-mn_gmenu');
			}
			if($mybb->request_method == 'post'){
				if(!trim($mybb->input['mngmtitle'])){
					$errors[] = 'Please enter a title for this menu.';
				}
				if(my_strlen($mybb->input['mngmtitle']) > 20){
					$errors[] = 'Maximum characters for the menu title is 20.';
				}
				if($mybb->input['mngmtype'] == 1){
					$mngmtype_checked[1] = ' checked="checked"';
				}else{
					$mngmtype_checked[2] = ' checked="checked"';
				}
				if($mybb->input['mngmcms'] == 1){
					$mngmcms_checked[1] = ' checked="checked"';
				}else{
					$mngmcms_checked[2] = ' checked="checked"';
				}
				if(!$errors){
					$update_menu = array(
						'mngmtitle' => $db->escape_string($mybb->input['mngmtitle']),
						'mngmurls' => $db->escape_string($mybb->input['mngmurls']),
						'mngmactive'  => intval($mybb->input['mngmactive']),
						'mngmdo'  => intval($mybb->input['mngmdo'])
					);
					if(is_array($mybb->input['mngmugid'])){
						$ugchecked = array();
						foreach($mybb->input['mngmugid'] as $gid){
							$ugchecked[] = intval($gid);
						}
						$update_menu['mngmugid'] = implode(',', $ugchecked);
					}else{
						$update_menu['mngmugid'] = '-1';
					}
					if(is_array($mybb->input['mngmfids'])){
						$fchecked = array();
						foreach($mybb->input['mngmfids'] as $fid){
							$fchecked[] = intval($fid);
						}
						$update_menu['mngmfids'] = implode(',', $fchecked);
					}else{
						$update_menu['mngmfids'] = '-1';
					}
					if($mybb->input['mngmtype'] == 1){
						$update_menu['mngmfid'] = intval($mybb->input['mngmfid']);
						$update_menu['mngmurl'] = '';
					}else{
						$update_menu['mngmfid'] = '-1';
						$update_menu['mngmurl'] = $db->escape_string($mybb->input['mngmurl']);
					}
					if($mybb->input['mngmcms'] == 1){
						$update_menu['mngmcm'] = '';
					}else{
						$update_menu['mngmcm'] = $db->escape_string($mybb->input['mngmcm']);
					}
					$db->update_query('mn_gmenu', $update_menu, 'mngmid="'.intval($mybb->input['mngmid']).'"');
					mn_gmenu_cache_update();
					log_admin_action(intval($mybb->input['mngmid']));
					flash_message('The Menu Has Been Updated Successfully', 'success');
					admin_redirect('index.php?module=config-mn_gmenu');
				}
			}
			$page->add_breadcrumb_item('Edit Menu');
			$page->output_header('Edit Menu');
			unset($sub_tabs);
			$sub_tabs['edit_gmenu'] = array(
				'title'       => 'Edit Menu',
				'link'        => 'index.php?module=config-mn_gmenu',
				'description' => 'Edit menu settings'
			);
			$page->output_nav_tabs($sub_tabs, 'edit_gmenu');
			$form = new Form('index.php?module=config-mn_gmenu&amp;action=edit', 'post', 'edit');
			echo $form->generate_hidden_field('mngmid', $mybb->input['mngmid']);
			if($errors){
				$page->output_inline_error($errors);
			}else{
				$query = $db->simple_select('mn_gmenu', '*', 'mngmid="'.intval($mybb->input['mngmid']).'"');
				$mn_gmenu = $db->fetch_array($query);
				$mybb->input['mngmtitle'] = $mn_gmenu['mngmtitle'];
				$mybb->input['mngmfid'] = $mn_gmenu['mngmfid'];
				$mybb->input['mngmurl'] = $mn_gmenu['mngmurl'];
				$mybb->input['mngmurls'] = $mn_gmenu['mngmurls'];
				$mybb->input['mngmcm'] = $mn_gmenu['mngmcm'];
				$mybb->input['mngmactive'] = $mn_gmenu['mngmactive'];
				$mybb->input['mngmdo'] = $mn_gmenu['mngmdo'];
				$mybb->input['mngmugid'] = explode(',', $mn_gmenu['mngmugid']);
				$mybb->input['mngmfids'] = explode(',', $mn_gmenu['mngmfids']);
				if($mn_gmenu['mngmfid'] > 0){
					$mngmtype_checked[1] = ' checked="checked"';
					$mngmtype_checked[2] = '';
				}else{
					$mngmtype_checked[1] = '';
					$mngmtype_checked[2] = ' checked="checked"';
				}
				if($mn_gmenu['mngmcm']){
					$mngmcms_checked[2] = ' checked="checked"';
					$mngmcms_checked[1] = '';
				}else{
					$mngmcms_checked[2] = '';
					$mngmcms_checked[1] = ' checked="checked"';
				}
			}
			$form_container = new FormContainer('Edit Menu Settings');
			$form_container->output_row('Title <em>*</em>', 'This menu title will be displayed as text display for this menu.', $form->generate_text_box('mngmtitle', $mybb->input['mngmtitle']));
			$form_container->output_row('Menu Type', 'Select menu type.', "<script type=\"text/javascript\">
	function checkAction(id)
	{
		var checked = '';
		$$('.'+id+'s_check').each(function(e)
		{
			if(e.checked == true)
			{
				checked = e.value;
			}
		});
		$$('.'+id+'s').each(function(e)
		{
			Element.hide(e);
		});
		if($(id+'_'+checked))
		{
			Element.show(id+'_'+checked);
		}
	}    
</script>
<div style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<table border=\"0\">
		<tr>
			<td valign=\"top\" style=\"border: 0px solid;\">
				<input type=\"radio\" name=\"mngmtype\" value=\"2\"{$mngmtype_checked[2]} class=\"mngmtypes_check\" onclick=\"checkAction('mngmtype');\" />
			</td>
			<td style=\"border: 0px solid;\">
				<strong>File</strong>
				<div class=\"smalltext\">Use file as menu URL</div>
				<div id=\"mngmtype_2\" class=\"mngmtypes\">
					".$form->generate_text_box('mngmurl', $mybb->input['mngmurl'])."
				</div>
			</td>
		</tr>
		<tr>
			<td valign=\"top\" style=\"border: 0px solid;\">
				<input type=\"radio\" name=\"mngmtype\" value=\"1\"{$mngmtype_checked[1]} class=\"mngmtypes_check\" onclick=\"checkAction('mngmtype');\" />
			</td>
			<td style=\"border: 0px solid;\">
				<strong>Forum</strong>
				<div class=\"smalltext\">Use a forum as menu URL.</div>
				<div id=\"mngmtype_1\" class=\"mngmtypes\">
					".$form->generate_forum_select('mngmfid', $mybb->input['mngmfid'])."
				</div>
			</td>
		</tr>
	</table>
</div>
	<script type=\"text/javascript\">
		checkAction('mngmtype');
	</script>");
			$form_container->output_row('Covered Area', 'Active area for this menu.', '<div class="smalltext">
	<strong>Forum(s):</strong> Selected forum(s) where this menu will be actived.
</div>
'.$form->generate_forum_select('mngmfids[]', $mybb->input['mngmfids'], array('multiple' => true, 'size' => 5)).'
<br /><br />
<div class="smalltext">
	<strong>File(s):</strong> Selected file(s) where this menu will be actived. Separate each file with new line.
</div>
'.$form->generate_text_area('mngmurls', $mybb->input['mngmurls']).'');
			$form_container->output_row('Viewable By Usergroup', 'Don\'t select any usergroup if this menu is viewable for all uergroups.', $form->generate_group_select('mngmugid[]', $mybb->input['mngmugid'], array('multiple' => true, 'size' => 5)));
			$form_container->output_row('Menu Style', 'Select menu style.', "<div style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
		<table border=\"0\">
			<tr>
				<td valign=\"top\" style=\"border: 0px solid;\">
					<input type=\"radio\" name=\"mngmcms\" value=\"1\"{$mngmcms_checked[1]} class=\"mngmcmss_check\" onclick=\"checkAction('mngmcms');\" />
				</td>
				<td style=\"border: 0px solid;\">
					<strong>Default</strong>
					<div class=\"smalltext\">Use default menu</div>
				</td>
			</tr>
			<tr>
				<td valign=\"top\" style=\"border: 0px solid;\">
					<input type=\"radio\" name=\"mngmcms\" value=\"2\"{$mngmcms_checked[2]} class=\"mngmcmss_check\" onclick=\"checkAction('mngmcms');\" />
				</td>
				<td style=\"border: 0px solid;\">
					<strong>Custom</strong>
					<div class=\"smalltext\">Use a custom menu layout.</div>
					<div id=\"mngmcms_2\" class=\"mngmcmss\">
						<br />
						<div class=\"smalltext\">
							Example:<br />
							<code>&lt;li{MN_GM_SELECTED}&gt;&lt;a href=&quot;{MN_GM_URL}&quot;&gt;{MN_GM_TITLE}&lt;/a&gt;&lt;/li&gt;</code>
						</div>
						".$form->generate_text_area('mngmcm', $mybb->input['mngmcm'])."
					</div>
				</td>
			</tr>
		</table>
	</div>
	<script type=\"text/javascript\">
		checkAction('mngmcms');
	</script>");
			$form_container->output_row('Active', 'Set <em>Yes</em> to activate this menu, <em>No</em> to disable this menu. If this menu is disabled, then this menu won\'t be displayed.', $form->generate_yes_no_radio('mngmactive', $mybb->input['mngmactive'], true));
			$form_container->output_row('Display Order', 'Display order for this menu.', $form->generate_text_box('mngmdo', $mybb->input['mngmdo']));
			$form_container->end();
			$buttons[] = $form->generate_submit_button('Update Menu');
			$buttons[] = $form->generate_reset_button('Reset');
			$form->output_submit_wrapper($buttons);
			$form->end();
			$page->output_footer();
		}
		
		if($mybb->input['action'] == 'delete'){
			$query = $db->simple_select('mn_gmenu', '*', 'mngmid="'.intval($mybb->input['mngmid']).'"');
			$mn_gmenu = $db->fetch_array($query);
			if(!$mn_gmenu['mngmid']){
				flash_message('Invalid Menu', 'error');
				admin_redirect('index.php?module=config-mn_gmenu');
			}
			if($mybb->input['no']){
				admin_redirect('index.php?module=config-mn_gmenu');
			}
			if($mybb->request_method == 'post'){
				$db->delete_query('mn_gmenu', 'mngmid="'.intval($mn_gmenu['mngmid']).'"');
				mn_gmenu_cache_update();
				log_admin_action($mn_gmenu['mngmid']);
				flash_message('The menu has been deleted successfully', 'success');
				admin_redirect('index.php?module=config-mn_gmenu');
			}else{
				$page->output_confirm_action('index.php?module=config-mn_gmenu&amp;action=delete&amp;mngmid='.$mn_gmenu['mngmid'].'', 'Are you sure you want to delete this menu?');
			}
		}

		if($mybb->input['action'] == 'update_gmenu' && $mybb->request_method == 'post'){
			if(!is_array($mybb->input['mngmdo'])){
				admin_redirect('index.php?module=config-mn_gmenu');
			}
			foreach($mybb->input['mngmdo'] as $mngmdo => $updates){
				$update_query = array(
					'mngmdo' => intval($updates)
				);
				$db->update_query('mn_gmenu', $update_query, 'mngmid="'.intval($mngmdo).'"');
			}
			mn_gmenu_cache_update();
			log_admin_action();
			flash_message('Your menu has been updated successfully', 'success');
			admin_redirect('index.php?module=config-mn_gmenu');
		}
		exit;
	}
}

function mn_gmenu_cache_update(){
	$mn_gmenu_cache = array();
	$query = $GLOBALS['db']->simple_select('mn_gmenu', '*', 'mngmactive=1', array('order_by' => 'mngmdo', 'order_dir' => 'asc'));
	while($mn_gmenu = $GLOBALS['db']->fetch_array($query)){
		$mn_gmenu_cache[$mn_gmenu['mngmid']] = $mn_gmenu;
	}
	$GLOBALS['cache']->update('mn_gmenu', $mn_gmenu_cache);
}

function mn_gmenu_ex($mngme,$mngmes,$mngmesp=''){
	if(empty($mngmesp)){
		$mngmesp = ',';
	}
	$mngmex = explode($mngmesp,$mngmes);
	return in_array($mngme,$mngmex);
}

$plugins->add_hook('global_start', 'mn_gmenu_run');
function mn_gmenu_run(){
	if(isset($GLOBALS['templatelist'])){
		$GLOBALS['templatelist'] .= ',mn_gmenu,mn_gmenu_menu';
	}
	$GLOBALS['plugins']->add_hook('global_end', 'mn_gmenu');
}

function mn_gmenu(){
	$mn_gmenucache = $GLOBALS['cache']->read('mn_gmenu');
	if(!empty($mn_gmenucache)){
		foreach($mn_gmenucache as $key => &$mngm){
			$mn_gmenu_class = '';
			if(mn_gmenu_ex($GLOBALS['mybb']->user['usergroup'],$mngm['mngmugid']) || $mngm['mngmugid'] == '-1'){
				if($mngm['mngmfid'] > 0){
					$mn_gmenu_url = get_forum_link($mngm['mngmfid']);
				}else{
					$mn_gmenu_url = htmlspecialchars_uni($mngm['mngmurl']);
				}
				if(mn_gmenu_ex($GLOBALS['style']['fid'],$mngm['mngmfids']) || mn_gmenu_ex($GLOBALS['current_page'],$mngm['mngmurls'],"\r\n")){
					$mn_gmenu_class = ' class="selected"';
				}
				$mn_gmenu_title = htmlspecialchars_uni($mngm['mngmtitle']);
				if(!$mngm['mngmcm']){
					eval('$mn_gmenu_menu .= "'.$GLOBALS['templates']->get('mn_gmenu_menu').'";');
				}else{
					$mn_gmenu_menu .= $mngm['mngmcm'];
					$mn_gmenu_menu = str_replace(array('{MN_GM_SELECTED}','{MN_GM_URL}','{MN_GM_TITLE}'), array($mn_gmenu_class,$mn_gmenu_url,$mn_gmenu_title), $mn_gmenu_menu);
				}
			}
		}
		eval('$mn_gmenu = "'.$GLOBALS['templates']->get('mn_gmenu').'";');
		$GLOBALS['header'] = str_replace('<!-- mn_gmenu -->', $mn_gmenu, $GLOBALS['header']);
	}
}
?>