<?php
/*
 * Plugin Name:   Ajax Post Listing
 * Version:       1.0
 * Plugin URI:    http://wordpress.org/extend/plugins/ajax-post-listing/
 * Description:   This plugin provides ajax based listing and navigation of posts under each catetgory. Adjust your settings <a href="options-general.php?page=mbp_apl_options">here</a>.
 * Author:        MaxBlogPress
 * Author URI:    http://www.maxblogpress.com
 *
 * License:       GNU General Public License
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * Copyright (C) 2007 www.maxblogpress.com
 *
 */
$mbpapl_path      = preg_replace('/^.*wp-content[\\\\\/]plugins[\\\\\/]/', '', __FILE__);
$mbpapl_path      = str_replace('\\','/',$mbpapl_path);
$mbpapl_dir       = substr($mban_path,0,strrpos($mbpapl_path,'/'));
$mbpapl_siteurl   = get_bloginfo('wpurl');
$mbpapl_siteurl   = (strpos($mbpapl_siteurl,'http://') === false) ? get_bloginfo('siteurl') : $mbpapl_siteurl;
$mbpapl_fullpath  = $mbpapl_siteurl.'/wp-content/plugins/'.$mbpapl_dir.'';
$mbpapl_fullpath  = $mbpapl_fullpath.'ajax-post-listing/';
$mbpapl_abspath   = str_replace("\\","/",ABSPATH); 

define('MBP_APL_ABSPATH', $mbpapl_path);
define('MBP_APL_LIBPATH', $mbpapl_fullpath);
define('MBP_APL_SITEURL', $mbpapl_siteurl);
define('MBP_APL_NAME', 'Ajax Post Listing');
define('MBP_APL_VERSION', '1.0');  
define('MBP_APL_LIBPATH', $mbpapl_fullpath);

//Hooks
add_action('wp_print_scripts','mbp_apl_include_jquery');
add_action('admin_menu', 'mbp_apl_setting_page');
add_action('init', 'mbp_apl_register_widget');
if (get_option('mbp_apl_show_single_post') == 1) {
	add_filter('the_content', 'mbp_apl_post_content');
}

/**
* include jquery library
*/
function mbp_apl_include_jquery() {
	 if (!is_admin()) {
	 		wp_enqueue_script("jquery");
			wp_enqueue_script('my-script', MBP_APL_LIBPATH . 'script.js', array('jquery'));
			echo "<link type='text/css' rel='stylesheet' href='". MBP_APL_LIBPATH ."style.css'/>";
		}		
	 
}

/**
* register widget
*/
function mbp_apl_register_widget(){
	register_sidebar_widget ('Ajax Post List','mbp_apl_widget');
	register_widget_control('Ajax Post List', 'mbp_apl_widget_control');
}

/**
* widget body
*/
function mbp_apl_widget($args = array()) {
	extract($args);
	$options = get_option('mbp_apl_widget_title');
	$title 	 = ($options['mbp_apl_widget_title'] == '')?'' : $options['mbp_apl_widget_title'];
	echo $before_widget . $before_title . $title . $after_title;
	mbp_apl_widget_content();
	echo $after_widget;
}

