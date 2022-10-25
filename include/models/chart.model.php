<?php

namespace Oxytocin;

class Chart extends Model {

	protected $tree;
	public static $count = 0;
	protected $index;

	public function __construct ($tree) {

        $this->index = static::$count;
		static::$count++;

		$this->tree = $tree;

		/* if (property_exists($tree, 'structure') && $tree->structure == 'flat') {
			$this->tree = $tree;
		} else {
			//dprint($tree);
			$this->tree = Genealogist::flatten_tree($tree);
			
		} */

	}

	public function render ($id = 'oxytocin-graph') {

		echo "<script src='https://unpkg.com/chart.js@3'></script>";
		echo "<script src='https://unpkg.com/chartjs-chart-graph@3'></script>";
		echo "<script src='https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2'></script>";

		echo "<canvas class='oxytocin-graph' id='{$id}' data-index='{$this->index}'></canvas>";

		$nodes = $this->get_nodes();

		echo "<script>new_chart({$nodes}, '{$id}', 'tree', 'horizontal');</script>";
		
		if ($this->index == 0) {
			echo "<script>let chart_data = [{$nodes}];</script>";
		} else {
			echo "<script>chart_data.push({$nodes});</script>";
		}

	}

	public function get_nodes () {

		global $post;

		$data = [];
		$current_id = $post ? $post->ID : null;
		$i = 0;

		if ($this->tree->get_flat_tree()) foreach ($this->tree->get_flat_tree() as $post_id => $post) {

			$node = [
				'name' 			=> $post->post_title,
				'tree_index' 	=> $i,
				'current' 		=> $post->ID == $current_id,
				'url'			=> ($post->ID == $current_id) ? null : get_edit_post_link($post->ID, 'raw'),
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
			$i++;

		}

		return json_encode($data);

	}

}