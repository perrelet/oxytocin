<?php

namespace Oxytocin;

class Genealogist extends Utility {

    public static function get_tree ($post_id) {

		$inheritance = self::get_inheritance($post_id, true);

		$post = get_post($post_id);
		if ($inheritance) self::check_inner_content($post, $inheritance[0]);

		if ($post->post_type == 'ct_template') {
			if (get_post_meta($post_id, 'ct_template_type', true)  == 'reusable_part') {
				$post->type = 'reusable';
			} else {
				$post->type = 'template';
			}
		} else {
			$post->type = 'post';
		}

		if ($inheritance) {
			
			self::add_children($inheritance[0], [$post]);
			$last_child_index = count($inheritance[0]->children) - 1;
			self::add_children($inheritance[0]->children[$last_child_index], self::get_reusable_parts($post));

		} else {

			$inheritance = [$post];

			self::add_children($inheritance[0], self::get_reusable_parts($post));

		}

		$template = null;
		$prev_template = null;

		if ($inheritance) foreach ($inheritance as $i => $template) {

			if ($i < count($inheritance) - 1) {
				self::check_inner_content($template, $inheritance[$i + 1]);
			} else {
				$template->inner = false;
			}

			if ($prev_template) {

				if ($prev_template->inner && (strpos($prev_template->inner_location, "-") !== false) && $template->children) {

					foreach ($template->children as $child) {

						if ($child->ID == $prev_template->inner_location) {

							//jprint("Adding " . $prev_template->post_title . " as a child of {$child->post_title}");
							self::add_children($child, [$prev_template]);
							break;

						}

					} 

				} else {

					//jprint("Adding " . $prev_template->post_title . " as a child of {$template->post_title}");
					self::add_children($template, [$prev_template]);

				}

				
			}

			$prev_template = $template;

		}

		$tree = new Tree($template);

		return $tree;

	}

	public static function add_children ($obj, $children) {

		if (property_exists($obj, 'children') && $obj->children) {
			$obj->children = array_merge($obj->children, $children);
		} else {
			$obj->children = $children;
		}

	}

	public static function get_template ($post_id) {

		$template_id = intval(get_post_meta($post_id, 'ct_other_template', true));

		if (empty($template_id)) {
			if (!$page_template = ct_get_posts_template($post_id)) return null;
			$template_id = $page_template->ID;
		}

		if ($template_id == -1) return null;

		$template = get_post($template_id);
		$template->type = 'template';

		return $template;

	}

    public static function get_parent_template ($post_id) {
		
		$template_type = get_post_meta($post_id, 'ct_template_type', true);
		if ($template_type == 'reusable_part') return null;
		
		if (!$parent_id = get_post_meta($post_id, 'ct_parent_template', true)) return null;
		
		$template = get_post($parent_id);
		$template->type = 'template';

		return $template;
		
	}

	public static function check_inner_content ($post, $parent) {

		if ($json = get_post_meta($parent->ID, 'ct_builder_json', true)) {
			//$post->inner = self::find_inner_content(json_decode($json, true), $parent->ID);
			self::find_inner_content($post, $parent, json_decode($json, true));
		} else {
			$post->inner = false;
		}

		return $post;

	}

	public static function find_inner_content ($post, $parent, $elements) {

		if (!$elements || !isset($elements['children'])) return;

		foreach ($elements['children'] as $element) {

			switch ($element['name']) {

				//case 'ct_reusable':
				case 'ct_section':

					$post->section_id = $element['id'];
					break;

				case 'ct_inner_content':

					$post->inner_location = $post->section_id ? ($parent->ID . "-" . $post->section_id) : $parent->ID;
					$post->inner = true;
					return;

			}

			if (isset($element['children'])) self::find_inner_content($post, $parent, $element);

		}

	}

	public static function get_inheritance ($post_id, $find_parts = false) {

		if (get_post_type($post_id) == 'ct_template') {

			$template_id = $post_id;
			$inheritance = [];

		} else {

			if (!$template = self::get_template($post_id)) return [];
			if ($find_parts) self::add_children($template, self::get_reusable_parts($template));

			$template_id = $template->ID;
			$inheritance = [$template];

		}
		
		return self::get_template_inheritance($template_id, $inheritance, $find_parts);

	}

    public static function get_template_inheritance ($template_id, $inheritance = [], $find_parts = false) {
		
		$parent = self::get_parent_template($template_id);

		if ($parent && $find_parts) self::add_children($parent, self::get_reusable_parts($parent));

		if (is_null($parent)) {

			return $inheritance;

		} else {

			$inheritance[] = $parent;
			$inheritance = self::get_template_inheritance($parent->ID, $inheritance, $find_parts);

		}
		
		return $inheritance;
		
	}

    public static function get_reusable_parts ($post, $find_sections = true) {

		//jprint($post->post_title);

		if ($post->type == 'section') return [];
		if (!$json = get_post_meta($post->ID, 'ct_builder_json', true)) return [];

		$tree = json_decode($json, true);
		return self::find_reusable_parts($tree, $post, $find_sections);

	}

	public static function find_reusable_parts ($elements, $post, $find_sections = true, $reusable = []) {

		if (!$elements || !isset($elements['children'])) return $reusable;

		foreach ($elements['children'] as $element) {

			if ($element['name'] == 'ct_reusable') {

				//$reusable[] = $element;
				$part = get_post($element['options']['view_id']);
				$part->nicename = isset($element['options']['nicename']) ? $element['options']['nicename'] : $element['options']['selector'];
				$part->type = 'reusable';
				$part->inner = false;

				//jprint(". " . $part->post_title);

				self::add_children($part, self::get_reusable_parts($part, $find_sections));

				//if ($part->post_title == 'Gallery') jprint($part);

				$reusable[] = $part;

			}
			
			if ($find_sections && ($element['name'] == 'ct_section')) {

				$nicename = isset($element['options']['nicename']) ? $element['options']['nicename'] : $element['options']['selector'];

				//$part = new \WP_Post((new \stdClass())->post_title = 'test');
				$section = new \stdClass();
				$section->ID = $post->ID . "-" . $element['id'];
				$section->post_title = $nicename;
				$section = new \WP_Post($section);
				$section->nicename = $nicename;
				$section->type = 'section';
				$section->inner = false;
				if (isset($element['children'])) $section->children = self::find_reusable_parts($element, $post, $find_sections, []);
				$reusable[] = $section;				

			} else {

				if (isset($element['children'])) $reusable = self::find_reusable_parts($element, $post,  $find_sections, $reusable);

			}

			

		}

		return $reusable;

	}

	public static function get_builder_url ($post) {

		if (!property_exists($post, 'inner')) return false; // These should be posts inherting templates that dont have an inner content block

		$url = ct_get_post_builder_link($post->ID);
		$url .= $post->inner ? '&ct_inner=true' : '';

		return $url;

	}

}