/**
* widget content
*/
function mbp_apl_widget_content($single=0) {
	if ($single == 1) {
		ob_start();
	}
	//get the config variables
	global $table_prefix;
	$category_order 	= (get_option('mbp_apl_category_order') == '')?'ASC':get_option('mbp_apl_category_order');
	$category_orderby 	= (get_option('mbp_apl_category_orderby') == 'ID')?'a.term_id' : 'a.name';
	$post_order 		= (get_option('mbp_apl_post_order') == 'Random')?"rand()" : "post_date " . get_option('mbp_apl_post_order');
	$post_limit 		= (get_option('mbp_apl_post_limit') == '')?5:get_option('mbp_apl_post_limit');
	$categories			= get_option('mbp_apl_categories');
	$start				= ($_GET['start'] == '')?0:$_GET['start'];
	$show_comment		= get_option('mbp_apl_show_comment');
	
	if ($categories != '') {
		$query = "SELECT
						a.name as category_name,
						b.term_id
				 FROM 
						". $table_prefix ."terms a
				 INNER JOIN ". $table_prefix ."term_taxonomy b ON(a.term_id=b.term_id)
				 WHERE
					b.taxonomy='category'
					AND b.count > 0
					AND b.term_id IN(". $categories . ")" . "
				ORDER BY 
						 ". $category_orderby . " " . $category_order . " LIMIT " . $start . ",1";
						 
		$query_count = "SELECT
						a.name as category_name,
						b.term_id
					 FROM 
							". $table_prefix ."terms a
					 INNER JOIN ". $table_prefix ."term_taxonomy b ON(a.term_id=b.term_id)
					 WHERE
						b.taxonomy='category'
						AND b.count > 0
						AND b.term_id IN(". $categories . ")";
		$sql_count = mysql_query($query_count);
		$no_count  = mysql_num_rows($sql_count);													 
							 				 
	} else {
		$query = "SELECT
						a.name as category_name,
						b.term_id
				 FROM 
						". $table_prefix ."terms a
				 INNER JOIN ". $table_prefix ."term_taxonomy b ON(a.term_id=b.term_id)
				 WHERE
					b.taxonomy='category'
					AND b.count > 0
				ORDER BY  
						 ". $category_orderby . " " .  $category_order . " LIMIT " . $start . ",1";	
		$query_count = "SELECT
						a.name as category_name,
						b.term_id
					 FROM 
							". $table_prefix ."terms a
					 INNER JOIN ". $table_prefix ."term_taxonomy b ON(a.term_id=b.term_id)
					 WHERE
						b.taxonomy='category'
						AND b.count > 0";
		$sql_count = mysql_query($query_count);
		$no_count  = mysql_num_rows($sql_count);													 	
	}
	$sql  = mysql_query($query);
	$no   = mysql_num_rows($sql);
	$rs   = mysql_fetch_array($sql);
 if ($no > 0) {
?>	
<div id="result">	
	<?php if ($no_count > 1) { ?>		
		<div class="prev_next">
			<a href="#" id="prev_cat"><img alt="Previous Category" title="Previous Category" border="0" src="<?php echo MBP_APL_LIBPATH?>images/pre-bttn.png" /></a>
			<a href="#" id="next_cat"><img alt="Next Category" title="Next Category" border="0" src="<?php echo MBP_APL_LIBPATH?>images/next-bttn.png" /></a>
		</div>		
	<?php } ?>
	<div>
		<h2><?php echo $rs['category_name'];?></h2>
	</div>
		<ul class="ajaxul">
		<?php 
		//post query
		$query_post = "SELECT 
							a.ID,
							a.post_title,
							a.comment_count
					   FROM
							wp_posts a
					   INNER JOIN wp_term_relationships b ON(a.ID = b.object_id)
					   INNER JOIN wp_term_taxonomy c ON(b.term_taxonomy_id = c.term_taxonomy_id)
					   WHERE
							a.post_status='publish'
							AND a.post_type='post'
							AND c.term_taxonomy_id='" .$rs['term_id'] . "'
					   ORDER BY " . $post_order . " " . "LIMIT 0," . $post_limit;					
		$sql_post	= mysql_query($query_post);
		while($rs_post = mysql_fetch_array($sql_post)) {							 		
		?>
			<li style="padding:10px;"><a href="<?php echo get_permalink( $rs_post['ID'] );?>" id="post_link"><?php echo $rs_post['post_title'];?></a>
				<?php if ($show_comment == 1) { 
					$comment_text = ($rs_post['comment_count'] > 1)?'comments':'comment';
				?>
					<br/>
					<span>[has <?php echo $rs_post['comment_count'] . " " . $comment_text;?>]</span>
				<?php } ?>			
			</li>
			

			
		<?php } ?>			
		</ul>
		
		<form>
			<input type="hidden" name="path" id="path" value="<?php echo MBP_APL_LIBPATH;?>" />
			<input type="hidden" name="next_value" id="next_value" value="1" />
			<input type="hidden" name="prev_value" id="prev_value" value="1" />
		</form>	
		<div>
			<?php echo mbp_apl_powered_by();?>	
		</div>
</div>	
<?php
	}	
	
	if ($single == 1) {
		$output = ob_get_contents();	
		ob_end_clean();
		return $output;			
	}							
}

