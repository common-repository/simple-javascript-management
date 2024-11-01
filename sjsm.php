<?php
/*	
Plugin Name: Simple JavaScript management
Plugin URI: http://www.efoo.se/project/wordpress/simple-javascript-management/
Description: Simple JavaScript management
Version: 1.2
Author: Fredrik Forsmo
Author URI: http://www.efoo.se
License: GPL2
*/

	$sjsmClass = new sjsm;
	global $wpdb;		
	class sjsm {
		
		function __construct() {
			add_action('init', array($this, 'init'));
		}
		
		function init() {
			add_action('admin_menu', array($this, 'admin_menu'));
			add_action('wp_head', array($this, 'handle_head_js'));
			add_action('wp_footer', array($this, 'handle_footer_js'));
			if(!empty($_GET['action'])) {
				$action = $_GET['action'];
			} else {
				$action = '';
			}
			
			if($action == 'remove') {
				$this->sjsm_remove();
			} else {
				$this->sjsm_install();				
			}	
		}
		
		function sjsm_install() {
			global $wpdb;
		   	$sjsm_table = $wpdb->prefix . "sjsm";
		    if($wpdb->get_var("show tables like '$sjsm_table'") != $sjsm_table) {

		      	$sql = "CREATE TABLE " . $sjsm_table . " (
			  		id int(1) NOT NULL AUTO_INCREMENT,
			  		name longtext NOT NULL,
			  		url longtext NOT NULL,
			  		active int(1) NOT NULL,
					loadorder int(1) NOT NULL,
					page text NOT NULL,
					loadwhere text NOT NULL,
			  		UNIQUE KEY id (id)
					);";

		    	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		    	dbDelta($sql);
			}
			
		}
		
		function sjsm_remove() {
			global $wpdb;
			$id = $_GET['id'];
			$sjsm_table = $wpdb->prefix . "sjsm";
			$wpdb->query("DELETE FROM $sjsm_table WHERE id = '$id'");
		}
		
		function admin_menu() {
			add_menu_page('Simple JavaScript management', 'Simple JavaScript management', 'manage_options', 'sjsm_start', array($this, 'sjsm_options_page'));
			add_submenu_page( 'sjsm_start', 'Simple JavaScript management add', 'Add', 'manage_options', 'sjsm_add', array($this, 'sjsm_add_page'));
			add_submenu_page( 'sjsm_start', 'Simple JavaScript management edit', 'Edit', 'manage_options', 'sjsm_edit', array($this, 'sjsm_edit_page'));
		}
		
		
		function handle_head_js() {
			$this->add_to_page('head');
		}
		
		function handle_footer_js() {
			$this->add_to_page('body');
		}
		
		function add_to_page($where) {
			global $wpdb;
			$sjsm_table = $wpdb->prefix . "sjsm";
			$sjsmdb = $wpdb->get_results("SELECT id, url, loadorder, page, loadwhere FROM $sjsm_table WHERE active = '1'", ARRAY_A);
			$s = $sjsmdb;
			$c = count($sjsmdb);
			for($b = 0; $b < $c; $b++) {
				$this->add_js('all', $s[$b], $where);
				
				if( is_paged() ) {
					$this->add_js('paged', $s[$b], $where);
				} else if( is_home() ) {
					$this->add_js('home', $s[$b], $where);
				} else if( is_search() ) {
					$this->add_js('search', $s[$b], $where);
				} else if( is_404() ) {
					$this->add_js('404', $s[$b], $where);
				} else if( is_front_page() ) {
					$this->add_js('frontpage', $s[$b], $where);
				} else if( is_single() ) {
					$this->add_js('single', $s[$b], $where);
				} else if( is_category() ) {
					$this->add_js('category', $s[$b], $where);
				} else if( is_author() ) { 
					$this->add_js('author', $s[$b], $where);
				} else if( is_day() ) {
					$this->add_js('day', $s[$b], $where);
				} else if( is_month() ) { 
					$this->add_js('month', $s[$b], $where);
				} else if( is_year() ) { 	
					$this->add_js('year', $s[$b], $where);
				} else if ( is_archive() ) {
					$this->add_js('archive', $s[$b], $where);
				}
			}	
		}	
		
		function add_js($page, $sjsm, $where) {
			$c = count($sjsm);	
			for($b = 0; $b < $c; $b++) {
				if($sjsm['loadorder'] == $b && $sjsm['loadwhere'] == $where && $sjsm['page'] == $page) {
					echo '<script type="text/javascript" src="'.$sjsm['url'].'"></script>';
					echo "\n";
				}
			}			
		}

		
		function sjsm_add_page() {
			global $wpdb;
			
			echo '<div class="sjsm-options">';
			echo '<h1>Simple JavaScript management</h1>';
			if($_POST) {
				$name = $_POST['name'];
				$url = $_POST['url'];
				$check = $_POST['check'];
				$atp = $_POST['atp'];
				$wtl = $_POST['loadwhere'];
				$active = null;
				if($check == 'on') {
					$active = 1;
				} else {
					$active = 0;
				}

				$sjsm_table = $wpdb->prefix . "sjsm";
				$add = $wpdb->query("INSERT INTO $sjsm_table(name, url, active, page, loadwhere) VALUES('$name', '$url', '$active', '$atp', '$wtl')");
				
				echo '<p>'.$name.' was added to list. <a href="?page=sjsm_start">Back to list</a></p>';				
			} else {
				echo '<p>Add new JavaScript file</p>';
				echo '<form action="admin.php?page=sjsm_add" method="post">';
				echo '<p>Script Name <input name="name" type="text" /></p>';
				echo '<p>Script Url <input name="url" type="text" /> </p>';		
				echo '<p>Add to site? <input type="checkbox" name="check" /> </p>';
				echo '<p>On with page? 
					<select name="atp">
						<option value="all">All</option>
						<option value="home">Home</option>
						<option value="page">Page</option>
						<option value="single">Single</option>
						<option value="category">Category</option>
						<option value="frontpage">Frontpage</option>
						<option value="search">Search</option>
						<option value="author">Author</option>
						<option value="archive">Archive</option>
						<option value="year">Year</option>
						<option value="month">Month</option>
						<option value="day">Day</option>
						<option value="404">404</option>
					</select>
					</p>';
				echo '<p>Where to load? <select name="loadwhere"><option value="head">Header (before &lt;/head&gt;)</option><option value="body">Footer (before &lt;/body&gt;)</option></select>';
				echo '<p><input type="submit" value="Add" /></p>';
				echo '</form>';			
			}
			echo '</div>';
		}
		

		
		function sjsm_edit_page() {
			global $wpdb;
			$sjsm_table = $wpdb->prefix . "sjsm";
			$id = $_GET['id'];
			echo '<div class="sjsm-options">';
			echo '<h1>Simple JavaScript management</h1>';
			if($_POST && $id != null) { 
				$name = $_POST['name'];
				$url = $_POST['url'];
				$check = $_POST['check'];
				$atp = $_POST['atp'];
				$wtl = $_POST['loadwhere'];
				$active = null;
				if($check == 'on') {
					$active = 1;
				} else {
					$active = 0;
				}
				
				$wpdb->update($sjsm_table, array('name' => $name, 'url' => $url, 'active' => $active, 'page' => $atp, 'loadwhere' => $wtl), array('id' => $id));
				echo '<p>Updated. <a href="?page=sjsm_start">Back to list</a></p>';
				
			} else {
				if($id != null) {
					$sjsmdb = $wpdb->get_results("SELECT url, name, active, loadwhere, page FROM $sjsm_table WHERE id = '$id'", ARRAY_A);	
					foreach($sjsmdb as $jsFile) {
						echo '<p>Edit JavaScript file</p>';
						echo '<form action="admin.php?page=sjsm_edit&id='.$id.'" method="post">';
						echo '<p>Name: <input name="name" type="text" value="'.$jsFile['name'].'" /></p>';
						echo '<p>Url: <input name="url" type="text" value="'.$jsFile['url'].'" /> </p>';
						if($jsFile['active'] == 1) {
							echo '<p>Add to site: <input name="check" type="checkbox" checked="checked" /></p>'; 
						} else {
							echo '<p>Add to site: <input name="check" type="checkbox" /></p>';
						}	
						$pageArr = array('paged', 'home', 'page', 'search', '404', 'frontpage', 'single', 'category', 'author', 'day', 'month', 'year', 'archive', 'all');							
						echo '<p>On with page? <select name="atp">';
						foreach($pageArr as $p) {
							if($jsFile['page'] == $p) {
								echo '<option value="'.$p.'" selected="selected">'.ucfirst($p).'</option>';
							} else {
								echo '<option value="'.$p.'">'.ucfirst($p).'</option>';
							}
						}
						echo'</select> </p>';
						echo '<p>Where to load? <select name="loadwhere">';
						
						if($jsFile['loadwhere'] == 'head') {
							echo '<option value="head" selected="selected">Header (before &lt;/head&gt;)</option>';
							echo '<option value="body">Footer (before &lt;/body&gt;)</option>';
						} else if($jsFile['loadwhere'] == 'body') {	
							echo '<option value="head">Header (before &lt;/head&gt;)</option>';
							echo '<option value="body" selected="selected">Footer (before &lt;/body&gt;)</option>';
						}
						echo '</select></p>';						
						echo '<p><input type="submit" value="Save Changes" /></p>';
						echo '</form>';
					} 
				} else {
					echo '<p>Please click edit link next to the JavaScript file you want to edit</p>';
				} 
			}
			echo '</div>';			
		}
		
		function add_option_to_select($id, $p) {
			global $wpdb;		
			$sjsm_table = $wpdb->prefix . "sjsm";
			$sjsmdb = $wpdb->get_var($wpdb->prepare("SELECT loadorder FROM $sjsm_table WHERE active = '1' AND id = '$id' AND page = '$p'"));	
			$c = count($wpdb->get_results("SELECT * FROM $sjsm_table WHERE active = '1' AND page = '$p'", ARRAY_A));
			$all = count($wpdb->get_results("SELECT * FROM $sjsm_table WHERE active = '1' AND page = 'all'", ARRAY_A));
			if($p != 'all') {
				$c = $c + $all;	
			}
			echo '<select name="order[]">';
			for($b = 0; $b < $c; $b++) {
				if($p != 'all') {
					if($b < $all) {
					} else {
						if($sjsmdb == $b) {
							echo '<option selected="selected" value="'.$b.'">'.$b.'</option>';	
						} else {
							echo '<option value="'.$b.'">'.$b.'</option>';
						}
					}
				} else {
					if($sjsmdb == $b) {
						echo '<option selected="selected" value="'.$b.'">'.$b.'</option>';	
					} else {
						echo '<option value="'.$b.'">'.$b.'</option>';
					}					
				}
			}
			echo '</select>';
		}
		
		function add_to_right_list() {
			global $wpdb;	
			$sjsm_table = $wpdb->prefix . "sjsm";		
			$pageArr = array('paged', 'home', 'page', 'search', '404', 'frontpage', 'single', 'category', 'author', 'day', 'month', 'year', 'archive', 'all');
		 	sort($pageArr);
			foreach($pageArr as $p) {
				$sjsmdb = $wpdb->get_results("SELECT id, url, name, active, loadorder, loadwhere, page FROM $sjsm_table WHERE page = '$p'", ARRAY_A);
				$s = count($sjsmdb);
				if($s > 0) {
					echo '<li><h3>'.ucfirst($p).'</h3><ul style="margin-left:20px;">';
					foreach($sjsmdb as $jsFile) {
						if($jsFile['active'] == 1) {
							echo '<li id="sjsm-item-'.$jsFile['id'].'>';
							echo '<a href="'.$jsFile["url"].'">'.$jsFile['name'].'</a> - <a href="?page=sjsm_edit&id='.$jsFile['id'].'">(edit)</a> - <a class="sjsm-remove-item" href="?page=sjsm_start&action=remove&id='.$jsFile['id'].'">(remove)</a> - ';
							$this->add_option_to_select($jsFile['id'], $p);
							echo '<input type="hidden" name="hiddenid[]" value="'.$jsFile['id'].'" /> - '.$jsFile['loadwhere'].'</li>';

						} else {
							echo '<li id="sjsm-item-'.$jsFile['id'].'"><a href="'.$jsFile["url"].'">'.$jsFile['name'].'</a> - <a href="?page=sjsm_edit&id='.$jsFile['id'].'">(edit)</a> - <a class="sjsm-remove-item" href="?page=sjsm_start&action=remove&id='.$jsFile['id'].'">(remove)</a><input type="hidden" name="hiddenid[]" value="'.$jsFile['id'].'" /></li>';
						}
					}
					
					echo '</ul></li>';
				}
			}			
		}
		
	
		function sjsm_options_page() {
			global $wpdb;	
			$sjsm_table = $wpdb->prefix . "sjsm";
			if($_POST) {
				$order = $_POST['order'];
				$id = $_POST['hiddenid'];
				$count = count($order);
				$od = null;
				for($b = 0; $b < $count; $b++) {
			
					if($order[$b] == null) {
						$od = 0;
					} else {
						$od = $order[$b];
					}
					$wpdb->update($sjsm_table, array('loadorder' => $od), array('id' => $id[$b]));
				}
			}

			echo '<div class="sjsm-page">';
			echo '<h1>Simple JavaScript management</h1>';
			echo '<p>Management your JavaScript files easy</p>';
			echo '<p>Your WordPress site have this JavaScripts files included allready.</p>';
			echo '<div class="sjsm-content">';
			echo '<form action="admin.php?page=sjsm_start" method="post">';
			echo '<ul class="sjsm-list">';
			echo '<li><p>Name - options - load order (0 is first) - Load where</p>';
			$this->add_to_right_list();
			echo '</ul>';
			echo '<p><input type="submit" value="Update" /></p>';
			$this->sjsm_usage();
			echo '</form>';
			echo '</div>';
			echo '</div>';
		}
		
		function sjsm_usage() {
			echo '<h3>Wait what?</h3>';
			echo '<p>The "all page" is an option that allows you to load all you JavaScripts on all the pages. The other "page" option are different WordPress pages. </p>';
			echo '<p>You will find that you get more numbers in your "load order" in the others "page" option. Note that the JavaScript files in the "all page" option will be loaded before any of the other "page" options.
			</p>';
		}
	}
?>