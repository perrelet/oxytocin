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
			
			//$inheritance[0]->children = [$post];
			//$inheritance[0]->children[0]->children = self::get_reusable_parts($post_id);

			self::add_children($inheritance[0], [$post]);
			$last_child_index = count($inheritance[0]->children) - 1;
			self::add_children($inheritance[0]->children[$last_child_index], self::get_reusable_parts($post_id));

		} else {

			$inheritance = [$post];
			//$inheritance[0]->children = self::get_reusable_parts($post_id);

			self::add_children($inheritance[0], self::get_reusable_parts($post_id));

		}
/* 		if ($inheritance) for ($i = (count($inheritance) - 1); $i >= 0; $i--) {
			if ($i < (count($inheritance) - 1)) dprint($inheritance[$i]->post_title . " -> parent -> " . $inheritance[$i + 1]->post_title);
			//if ($i < (count($inheritance) - 1)) self::check_inner_content($inheritance[$i], $inheritance[$i + 1]);
		} */

		$template = null;
		$prev_template = null;

		if ($inheritance) foreach ($inheritance as $i => $template) {

			if ($i < count($inheritance) - 1) {
				self::check_inner_content($template, $inheritance[$i + 1]);
			} else {
				$template->inner = false;
			}

			if ($prev_template) {
				if ($template->children) {
					$template->children = array_merge($template->children, $prev_template);
				} else {
					$template->children = $prev_template;
				}
			}
			$prev_template = [$template];

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
			$post->inner = self::find_inner_content(json_decode($json, true));
		} else {
			$post->inner = false;
		}

		return $post;

	}

	public static function find_inner_content ($elements, $inner = false) {

		if (!$elements || !isset($elements['children'])) return false;

		foreach ($elements['children'] as $element) {

			if ($element['name'] == 'ct_inner_content') return true;
			if (isset($element['children'])) $inner = self::find_inner_content($element, $inner);

		}

		return $inner;

	}

	public static function get_inheritance ($post_id, $parts = false) {

		if (get_post_type($post_id) == 'ct_template') {

			$template_id = $post_id;
			$inheritance = [];

		} else {

			if (!$template = self::get_template($post_id)) return [];
			$template_id = $template->ID;
			$inheritance = [$template];

		}
		
		return self::get_template_inheritance($template_id, $parts, $inheritance);

	}

    public static function get_template_inheritance ($template_id, $parts = false, $inheritance = []) {
		
		$parent = self::get_parent_template($template_id);
		
		if ($parent && $parts) self::add_children($parent, self::get_reusable_parts($parent->ID, true));

		if (is_null($parent)) {

			return $inheritance;

		} else {

			$inheritance[] = $parent;
			$inheritance = self::get_template_inheritance($parent->ID, $parts, $inheritance);

		}
		
		return $inheritance;
		
	}

    public static function get_reusable_parts ($post_id, $recursive = true) {

		if (!$json = get_post_meta($post_id, 'ct_builder_json', true)) return [];

		$tree = json_decode($json, true);
		$parts = self::find_reusable_parts($tree);

		if ($recursive && $parts) foreach ($parts as $part) {

			self::add_children($part, self::get_reusable_parts($part->ID, true));

		}

		return $parts;

	}

	public static function find_reusable_parts ($elements, $reusable = []) {

		if (!$elements || !isset($elements['children'])) return $reusable;

		foreach ($elements['children'] as $element) {

			if ($element['name'] == 'ct_reusable') {
				
				//$reusable[] = $element;
				$part = get_post($element['options']['view_id']);
				$part->nicename = $element['options']['nicename'];
				$part->type = 'reusable';
				$part->inner = false;
				$reusable[] = $part;

			} else if ($element['name'] == 'ct_section') {

				//$part = new \WP_Post((new \stdClass())->post_title = 'test');
				$x = new \stdClass();
				$x->ID = 1;
				$x->post_title = 'SECTION';
				$part = new \WP_Post($x);
				$part->nicename = $element['options']['nicename'];
				$part->type = 'section';
				$part->inner = false;
				$reusable[] = $part;

			}

			if (isset($element['children'])) $reusable = self::find_reusable_parts($element, $reusable);

		}

		return $reusable;

	}

	public static function get_builder_url ($post) {

		if (!property_exists($post, 'inner')) return false;

		$url = ct_get_post_builder_link($post->ID);
		$url .= $post->inner ? '&ct_inner=true' : '';

		return $url;

	}

}