/**
* powered by options
*/
function mbp_apl_powered_by() {
	$apl_pwdby = get_option('mbp_apl_pwdby_option');
	if ($apl_pwdby[0] != 'apl_pwdby') {
		return "<div><a target='_blank' href='http://wordpress.org/extend/plugins/ajax-post-listing/'>Powered by Ajax Post Listing</a></div>";
	} else {
		return;
	}	
 }

/**
* widget control panel
*/
function mbp_apl_widget_control() {
	$options = get_option('mbp_apl_widget_title');
	if ($_POST['mbp_apl_widget_title_submit']) {
		$options['mbp_apl_widget_title'] = $_POST['mbp_apl_widget_title'];
		update_option('mbp_apl_widget_title', $options);
	}
	
	$mbp_apl_widget_title = $options['mbp_apl_widget_title'];
?>
<p>
	<label>Title</label>
	<input size="30" type="text" name="mbp_apl_widget_title" value="<?php echo $mbp_apl_widget_title; ?>"/>
 	<input type="hidden" id="mbp_apl_widget_title_submit" name="mbp_apl_widget_title_submit"  value="1" />	
</p>
<?php }

/**
* single page listing
*/
function mbp_apl_post_content($content) {
	if (is_single() || is_page())
		return $content . mbp_apl_widget_content(1); 
	else
		return $content;
}

/**
* Admin settings menu
*/
function mbp_apl_setting_page(){
	add_options_page('Ajax Post Listing', 'Ajax Post Listing', 8, 'mbp_apl_options', 'mbp_apl_options');
}

