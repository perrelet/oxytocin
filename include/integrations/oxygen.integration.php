<?php

namespace Oxytocin;

use Generator;

class Oxygen extends \Digitalis\Integration {

	protected $templates;

	public static $notes_key = 'oxytocin_notes';

	const INDENT = 	"&nbsp;&nbsp;&nbsp;&nbsp;";
	const NEST = 	"&rdca;";
	const CURRENT = "&#9733;";

    public function condition () {

        return defined("CT_VERSION");

    }

    public function run () {

		add_action('init', function () {

			if (current_user_can('administrator')) {

				// Admin
				add_action('admin_menu', [$this, 'admin_menu'], 99);
				add_action('admin_bar_menu', [$this, 'admin_bar_menu'], PHP_INT_MAX);
		
				// Templates
				
				add_filter('manage_ct_template_posts_columns', [$this, 'ct_custom_views_columns'], 100);
				add_action('manage_ct_template_posts_custom_column' , [$this, 'ct_custom_view_column'], 100, 2 );
				add_action('add_meta_boxes', [$this, 'add_notes_meta_box']);
				add_action('save_post_ct_template', [$this, 'save_template'], 1, 3);
				
				// Pages Table
				
				add_filter('manage_pages_columns', [$this, 'manage_pages_columns']);
				add_action('manage_pages_custom_column', [$this, 'pages_custom_column'], 10, 2);

				// Page

				add_action('add_meta_boxes', [$this, 'dependency_meta_box']);
			
			}
		
		});

    }

    public function admin_menu () {
		
		global $submenu;
		
		$submenu['ct_dashboard_page'][] = [
			'Filter: Templates',
			'manage_options',
			admin_url('edit.php?s&post_type=ct_template&ct_template_type=template')
		];
		
		$submenu['ct_dashboard_page'][] = [
			'Filter: Reusable',
			'manage_options',
			admin_url('edit.php?s&post_type=ct_template&ct_template_type=reusable_part')
		];
		
	}

	public function admin_bar_menu () {

		global $wp_admin_bar, $post;

		if (!$wp_admin_bar->get_node('oxygen_admin_bar_menu')) return;

		$wp_admin_bar->add_menu( [
			'id' => 'oxytocin_oxy_div_1',
			'parent' => 'oxygen_admin_bar_menu',
			'title' => '---',
			'href' => false,
		]);

		$templates = $this->get_recent_templates();

		if ($templates) {

			$wp_admin_bar->add_menu( [
				'id' => 'oxytocin_recent_templates',
				'parent' => 'oxygen_admin_bar_menu',
				'title' => 'Recent Templates',
				'href' => false,
			]);

			foreach ($templates as $template) {

				//Genealogist::check_inner_content($template);

				/* // We cant link directly to the builder as oxygen would require us to check each posts shortcodes for a ct_inner_content block. 
	
				$wp_admin_bar->add_menu( [
					'id' => 'oxytocin_recent_template-' . $template->ID,
					'parent' => 'oxytocin_recent_templates',
					'title' => $template->post_title,
					'href' => get_edit_post_link($template->ID, 'raw'),
				]); */

				$this->admin_menu_oxygen_link(
					'oxytocin_recent_template-' . $template->ID,
					'oxytocin_recent_templates',
					$template
				);
	
			}

		}

		if ($tree = Genealogist::get_tree($post->ID)) {

			$wp_admin_bar->add_menu( [
				'id' => 'oxytocin_tree',
				'parent' => 'oxygen_admin_bar_menu',
				'title' => 'Template Inheritance',
				'href' => false,
			]);

			$this->admin_menu_tree($tree->get_tree());

		}

		if ($reusable = Genealogist::get_reusable_parts($post, false)) {

			$wp_admin_bar->add_menu( [
				'id' => 'oxytocin_reusable_parts',
				'parent' => 'oxygen_admin_bar_menu',
				'title' => 'Child Reusable Parts',
				'href' => false,
			]);	

			foreach ($reusable as $i => $part) {

				$this->admin_menu_oxygen_link(
					'oxytocin_reusable_part-' . $i,
					'oxytocin_reusable_parts',
					$part
				);

			}

		}

		$wp_admin_bar->add_menu( [
			'id' => 'oxytocin_oxy_div_2',
			'parent' => 'oxygen_admin_bar_menu',
			'title' => '---',
			'href' => false,
		]);

		$wp_admin_bar->add_menu( [
			'id' => 'oxytocin_templates',
			'parent' => 'oxygen_admin_bar_menu',
			'title' => 'All Templates',
			'href' => admin_url('edit.php?s&post_type=ct_template&ct_template_type=template'),
		]);

		$wp_admin_bar->add_menu( [
			'id' => 'oxytocin_reusable',
			'parent' => 'oxygen_admin_bar_menu',
			'title' => 'All Reusable Parts',
			'href' => admin_url('edit.php?s&post_type=ct_template&ct_template_type=reusable_part'),
		]);

	}

