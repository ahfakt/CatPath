<?php
/*
Plugin Name: CatPath
Plugin URI: http://ahfakt.com
Description: CatPath provides a widget that displays your posts and categories in hierarchical order like file explorers.
Version: 0.1
Author: ahfakt
Author URI: http://ahfakt.com
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: catpath

CatPath is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

CatPath is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with CatPath. If not, see https://www.gnu.org/licenses/gpl-2.0.html
*/

defined('ABSPATH') or die('You are not allowed to call this page directly.');

class CatPath extends WP_Widget{
	static function Get($cat_ID) {
    	$posts = get_posts(
			array(
				'post_type'        => 'post',
    			'post_status'      => 'publish',
    			'posts_per_page'   => -1,
				'category__in'     => array($cat_ID),
				'meta_key'			=> 'cp_Index',
				'orderby'          => 'meta_value_num',
    			'order'            => 'ASC',
    			'suppress_filters' =>  true)
			);
    	$sub_categories = get_categories(
			array(
				'type'             => 'post',
				'parent'           => $cat_ID,
	    		'meta_key'			=> 'cp_Index',
				'orderby'          => 'meta_value_num',
				'order'            => 'ASC',
				'hide_empty'       => 0,
				'hierarchical'     => 0,
				'taxonomy'         => 'category')
			);
		$a = 0; $b = 0;
		$inside = array();
		for($i = 0; $i < (count($posts) + count($sub_categories)); ++$i) {
			if($i == get_post_meta($posts[$a]->ID, 'cp_Index', true) - 1)
				array_push($inside, array(true, // isPost
					$posts[$a]->post_title,// name
					get_permalink($posts[$a++]->ID, false)) // target
				);

			elseif($i == get_term_meta($sub_categories[$b]->term_id, 'cp_Index', true) - 1)
				array_push($inside, array(false, // isPost
					$sub_categories[$b]->name,// name
					$sub_categories[$b++]->term_id) // target
				);
		}

		if($cat_ID) {
			$category = get_term($cat_ID, 'category');
			return array(array(true, $category->name, $cat_ID), $inside);
		}
		return array(array(false, get_option('blogname'), 0), $inside);
	}
	function __construct() {
		parent::__construct('CatPath', 'CatPath',
			array('classname' => 'CatPath',
				'description' => __('CatPath provides a widget that displays your posts and categories in hierarchical order like file explorers.','catpath')),
			array()
			);
	}
	public static function Request() {
		$cat_ID = (int)$_GET['id'];
		if($_GET['cp_up'] == true) {
			$category = get_term($cat_ID, 'category');
			$cat_ID = $category->parent;
		}
		echo json_encode(self::Get($cat_ID));
		die();
	}
	static $Theme = 'default';
	function widget($args, $instance) {
		wp_register_script('cp-client', plugin_dir_url(__FILE__).'scripts/client.js');
		wp_register_style('cp-client', plugin_dir_url(__FILE__).'styles/'.self::$Theme.'/style.css');
		wp_enqueue_script('jquery');
		wp_enqueue_script('cp-client');
		wp_enqueue_style('cp-client');
		echo $args['before_widget'].'<ul class="cp_top"></ul><ul class="cp_in"></ul>';
		$cat_ID = 0;
		if(is_single()) {
			$category = get_the_category();
			$cat_ID = (int)$category[0]->term_id;
		}
		?><script type="text/javascript"> var cp_cache = <?php echo json_encode(self::Get($cat_ID)); ?>;</script><?php
		echo $args['after_widget'];
	}
	function form($instance) {
	}
	function update($new_instance, $old_instance) {
	}
	static $PostField = array(
		'name' => 'cp_PostIndex',
		'desc' => '',
		'id' => 'cp_PostIndex',
		'type' => 'text'
	);
	public static function EditPost() {
		add_meta_box(
				'cp_PostIndex', // id
				__('Index in parent category','catpath'), // title
				function() {
					global $post;
			    	// Use nonce for verification
			    	echo '<input type="hidden" name="cp_unique" value="'.wp_create_nonce(basename(__FILE__)).'" />';

			    	echo '<input type="'.self::$PostField['type'].'" name="'.self::$PostField['name'].'" id="'.self::$PostField['id'].'" value="'.get_post_meta($post->ID, 'cp_Index', true).'" size="30" style="width:97%" /><br />'.self::$PostField['desc'];
					echo '<ul class="cp_apin"></ul>';
				},
				'post', // page type
				'side', // context
				'high' // priority
			);
		
	}
	public static function OnSavePost($post_id) {
		if('post' == $_POST['post_type']) {
			if (!wp_verify_nonce($_POST['cp_unique'], basename(__FILE__)))
	        return $post_id;

	    	// check autosave
	    	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
	        	return $post_id;

	    	// check permissions
		    if (!current_user_can('edit_post', $post_id))
				return $post_id;
			update_post_meta($post_id, 'cp_Index', $_POST[self::$PostField['name']]);
		}
	}
	static $CategoryField = array(
		'name' => 'cp_PostIndex',
		'title' => 'Index in parent category',
		'desc' => '',
		'id' => 'cp_PostIndex',
		'type' => 'text'
	);
	public static function CategoryAddFormFields($tag) {
		echo '<div class="form-field"><label>'.__(self::$CategoryField['title'],'catpath').'</label>';
		echo '<input type="'.self::$CategoryField['type'].'" name="'.self::$CategoryField['name'].'" id="'.self::$CategoryField['id'].'" size="3" style="width:95%;" value="0"><br />';
		echo '<span class="description">'.self::$CategoryField['desc'].'</span></div>';
	}
	public static function OnCreateCategory($term_id) {
		add_term_meta($term_id, 'cp_Index', $_POST[self::$CategoryField['name']]);
	}
	public static function EditCategoryFormFields($tag) {
	    echo '<tr class="form-field"><th scope="row"><label>'.__(self::$CategoryField['title'],'catpath').'</label></th>';
		echo '<td><input type="'.self::$CategoryField['type'].'" name="'.self::$CategoryField['name'].'" id="'.self::$CategoryField['id'].'" size="3" style="width:95%;" value="'.get_term_meta($tag->term_id, 'cp_Index', true).'"><br />';
		echo '<span class="description">'.self::$CategoryField['desc'].'</span></td></tr>';
	}
	public static function OnSaveCategory($term_id) {
		update_term_meta($term_id, 'cp_Index', $_POST[self::$CategoryField['name']]);
	}
}
add_action('widgets_init', function(){
	register_widget('CatPath');
});
//ajax
add_action('wp_ajax_nopriv_cp_req', 'CatPath::Request');
add_action('wp_ajax_cp_req', 'CatPath::Request');
// Edit post
add_action('admin_head-post.php', 'CatPath::EditPost');
add_action('save_post', 'CatPath::OnSavePost');
// Create category
add_action('category_add_form_fields', 'CatPath::CategoryAddFormFields');
add_action('create_category', 'CatPath::OnCreateCategory');
// Edit Category
add_action('edit_category_form_fields', 'CatPath::EditCategoryFormFields');
add_action('edited_category', 'CatPath::OnSaveCategory');

add_action('init', function(){
	load_plugin_textdomain('catpath', null, basename(dirname(__FILE__)).'/languages');
});