/**
* Admin pages
*/
function mbp_apl_options() {
	$mbp_apl_activate = get_option('mbp_apl_activate');
	$reg_msg = '';
	$mbp_apl_msg = '';
	$form_1 = 'mbp_apl_reg_form_1';
	$form_2 = 'mbp_apl_reg_form_2';
		// Activate the plugin if email already on list
	if ( trim($_GET['mbp_onlist']) == 1 ) {
		$mbp_apl_activate = 2;
		update_option('mbp_apl_activate', $mbp_apl_activate);
		$reg_msg = 'Thank you for registering the plugin. It has been activated'; 
	} 
	// If registration form is successfully submitted
	if ( ((trim($_GET['submit']) != '' && trim($_GET['from']) != '') || trim($_GET['submit_again']) != '') && $mbp_apl_activate != 2 ) { 
		update_option('mbp_apl_name', $_GET['name']);
		update_option('mbp_apl_email', $_GET['from']);
		$mbp_apl_activate = 1;
		update_option('mbp_apl_activate', $mbp_apl_activate);
	}
	if ( intval($mbp_apl_activate) == 0 ) { // First step of plugin registration
		global $userdata;
		mbp_aplRegisterStep1($form_1,$userdata);
	} else if ( intval($mbp_apl_activate) == 1 ) { // Second step of plugin registration
		$name  = get_option('mbp_apl_name');
		$email = get_option('mbp_apl_email');
		mbp_aplRegisterStep2($form_2,$name,$email);
	} else if ( intval($mbp_apl_activate) == 2 ) { // Options page
			if ( trim($reg_msg) != '' ) {
				echo '<div id="message" class="updated fade"><p><strong>'.$reg_msg.'</strong></p></div>';
			}			
	$updated = 0;
	$mbp_apl_categories  		= get_option('mbp_apl_categories');
	$mbp_apl_category_order 	= (get_option('category_order') == '')?'DESC' : get_option('category_order');
	$mbp_apl_category_orderby 	= (get_option('category_orderby') == '')?'name' : get_option('category_orderby');
	$mbp_apl_post_order 		= (get_option('post_order') == '')?'DESC' : get_option('post_order');	
	$mbp_apl_post_limit 		= (get_option('post_limit') == '')?5 : get_option('post_limit');
	$mbp_apl_show_comment 		= (get_option('show_comment') == '')?0 : get_option('show_comment');
	$mbp_apl_show_single_post 	= (get_option('show_single_post') == '')?0 : get_option('show_single_post');	
	
	if($_POST['Submit'] == 'Save Configuration') {
		$mbp_apl_category_order 	= $_POST['category_order'];
		$mbp_apl_category_orderby 	= $_POST['category_orderby'];
		$mbp_apl_post_order 		= $_POST['post_order'];
		$mbp_apl_post_limit 		= $_POST['post_limit'];
		$mbp_apl_categories			= @implode(",", $_POST['categories']);
		$mbp_apl_show_comment		= $_POST['show_comment'];
		$mbp_apl_show_single_post	= $_POST['show_single_post'];		
		
		update_option("mbp_apl_category_order", $mbp_apl_category_order);
		update_option("mbp_apl_post_order", $mbp_apl_post_order);
		update_option("mbp_apl_categories", $mbp_apl_categories);
		update_option("mbp_apl_post_limit", $mbp_apl_post_limit);
		update_option("mbp_apl_show_comment", $mbp_apl_show_comment);
		update_option("mbp_apl_category_orderby", $mbp_apl_category_orderby);
		update_option("mbp_apl_show_single_post", $mbp_apl_show_single_post);
		$updated = 1;
	} 
	
	if($_POST['submit'] == "Remove"){
		$apl_pwdby = array(''.$_POST['apl_pwdby'].'');
		update_option('mbp_apl_pwdby_option', $apl_pwdby);
		$apl_pwdby = get_option('mbp_apl_pwdby_option');
		if( $apl_pwdby[0] == 'apl_pwdby' ) $apl_pwdby = 'checked';
		$updated = 1;
	}	
?>
<style type="text/css">
<!--
#wpcontent select {
	height:auto;
}
-->
</style>
<div class="wrap">
<h2><?php echo MBP_APL_NAME.' '.MBP_APL_VERSION; ?></h2>
		<strong><img src="<?php echo MBP_APL_LIBPATH;?>images/how.gif" border="0" align="absmiddle" /> <a href="http://wordpress.org/extend/plugins/ajax-post-listing/other_notes/" target="_blank">How to use it</a>&nbsp;&nbsp;&nbsp;
				<img src="<?php echo MBP_APL_LIBPATH;?>images/commentimg.gif" border="0" align="absmiddle" /> <a href="#">Community</a>
		&nbsp;&nbsp;&nbsp;
		<img src="<?php echo MBP_APL_LIBPATH;?>images/helpimg.gif" border="0" align="absmiddle" /> 
		<a href="http://www.maxblogpress.com/revived-plugins/" target="_blank">View our revived plugins</a>				
				</strong>
		<br/><br/>	
<?php if ($updated == 1) { ?>		
<div class="updated fade">
	<p><strong>Settting saved</strong></p>
</div>
<?php } ?>					
<form name="form1" action="" method="post">
<table border="0" width="100%" bgcolor="#f1f1f1" style="border:1px solid #e5e5e5">
  
  <tr >
    <td colspan="2" style="padding:4px 4px 4px 4px;background-color:#f1f1f1; color:#0066CC"><strong>  Ajax Post Listing Configuration &gt;&gt;</strong></td>
  </tr>
  <tr>
    <td width="31%" style="padding:3px 3px 3px 3px; background-color:#fff"><strong>Select Categories :</strong></td>
    <td width="69%" style="padding:3px 3px 3px 3px; background-color:#fff">
      	<?php
		$query_cat = "SELECT
						a.name,
						b.term_id
					FROM 
						wp_terms a
					INNER JOIN wp_term_taxonomy b ON(a.term_id=b.term_id)
					WHERE
						b.taxonomy='category'
						AND b.parent=0
					ORDER BY 
						b.term_id ASC";		
		$sql_cat   = mysql_query($query_cat);
		
		$exclude_vals = explode(",",$mbp_apl_categories);
		foreach($exclude_vals as $key=>$val) {
			$arr_exclude[] = $val;
		}
		?> 
		<select name="categories[]" multiple="">
		<?php while($rs_cat = mysql_fetch_array($sql_cat)) { 
			$sel = (in_array($rs_cat['term_id'], $arr_exclude)) ? ' selected="selected"':'';
		?>
			<option <?php echo $sel;?> value="<?php echo $rs_cat['term_id'];?>"><?php echo $rs_cat['name'];?></option>
		<?php } ?>
		</select>	</td>
  </tr>
  
  <tr>
    <td style="padding:3px 3px 3px 3px; background-color:#fff"><strong>Categories Order By :</strong></td>
    <td style="padding:3px 3px 3px 3px; background-color:#fff"><span style="padding:3px 3px 3px 3px; background-color:#fff">
      
      <select name="category_orderby" id="category_orderby">
        <option <?php if(get_option('mbp_apl_category_orderby') == 'ID') { echo 'selected';}?> value="ID">ID</option>
        <option <?php if(get_option('mbp_apl_category_orderby') == 'name') { echo 'selected';}?> value="name">Name</option>
      </select>
     
      <select name="category_order" id="category_order">
        <option <?php if(get_option('mbp_apl_category_order') == 'ASC') { echo 'selected';}?> value="ASC">ASC</option>
        <option <?php if(get_option('mbp_apl_category_order') == 'DESC') { echo 'selected';}?> value="DESC">DESC</option>
	  </select>     </td>
  </tr>
  <tr>
    <td style="padding:3px 3px 3px 3px; background-color:#fff"><strong>Post Order By Date:</strong> </td>
    <td style="padding:3px 3px 3px 3px; background-color:#fff">
 
      <select name="post_order" id="post_order">
        <option <?php if(get_option('mbp_apl_post_order') == 'ASC') { echo 'selected';}?> value="ASC">ASC</option>
        <option <?php if(get_option('mbp_apl_post_order') == 'DESC') { echo 'selected';}?> value="DESC">DESC</option>
      	<option <?php if(get_option('mbp_apl_post_order') == 'Random') { echo 'selected';}?> value="Random">Random</option>
	  </select>    </td>
  </tr>
  
  <tr>
    <td style="padding:3px 3px 3px 3px; background-color:#fff"><strong>Post Limit :</strong></td>
    <td style="padding:3px 3px 3px 3px; background-color:#fff"><select name="post_limit" id="post_limit">
      <option <?php if(get_option('mbp_apl_post_limit') == '5') { echo 'selected';}?> value="5">5</option>
      <option <?php if(get_option('mbp_apl_post_limit') == '10') { echo 'selected';}?> value="10">10</option>
      <option <?php if(get_option('mbp_apl_post_limit') == '15') { echo 'selected';}?> value="15">15</option>
      <option <?php if(get_option('mbp_apl_post_limit') == '20') { echo 'selected';}?> value="20">20</option>
      <option <?php if(get_option('mbp_apl_post_limit') == '25') { echo 'selected';}?> value="25">25</option>
      <option <?php if(get_option('mbp_apl_post_limit') == '30') { echo 'selected';}?> value="30">30</option>
    </select></td>
  </tr>
  <tr>
    <td style="padding:3px 3px 3px 3px; background-color:#fff"><strong>Show in single post: </strong></td>
    <td style="padding:3px 3px 3px 3px; background-color:#fff"><input name="show_single_post" <?php if (get_option('mbp_apl_show_single_post') == 1) { echo 'checked';}?> type="checkbox" id="show_single_post" value="1" /></td>
  </tr>
  <tr>
    <td style="padding:3px 3px 3px 3px; background-color:#fff"><strong>Show comment count in post:</strong></td>
    <td style="padding:3px 3px 3px 3px; background-color:#fff">
      <input name="show_comment" <?php if (get_option('mbp_apl_show_comment') == 1) { echo 'checked';}?> type="checkbox" id="show_comment" value="1" />   </td>
  </tr>  
  
  <tr>
    <td colspan="2" style="padding:3px 3px 3px 3px; background-color:#f1f1f1">
	<input name="Submit" type="submit" class="button" id="Submit" value="Save Configuration" /></td>
  </tr>
</table>
</form>

<script type="text/javascript">
function __ShowHide(curr, img, path) {
	var curr = document.getElementById(curr);
	if ( img != '' ) {
		var img  = document.getElementById(img);
	}
	var showRow = 'block'
	if ( navigator.appName.indexOf('Microsoft') == -1 && curr.tagName == 'TR' ) {
		var showRow = 'table-row';
	}
	if ( curr.style == '' || curr.style.display == 'none' ) {
		curr.style.display = showRow;
		if ( img != '' ) img.src = path + 'image/minus.gif';
	} else if ( curr.style != '' || curr.style.display == 'block' || curr.style.display == 'table-row' ) {
		curr.style.display = 'none';
		if ( img != '' ) img.src = path + 'image/plus.gif';
	}
}
</script>
	<b><img src="<?php echo MBP_AIT_LIBPATH?>image/plus.gif" id="rep_img3" border="0" /><a style="cursor:hand;cursor:pointer" onclick="__ShowHide('div3','rep_img3','<?php echo MBP_AIT_LIBPATH ?>')">Powered Option:</a></b><br> 

<div id="div3" style="display:none" >

<form action="" method="post">
    <table border="0" width="100%" bgcolor="#f1f1f1" style="border:1px solid #e5e5e5">
     <tr >
		<td style="padding:3px 3px 3px 3px; background-color:#fff">
<input name="apl_pwdby" type="checkbox" value="apl_pwdby" <?php echo $apl_pwdby; ?> /> &nbsp;Remove "powered by <?php echo MBP_APL_NAME; ?>"&nbsp; <br>
		</td>
	</tr>
	
<tr>
<td style="padding:3px 3px 3px 3px; background-color:#f1f1f1">
<input name="submit" type="Submit" value="Remove"  class="button" />
</td>
</tr>
	</table>
</form>
</div>

<br/><br/>
<div align="center" style="background-color:#f1f1f1; padding:5px 0px 5px 0px" >
<p align="center"><strong><?php echo MBP_APL_NAME.' '.MBP_APL_VERSION; ?> by <a href="http://www.maxblogpress.com" target="_blank">MaxBlogPress</a></strong></p>
<p align="center">This plugin is the result of <a href="http://www.maxblogpress.com/blog/219/maxblogpress-revived/" target="_blank">MaxBlogPress Revived</a> project.</p>
</div>
</div><!-- end wrap -->
<?php	
	}
}
// Srart Registration.

