<?php

namespace Oxytocin;

class Genealogist extends Utility {

	public static function flatten_tree ($tree, $flat_tree = []) {

		$parent_node = count($flat_tree) - 1;

		if (property_exists($tree, 'children') && $tree->children) foreach ($tree->children as $post) {

			//if (!$post) continue;

			$post->parent_id = property_exists($tree, 'ID') ? $tree->ID : null;
			if ($parent_node >= 0) $post->parent_node = $parent_node;
			$post->structure = 'flat';

			$flat_tree[$post->ID] = $post;

			$flat_tree = self::flatten_tree($post, $flat_tree);

			$flat_tree[$post->ID]->children = null;

		}

		return $flat_tree;

	} 

    public static function get_tree ($post_id) {

		$inheritance = self::get_inheritance($post_id, true);

		$post = get_post($post_id);
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

		$template = null;
		$prev_template = null;

		if ($inheritance) foreach ($inheritance as $i => $template) {

			if ($prev_template) {
				if ($template->children) {
					$template->children = array_merge($template->children, $prev_template);
				} else {
					$template->children = $prev_template;
				}
			}
			$prev_template = [$template];

		}

		$tree = new \stdClass();
		$tree->children = [$template];

		return $tree;

	}

	public static function add_children ($obj, $children) {

		if (property_exists($obj, 'children') && $obj->children) {
			$obj->children = array_merge($obj->children, $children);
		} else {
			$obj->children = $children;
		}

	}

	public static function get_template_id ($post_id) {

		$template_id = get_post_meta($post_id, 'ct_other_template', true );

		if (empty($template_id)) {
			if (!$page_template = ct_get_posts_template($post_id)) return null;
			$template_id = $page_template->ID;
		}

		return $template_id;

	}

    public static function get_parent_template ($post_id) {
		
		$template_type = get_post_meta($post_id, 'ct_template_type', true);
		if ($template_type == 'reusable_part') return null;
		
		if (!$parent_id = get_post_meta($post_id, 'ct_parent_template', true)) return null;
		
		$template = get_post($parent_id);
		$template->type = 'template';
		return $template;
		
	}

	public static function get_inheritance ($post_id, $parts = false) {

		if (get_post_type($post_id) == 'ct_template') {

			$template_id = $post_id;

		} else {

			$template_id = self::get_template_id($post_id);

		}
		
		return self::get_template_inheritance($template_id, $parts);

	}

    public static function get_template_inheritance ($template_id, $parts = false, $inheritance = []) {
		
		$parent = self::get_parent_template($template_id);
		
		if ($parent && $parts) $parent->children = self::get_reusable_parts($parent->ID, true);

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

			$part->children = self::get_reusable_parts($part->ID, true);

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
				$reusable[] = $part;

			}

			if (isset($element['children'])) $reusable = self::find_reusable_parts($element, $reusable);

		}

		return $reusable;

	}

}