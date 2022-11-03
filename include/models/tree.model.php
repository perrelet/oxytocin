<?php

namespace Oxytocin;

class Tree extends Model {

    protected $tree;
    protected $flat;

    protected $info = null;

    public function __construct ($children) {

        $children = is_array($children) ? $children : [$children];

        $this->tree = new \stdClass();
        $this->tree->ID = 'tree_root';
        $this->tree->children = $children;

    }

    public function get_tree () {

        return $this->tree;

    }

    public function get_info () {

        $this->info = (object) [
            'depth' => 0,
            'width' => 0,
            'widths' => [],
        ];

        $this->iterate_get_info($this->tree);

        if ($this->info->widths) foreach ($this->info->widths as $width) $this->info->width = max($this->info->width, $width);

        return $this->info;

    }

    protected function iterate_get_info ($tree, $depth = 0) {

        $this->info->depth = max($this->info->depth, $depth);

        if (isset($this->info->widths[$depth])) {
            $this->info->widths[$depth]++;
        } else {
            $this->info->widths[$depth] = 1;
        }

        if (property_exists($tree, 'children') && $tree->children) {

            foreach ($tree->children as $i => $post) {

                $this->iterate_get_info($post, $depth + 1);

            }

        }

    }

    public function get_flat_tree () {

        if (is_null($this->flat)) $this->flat = $this->flatten($this->tree);
        return $this->flat;

    }

	public function flatten ($tree, $flat_tree = []) {

		$parent_node = count($flat_tree) - 1;

		if (property_exists($tree, 'children') && $tree->children) {
            
            for ($i = count($tree->children) - 1; $i >= 0; $i--) {
                $post = $tree->children[$i];
            //foreach ($tree->children as $i => $post) {

                $post->structure = 'flat';
                $post->parent_id = property_exists($tree, 'ID') ? $tree->ID : null;
                if ($parent_node >= 0) $post->parent_node = $parent_node;
                
                $id = $post->ID;
                $n = 1;
                while (isset($flat_tree[$id])) {

                    $id = $post->ID . "#" . $n;
                    $n++;

                }

                $flat_tree[$id] = $post;
                $flat_tree = $this->flatten($post, $flat_tree);                

                $flat_tree[$id]->children = null;

            }

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