/**
 * Plugin registration form
 */
function mbp_aplRegistrationForm($form_name, $submit_btn_txt='Register', $name, $email, $hide=0, $submit_again='') {
	$wp_url = get_bloginfo('wpurl');
	$wp_url = (strpos($wp_url,'http://') === false) ? get_bloginfo('siteurl') : $wp_url;
	$plugin_pg    = 'options-general.php';
	$thankyou_url = $wp_url.'/wp-admin/'.$plugin_pg.'?page='.$_GET['page'];
	$onlist_url   = $wp_url.'/wp-admin/'.$plugin_pg.'?page='.$_GET['page'].'&amp;mbp_onlist=1';
	if ( $hide == 1 ) $align_tbl = 'left';
	else $align_tbl = 'center';
	?>
	
	<?php if ( $submit_again != 1 ) { ?>
	<script><!--
	function trim(str){
		var n = str;
		while ( n.length>0 && n.charAt(0)==' ' ) 
			n = n.substring(1,n.length);
		while( n.length>0 && n.charAt(n.length-1)==' ' )	
			n = n.substring(0,n.length-1);
		return n;
	}
	function mbp_aplValidateForm_0() {
		var name = document.<?php echo $form_name;?>.name;
		var email = document.<?php echo $form_name;?>.from;
		var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
		var err = ''
		if ( trim(name.value) == '' )
			err += '- Name Required\n';
		if ( reg.test(email.value) == false )
			err += '- Valid Email Required\n';
		if ( err != '' ) {
			alert(err);
			return false;
		}
		return true;
	}
	//-->
	</script>
	<?php } ?>
	<table align="<?php echo $align_tbl;?>">
	<form name="<?php echo $form_name;?>" method="post" action="http://www.aweber.com/scripts/addlead.pl" <?php if($submit_again!=1){;?>onsubmit="return mbp_aplValidateForm_0()"<?php }?>>
	 <input type="hidden" name="unit" value="maxbp-activate">
	 <input type="hidden" name="redirect" value="<?php echo $thankyou_url;?>">
	 <input type="hidden" name="meta_redirect_onlist" value="<?php echo $onlist_url;?>">
	 <input type="hidden" name="meta_adtracking" value="mr-links-manage-widget">
	 <input type="hidden" name="meta_message" value="1">
	 <input type="hidden" name="meta_required" value="from,name">
	 <input type="hidden" name="meta_forward_vars" value="1">	
	 <?php if ( $submit_again == 1 ) { ?> 	
	 <input type="hidden" name="submit_again" value="1">
	 <?php } ?>		 
	 <?php if ( $hide == 1 ) { ?> 
	 <input type="hidden" name="name" value="<?php echo $name;?>">
	 <input type="hidden" name="from" value="<?php echo $email;?>">
	 <?php } else { ?>
	 <tr><td>Name: </td><td><input type="text" name="name" value="<?php echo $name;?>" size="25" maxlength="150" /></td></tr>
	 <tr><td>Email: </td><td><input type="text" name="from" value="<?php echo $email;?>" size="25" maxlength="150" /></td></tr>
	 <?php } ?>
	 <tr><td>&nbsp;</td><td><input type="submit" name="submit" value="<?php echo $submit_btn_txt;?>" class="button" /></td></tr>
	 </form>
	</table>
	<?php
}