	public function admin_menu_tree ($tree, $parent_menu_id = 'oxytocin_tree', $depth = 1, $indent = "") {

		if (property_exists($tree, 'children') && $tree->children) foreach ($tree->children as $i => $post) {

			if ($post->type == 'post') {

				$post_type = get_post_type_object($post->post_type);
				$type = $post_type->labels->singular_name;
				$symbol = self::CURRENT;
				
			} else {

				if ($post->type == 'template') $type = 'Template';
				if ($post->type == 'reusable') $type = 'Part';
				$symbol = self::NEST;

			}

			if ($post->type == 'section') {

				$this->admin_menu_tree($post, $parent_menu_id, $depth, $indent);

			} else {

				$this->admin_menu_oxygen_link(
					"{$tree->ID}_{$post->ID}_$i",
					$parent_menu_id,
					$post,
					"{$indent}{$symbol} {$depth}. {$post->post_title} ({$type})"
				);

				$this->admin_menu_tree($post, $parent_menu_id, $depth + 1, $indent . self::INDENT);

			}

		}

	}

	public function admin_menu_oxygen_link ($id, $parent_menu_id, $post, $title = null) {

		global $wp_admin_bar;

		if (is_null($title)) $title = $post->post_title;

		$wp_admin_bar->add_menu( [
			'id'		=> $id,
			'parent'	=> $parent_menu_id,
			'title'		=> $title,
			'href'		=> get_edit_post_link($post->ID, 'raw'),
		]);

		if ($url = Genealogist::get_builder_url($post)) {

			$wp_admin_bar->add_menu( [
				'id'		=> $id . "_oxy",
				'parent'	=> $id,
				'title'		=> "Edit with Oxygen",
				'href'		=> $url,
			]);

		}

	}

    public function ct_custom_views_columns ($columns) {
		
		$offset = 2;
		
		$columns = array_merge(
			array_slice($columns, 0, $offset),
			[
				'digitalis_inherits' 	=> 'Inheritance',
				'digitalis_notes' => 'Notes',
			],
			array_slice($columns, $offset, null)
		);
		
		return $columns;
		
	}

	public function ct_custom_view_column ($column, $post_id) {
		
		switch ($column) {
			
			case 'digitalis_inherits':

				$inheritance = Genealogist::get_inheritance($post_id);
				
				if ($inheritance) foreach ($inheritance as $i => $template) {
						
					if ($i > 0) echo " ";
					echo "&lArr; ";
					echo "<a href='" . get_edit_post_link($template->ID, 'raw') . "'>{$template->post_title}</a>";
					
				}

				
				break;
				
			case 'digitalis_notes':
				echo wp_trim_words(get_post_meta($post_id, self::$notes_key, true));
				break;
			
		}
			
	}

	public function manage_pages_columns ($columns) {
		
		$offset = 2;
		
		$columns = array_merge(
			array_slice($columns, 0, $offset),
			[
				'digitalis_template' 	=> 'Template'
			],
			array_slice($columns, $offset, null)
		);
		
		return $columns;
		
		
	}

