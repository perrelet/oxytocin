<?php

namespace Oxytocin;

class Chart extends Model {

	protected $tree;

	public function __construct ($tree) {

		if (property_exists($tree, 'structure') && $tree->structure == 'flat') {
			$this->tree = $tree;
		} else {
			$this->tree = Genealogist::flatten_tree($tree);
			
		}

	}

	public function get_nodes () {

		$data = [];

		global $post;
		$current_id = $post ? $post->ID : null;

		if ($this->tree) foreach ($this->tree as $i => $post) {

			$node = [
				'name' 			=> $post->post_title,
				'tree_index' 	=> $i,
				'current' 		=> $post->ID == $current_id,
			];

			switch ($post->type) {

				case 'template':
					$node['color'] = 'rgb(25,184,120)';
					$node['type'] = 'Template';
					break;

				case 'reusable':
					$node['color'] = 'rgb(238,122,72)';
					$node['type'] = 'Part';
					break;

				default:
					$node['color'] = 'rgb(59,98,161)';
					$post_type = get_post_type_object($post->post_type);
					$node['type'] = $post_type->labels->singular_name;

			}

			if (property_exists($post, 'parent_node')) $node['parent'] = $post->parent_node;

			$data[] = $node;

		}

		return json_encode($data);

	}

}