/**
 * Register Plugin - Step 2
 */
function mbp_aplRegisterStep2($form_name='frm2',$name,$email) {
	$msg = 'You have not clicked on the confirmation link yet. A confirmation email has been sent to you again. Please check your email and click on the confirmation link to activate the plugin.';
	if ( trim($_GET['submit_again']) != '' && $msg != '' ) {
		echo '<div id="message" class="updated fade"><p><strong>'.$msg.'</strong></p></div>';
	}
	?>
	<style type="text/css">
	table, tbody, tfoot, thead {
		padding: 8px;
	}
	tr, th, td {
		padding: 0 8px 0 8px;
	}
	</style>
	<div class="wrap"><h2> <?php echo MBP_APL_NAME.' '.MBP_APL_VERSION; ?></h2>
	 <center>
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:1px solid #e3e3e3; padding: 8px; background-color:#f1f1f1;">
	 <tr><td align="center">
	 <table width="650" cellpadding="5" cellspacing="1" style="border:1px solid #e9e9e9; padding: 8px; background-color:#ffffff; text-align:left;">
	  <tr><td align="center"><h3>Almost Done....</h3></td></tr>
	  <tr><td><h3>Step 1:</h3></td></tr>
	  <tr><td>A confirmation email has been sent to your email "<?php echo $email;?>". You must click on the link inside the email to activate the plugin.</td></tr>
	  <tr><td><strong>The confirmation email will look like:</strong><br /><img src="http://www.maxblogpress.com/images/activate-plugin-email.jpg" vspace="4" border="0" /></td></tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr><td><h3>Step 2:</h3></td></tr>
	  <tr><td>Click on the button below to Verify and Activate the plugin.</td></tr>
	  <tr><td><?php mbp_aplRegistrationForm($form_name.'_0','Verify and Activate',$name,$email,$hide=1,$submit_again=1);?></td></tr>
	 </table>
	 </td></tr></table><br />
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:1px solid #e3e3e3; padding:8px; background-color:#f1f1f1;">
	 <tr><td align="center">
	 <table width="650" cellpadding="5" cellspacing="1" style="border:1px solid #e9e9e9; padding:8px; background-color:#ffffff; text-align:left;">
	   <tr><td><h3>Troubleshooting</h3></td></tr>
	   <tr><td><strong>The confirmation email is not there in my inbox!</strong></td></tr>
	   <tr><td>Dont panic! CHECK THE JUNK, spam or bulk folder of your email.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>It's not there in the junk folder either.</strong></td></tr>
	   <tr><td>Sometimes the confirmation email takes time to arrive. Please be patient. WAIT FOR 6 HOURS AT MOST. The confirmation email should be there by then.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>6 hours and yet no sign of a confirmation email!</strong></td></tr>
	   <tr><td>Please register again from below:</td></tr>
	   <tr><td><?php mbp_aplRegistrationForm($form_name,'Register Again',$name,$email,$hide=0,$submit_again=2);?></td></tr>
	   <tr><td><strong>Help! Still no confirmation email and I have already registered twice</strong></td></tr>
	   <tr><td>Okay, please register again from the form above using a DIFFERENT EMAIL ADDRESS this time.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr>
		 <td><strong>Why am I receiving an error similar to the one shown below?</strong><br />
			 <img src="http://www.maxblogpress.com/images/no-verification-error.jpg" border="0" vspace="8" /><br />
		   You get that kind of error when you click on &quot;Verify and Activate&quot; button or try to register again.<br />
		   <br />
		   This error means that you have already subscribed but have not yet clicked on the link inside confirmation email. In order to  avoid any spam complain we don't send repeated confirmation emails. If you have not recieved the confirmation email then you need to wait for 12 hours at least before requesting another confirmation email. </td>
	   </tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>But I've still got problems.</strong></td></tr>
	   <tr><td>Stay calm. <strong><a href="http://www.maxblogpress.com/contact-us/" target="_blank">Contact us</a></strong> about it and we will get to you ASAP.</td></tr>
	 </table>
	 </td></tr></table>
	 </center>		
	<p style="text-align:center;margin-top:3em;"><strong><?php echo MBP_APL_NAME.' '.MBP_APL_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	</div>
	<?php
}

