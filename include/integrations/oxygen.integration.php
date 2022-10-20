<?php

namespace Oxytocin;

class Oxygen extends \Digitalis\Integration {

	protected $templates;

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
				add_action('add_meta_boxes', [$this, 'add_meta_box']);
				add_action('save_post_ct_template', [$this, 'save_template'], 1, 3);
				
				// Pages
				
				add_filter('manage_pages_columns', [$this, 'manage_pages_columns']);
				add_action('manage_pages_custom_column', [$this, 'pages_custom_column'], 10, 2);
			
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
			'id' => 'oxytocin_oxy_div',
			'parent' => 'oxygen_admin_bar_menu',
			'title' => '---',
			'href' => false,
		]);

		$wp_admin_bar->add_menu( [
			'id' => 'oxytocin_templates',
			'parent' => 'oxygen_admin_bar_menu',
			'title' => 'Templates',
			'href' => admin_url('edit.php?s&post_type=ct_template&ct_template_type=template'),
		]);

		$wp_admin_bar->add_menu( [
			'id' => 'oxytocin_reusable',
			'parent' => 'oxygen_admin_bar_menu',
			'title' => 'Reusable Parts',
			'href' => admin_url('edit.php?s&post_type=ct_template&ct_template_type=reusable_part'),
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

				// We cant link directly to the builder as oxygen would require us to check each posts shortcodes for a ct_inner_content block. 
	
				$wp_admin_bar->add_menu( [
					'id' => 'oxytocin_recent_template-' . $template->id,
					'parent' => 'oxytocin_recent_templates',
					'title' => $template->post_title,
					'href' => get_edit_post_link($template->id),
				]);
	
			}

		}

		$template_id = $this->get_template_id($post->ID);

		if ($template_id) {

			$wp_admin_bar->add_menu( [
				'id' => 'oxytocin_inherited_templates',
				'parent' => 'oxygen_admin_bar_menu',
				'title' => 'Inheritance',
				'href' => false,
			]);		

			$inheritance = $this->get_inheritance($template_id);
			$inheritance = array_reverse($inheritance);

			if ($inheritance) foreach ($inheritance as $i => $template) {

				$wp_admin_bar->add_menu( [
					'id' => 'oxytocin_inherited_template-' . $template->id,
					'parent' => 'oxytocin_inherited_templates',
					'title' => 'Level ' . ($i + 1) . ': ' . $template->post_title . ' (Template)',
					'href' => get_edit_post_link($template->id),
				]);

			}

			$wp_admin_bar->add_menu( [
				'id' => 'oxytocin_inherited_template-this',
				'parent' => 'oxytocin_inherited_templates',
				'title' => 'Level ' . ($i + 2) . ': ' . $post->post_title,
				'href' => get_edit_post_link($post->ID),
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
				//echo $this->get_templates()[get_post_meta($post_id, 'ct_parent_template', true)];
				
				$inheritance = $this->get_inheritance($post_id);
				
				//if (!is_null($template)) echo "<a href='" . get_edit_post_link($template->id) . "'>{$template->post_title}</a>";

				if ($inheritance) foreach ($inheritance as $i => $template) {
						
					if ($i > 0) echo " ";
					echo "&lArr; ";
					echo "<a href='" . get_edit_post_link($template->id) . "'>{$template->post_title}</a>";
					
				}

				
				break;
				
			case 'digitalis_notes':
				echo wp_trim_words(get_post_meta($post_id, $this->desc_key, true));
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
				
				$template_id = $this->get_template_id($post_id);
				$inheritance = $this->get_inheritance($template_id);
				
				echo "<a href='" . get_edit_post_link($template_id) . "'>" . get_the_title($template_id) . "</a>";
				
				if ($inheritance) foreach ($inheritance as $i => $template) {
						
					echo " &lArr; <a href='" . get_edit_post_link($template->id) . "'>{$template->post_title}</a>";
				
				}
				
				break;
				
				
		}
		
	}

    public function add_meta_box () {
	
		add_meta_box(
			'ct_template_digitalis_info',
			'Info',
			[$this, 'render_meta_box'],
			'ct_template',
			'side',
			'default'
		);	
		
	}

    public function render_meta_box () {
		
		global $post;

		wp_nonce_field( basename( __FILE__ ), 'digitalis' );

		$notes = esc_textarea(get_post_meta($post->ID, $this->desc_key, true));
		
		echo "<label>Template Notes:</label>";
		echo "<textarea name=$this->desc_key class='widefat'>{$notes}</textarea>";

		
	}

    public function save_template ($post_id, $post, $update) {

		if (!current_user_can('edit_post', $post_id)) return;
		if ('revision' === $post->post_type) return;
	
		if (isset($_POST[$this->desc_key]) && wp_verify_nonce($_POST['digitalis'], basename(__FILE__))) {

			$meta = [];
			$meta[$this->desc_key] = esc_textarea($_POST[$this->desc_key]);
			
			foreach ($meta as $key => $value) {
	
				update_post_meta($post_id, $key, $value);
	
			}

		}

	}

	protected function get_template_id ($post_id) {

		$template_id = get_post_meta($post_id, 'ct_other_template', true );

		if (empty($template_id)) {
			$page_template = ct_get_posts_template($post_id);
			$template_id = $page_template->ID;
		}

		return $template_id;

	}

    protected function get_inheritance ($post_id, $inheritance = []) {
		
		$parent = $this->get_parent_template($post_id);
		
		if (is_null($parent)) {
			return $inheritance;
		} else {
			$inheritance[] = $parent;
			$inheritance = $this->get_inheritance($parent->id, $inheritance);
		}
		
		return $inheritance;
		
	}

    protected function get_parent_template ($post_id) {
		
		$template_type = get_post_meta($post_id, 'ct_template_type', true);
		
		if ($template_type == 'reusable_part') return null;
		
		$parent_id = get_post_meta($post_id, 'ct_parent_template', true);
		$templates = $this->get_templates();
		
		foreach ($templates as $template) {
			
			if ($template->id == $parent_id) return $template;
			
		}
		
		return null;
		
	}

    protected function get_templates () {
		
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

	}

	protected function get_recent_templates ($n = 5) {

		global $wpdb;

		return $wpdb->get_results(
			"SELECT id, post_title
			FROM $wpdb->posts as post
			WHERE post_type = 'ct_template'
			AND post.post_status IN ('publish')
			ORDER BY post_modified DESC
			LIMIT {$n}"
		);

	}

}