    public function pages_custom_column ($column, $post_id) {
		
		switch ($column) {
			
			case 'digitalis_template':
				
				$inheritance = Genealogist::get_inheritance($post_id);
				
				echo "<a href='" . get_edit_post_link($post_id, 'raw') . "'>" . get_the_title($post_id) . "</a>";
				
				if ($inheritance) foreach ($inheritance as $i => $template) {
						
					echo " &lArr; <a href='" . get_edit_post_link($template->ID, 'raw') . "'>{$template->post_title}</a>";
				
				}
				
				break;
				
				
		}
		
	}

	public function dependency_meta_box () {

		if (!oxygen_vsb_current_user_can_full_access()) return;

		$screen = get_current_screen();
		if (get_option('oxygen_vsb_ignore_post_type_' . $screen->post_type, false) == "true") return;

		$post_types 	= get_post_types('', 'objects'); 
		$exclude_types 	= ["nav_menu_item", "revision"];

		foreach ($post_types as $post_type) {

			if (!$post_type->public) continue;
			if (in_array($post_type->name, $exclude_types)) continue;

			add_meta_box(
				'oxytocin_dependencies',
				'<span>Oxytocin: ' . __('Template Map', 'digitalis') . ' <sup>1.0</sup></span>',
				[$this, 'render_dependency_meta_box'],
				$post_type->name,
				'advanced',
				'default'
			);

		}

	}

	public function render_dependency_meta_box () {

		$tree = Genealogist::get_tree(get_the_ID());

		if (property_exists($tree, 'children') && count($tree->children) <= 1) {
			
		}

		$chart = new Chart($tree);
		$chart->render();
		
		/* $flat_tree = Genealogist::flatten_tree($tree, [], true);
		dprint($flat_tree);
		return; */

		//$inheritance = Genealogist::get_inheritance($post_clone->ID, true);
		//$reusable = Genealogist::get_reusable_parts($post_clone->ID);
		
		//echo "<script>new_chart(nodes, 'oxytocin-graph', 'dendogram', 'horizontal');</script>";
		
		//dprint($tree);

		/* dprint("<hr>");
		dprint($flat_tree); */
		//dprint("<hr>");
		/* dprint($tree);
		dprint("<hr>");
		dprint($inheritance);
		dprint("<hr>");
		dprint($reusable);  */

	}

    public function add_notes_meta_box () {
	
		add_meta_box(
			'ct_template_digitalis_info',
			'Info',
			[$this, 'render_notes_meta_box'],
			'ct_template',
			'side',
			'default'
		);	
		
	}

    public function render_notes_meta_box () {
		
		global $post;

		wp_nonce_field( basename( __FILE__ ), 'digitalis' );

		$notes = esc_textarea(get_post_meta($post->ID, self::$notes_key, true));
		
		echo "<label>Template Notes:</label>";
		echo "<textarea name='" . self::$notes_key . "' class='widefat'>{$notes}</textarea>";

		
	}

    public function save_template ($post_id, $post, $update) {

		if (!current_user_can('edit_post', $post_id)) return;
		if ('revision' === $post->post_type) return;
	
		if (isset($_POST[self::$notes_key]) && wp_verify_nonce($_POST['digitalis'], basename(__FILE__))) {

			$meta = [];
			$meta[self::$notes_key] = esc_textarea($_POST[self::$notes_key]);
			
			foreach ($meta as $key => $value) {
	
				update_post_meta($post_id, $key, $value);
	
			}

		}

	}

	//
	//
	//

    /* protected function get_templates () {
		
		if (is_null($this->templates)) {

			global $wpdb;

			$this->templates = $wpdb->get_results(
				"SELECT id, post_title
				FROM {$wpdb->posts} as post
				WHERE post_type = 'ct_template'
				AND post.post_status IN ('publish')"
			);

		}
		
		return $this->templates;

	} */

	protected function get_recent_templates ($n = 5) {
		
		return (new \WP_Query([
			'posts_per_page'	=> $n,
			'orderby'			=> 'date',
			'order'				=> 'DESC',
			'post_status'		=> 'publish',
			'post_type'			=> 'ct_template',
		]))->get_posts();

	}

}