/**
 * Register Plugin - Step 1
 */
function mbp_aplRegisterStep1($form_name='frm1',$userdata) {
	$name  = trim($userdata->first_name.' '.$userdata->last_name);
	$email = trim($userdata->user_email);
	?>
	<style type="text/css">
	tabled , tbody, tfoot, thead {
		padding: 8px;
	}
	tr, th, td {
		padding: 0 8px 0 8px;
	}
	</style>
	<div class="wrap"><h2> <?php echo MBP_APL_NAME.' '.MBP_APL_VERSION; ?></h2>
	 <center>
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:2px solid #e3e3e3; padding: 8px; background-color:#f1f1f1;">
	  <tr><td align="center">
		<table width="548" align="center" cellpadding="3" cellspacing="1" style="border:1px solid #e9e9e9; padding: 8px; background-color:#ffffff;">
		  <tr><td align="center"><h3>Please register the plugin to activate it. (Registration is free)</h3></td></tr>
		  <tr><td align="left">In addition you'll receive complimentary subscription to MaxBlogPress Newsletter which will give you many tips and tricks to attract lots of visitors to your blog.</td></tr>
		  <tr><td align="center"><strong>Fill the form below to register the plugin:</strong></td></tr>
		  <tr><td align="center"><?php mbp_aplRegistrationForm($form_name,'Register',$name,$email);?></td></tr>
		  <tr><td align="center"><font size="1">[ Your contact information will be handled with the strictest confidence <br />and will never be sold or shared with third parties ]</font></td></tr>
		</table>
	  </td></tr></table>
	 </center>
	<p style="text-align:center;margin-top:3em;"><strong><?php echo MBP_APL_NAME.' '.MBP_APL_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	</div>
	<?php
}	
?>