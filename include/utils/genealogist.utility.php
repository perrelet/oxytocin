<?php

namespace Oxytocin;

class Genealogist extends Utility {

    public static function get_tree ($post_id) {

		

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
		
		$parent_id = get_post_meta($post_id, 'ct_parent_template', true);
		return $parent_id ? get_post($parent_id) : null;
		
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
		
		if ($parent && $parts !== false) $parent->parts = self::get_reusable_parts($parent->ID, true);

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

			$part->parts = self::get_reusable_parts($part->ID, true);

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
				$reusable[] = $part;

			}

			if (isset($element['children'])) $reusable = self::find_reusable_parts($element, $reusable);

		}

		return $reusable;

	}

}