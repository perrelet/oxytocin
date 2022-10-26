<?php

namespace Oxytocin;

class Tree extends Model {

    protected $tree;
    protected $flat;

    public function __construct ($children) {

        $children = is_array($children) ? $children : [$children];

        $this->tree = new \stdClass();
        $this->tree->children = $children;

    }

    public function get_tree () {

        return $this->tree;

    }

    public function get_flat_tree () {

        if (is_null($this->flat)) $this->flat = $this->flatten($this->tree);
        return $this->flat;

    }

	public function flatten ($tree, $flat_tree = []) {

		$parent_node = count($flat_tree) - 1;

		if (property_exists($tree, 'children') && $tree->children) foreach ($tree->children as $post) {

			$post->parent_id = property_exists($tree, 'ID') ? $tree->ID : null;
			if ($parent_node >= 0) $post->parent_node = $parent_node;
			$post->structure = 'flat';

			$flat_tree[$post->ID] = $post;

			$flat_tree = $this->flatten($post, $flat_tree);

			$flat_tree[$post->ID]->children = null;

		}

		return $flat_tree;

	}
    
    public function get_meta ($tree = null) {

        if (is_null($tree)) $tree = $this->tree;

        if (property_exists($tree, 'children') && $tree->children) foreach ($tree->children as $post) {

            if ($post->post_type == 'ct_template') {
                $post->info = htmlspecialchars_decode(get_post_meta($post->ID, Oxygen::$notes_key, true));
            }

            $this->get_meta($post);

        }

        return $this->tree;

    }

}