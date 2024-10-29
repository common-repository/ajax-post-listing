<?php @include('../../../wp-config.php'); ?>
<script type="text/javascript" src="<?php echo MBP_APL_LIBPATH?>script.js">
</script>
<?php
echo "<link type='text/css' rel='stylesheet' href='". MBP_APL_LIBPATH ."style.css'/>";
	global $table_prefix;
	$category_order 	= (get_option('mbp_apl_category_order') == '')?'ASC':get_option('mbp_apl_category_order');
	$category_orderby 	= (get_option('mbp_apl_category_orderby') == 'ID')?'a.term_id' : 'a.name';
	$post_order 		= (get_option('mbp_apl_post_order') == 'Random')?"rand()" : "post_date " . get_option('mbp_apl_post_order');
	$post_limit 		= (get_option('mbp_apl_post_limit') == '')?5:get_option('mbp_apl_post_limit');
	$categories			= get_option('mbp_apl_categories');
	$show_comment		= get_option('mbp_apl_show_comment');
	
	//getting total categories
	if ($categories != '') {
		$query_total_cat = "SELECT
								a.name as category_name,
								b.term_id
							 FROM 
									". $table_prefix ."terms a
							 INNER JOIN ". $table_prefix ."term_taxonomy b ON(a.term_id=b.term_id)
							 WHERE
								b.taxonomy='category'
								AND b.count > 0
								AND b.term_id IN(". $categories . ")";
	} else {
		$query_total_cat = "SELECT
								a.name as category_name,
								b.term_id
							 FROM 
									". $table_prefix ."terms a
							 INNER JOIN ". $table_prefix ."term_taxonomy b ON(a.term_id=b.term_id)
							 WHERE
								b.taxonomy='category'
								AND b.count > 0
								";		
	}
	$sql_total_cat	= mysql_query($query_total_cat);
	$no_total_cat	= mysql_num_rows($sql_total_cat);					
	$start			= $_GET['start'];
	
	if ($start == $no_total_cat - 1) {
		$next = 0;
	} else {
		$next = $start + 1;
	}
	
	if ($start == 0) {
		$prev = $no_total_cat - 1;
	} else {
		$prev = $start - 1;
	}
	
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
	}
	$sql  = mysql_query($query);
	$rs   = mysql_fetch_array($sql);
if (mysql_num_rows($sql) > 0) {						
?>
		<div class="prev_next">
			<a href="#" id="prev_cat"><img alt="Previous Category" title="Previous Category" border="0" src="<?php echo MBP_APL_LIBPATH?>images/pre-bttn.png" /></a>
			<a href="#" id="next_cat"><img alt="Next Category" title="Previous Category" border="0" src="<?php echo MBP_APL_LIBPATH?>images/next-bttn.png" /></a>
		</div>	
<div id="result">		
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
	
<?php } ?>
		<div>
			<?php echo mbp_apl_powered_by();?>
		</div>
		<form>
			<input type="hidden" name="path" id="path" value="<?php echo MBP_APL_LIBPATH;?>" />
			<input type="hidden" name="next_value" id="next_value" value="<?php echo $next;?>" />
			<input type="hidden" name="prev_value" id="prev_value" value="<?php echo $prev;?>" />
		</form>
</div>