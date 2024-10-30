<?php
/* 
 * Plugin Name:   Insert Query As Tag
 * Version:       0.0.1
 * Plugin URI:    http://www.ainging.com
 * Description:   When a visitor coming from search engine, this plugin will retrieve query and insert it as tag of the post which the visitor landed on.
 * Author:        Jesse
 * Author URI:    http://www.ainging.com
 */

add_action('admin_menu', 'iqat_add_admin_pages');
add_action('wp_head', 'insert_query_as_tag');
register_activation_hook(__FILE__,'iqat_install');
//register_deactivation_hook(__FILE__,'iqat_uninstall');
function iqat_install(){

	if(!get_option('IQAT')){
	
		$options=array();
		$options['ignore_query']='';
		update_option('IQAT',$options);
	}

}
function iqat_uninstall(){
	delete_option('IQAT');

}
function iqat_add_admin_pages(){

	add_options_page('Insert Query As Tag', 'Insert Query As Tag', 10, __FILE__, 'iqat_main');
	if( isset($_POST['iqat_submit']) && ($_POST['iqat_update'] =='iqat_update') && !empty($_POST['ignore_query'])){
		$options=get_option('IQAT');
		$options['ignore_query']=$_POST['ignore_query'];
		update_option('IQAT',$options);
	}
	if( isset($_POST['iqat_reset']) && ($_POST['iqat_update'] =='iqat_update') ){
		iqat_uninstall();
	}
	

}

function is_search_engine() {
	
	$ref=parse_url($_SERVER['HTTP_REFERER']);
	$ref=$ref['host'];
	$ses[] = "bing.com";
	$ses[] = ".google.";
	$ses[] = ".yahoo.";
	$ses[] = ".ask.";
    
	foreach ($ses as $se) {
		
		if (stripos($ref, $se) !== false)

			return true;
	
	}
   
	return false;
} 

function get_query() {

	$query=parse_url($_SERVER['HTTP_REFERER']);
	$query=strtolower($query['query']);
	if ( strpos($query, "q=") !== false ) {
		$rtn_query= substr($query, strpos($query,"q="));
		$rtn_query= substr($rtn_query, 2);
		if (strpos($rtn_query,"&")) {
			$rtn_query = substr($rtn_query, 0,strpos($rtn_query,"&"));
		}
		if($rtn_query)
			return clean_query(urldecode($rtn_query));
	}
	return false;
     	
	
}
function clean_query($query){
	 if (stripos($query, "site:") !== false) return false;
         if (stripos($query, "related:") !== false) return false;
         if (stripos($query, "cache:") !== false) return false;
         if (stripos($query, "link:") !== false) return false;
	preg_match_all('@([0-9a-zA-Z]+)@i',$query,$m,PREG_PATTERN_ORDER);
	$m=$m[0];
	return implode(" ",$m);
}
function insert_query_as_tag(){
	
	if( !is_single())
		return false;

	if(!get_option('IQAT'))
		iqat_install();

	if(is_search_engine()){

		$query=get_query();
		
		if($query){

			$options=get_option('IQAT');

			$ignore_query=preg_split('/(\r\n|\r|\n)/',$options['ignore_query']);
			
			foreach( $ignore_query as $ig )
				if( $ig === $query)
					return false;

			global $wp_query;

			$id=$wp_query->post->ID;
			$post_link=get_permalink($id);
			wp_add_post_tags($id, $query);
			
			if(!$options[$post_link]){

				$options[$post_link]['link'] = $post_link;
				$options[$post_link]['query']=$query;
				$options[$post_link]['count']=1;

			}else{

				$options[$post_link]['count']+=1;
			}
			
			update_option('IQAT',$options);

			
		}

	}

	return false;


}  
function iqat_show_quries(){

	$options=get_option('IQAT');

	if(!$options)
		return false;

	unset($options['ignore_query']);

	$queries=array();

	foreach($options as $o){

		$queries[]=$o['query'].','.$o['link'].','.$o['count'];
		
		
	}
	
	return implode("\n",$queries);
	

}
function iqat_main(){   
		$options=get_option('IQAT');

		$ignore_query=$options['ignore_query'];


?>

      <div class="wrap">

      <h2>Insert Query As Tag</h2>
	<?php
	 if( isset($_POST['iqat_show']) && ($_POST['iqat_update'] =='iqat_update') ){
		
		echo "<p><span style='color:#FF0000'>format:</span>     query,link,count</p>";
		echo "<pre>";
		print_r(iqat_show_quries());
		echo "<p></p>";
		echo "</pre>";
	   }  
		

	?>
      <form method="post" action="">
      Ignore Query(one query per line):
      <p><textarea name="ignore_query" id="ignore_query" rows="10" cols="30"><?php echo $ignore_query;?></textarea></p>
      <p><input type="hidden" name="iqat_update" value="iqat_update" /></p>
      <p><input type="submit" name="iqat_submit" class="button" value="Submit" /></p>
      <p><input type="submit" name="iqat_show" class="button" value="Show Quries" /></p>
      <p><input type="submit" name="iqat_reset" class="button" value="Reset Database" /></p>
      </form>
   </div>
<?php }?>
