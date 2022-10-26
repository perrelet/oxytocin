<?php

namespace Oxytocin;

class Chart extends Model {

	protected $tree;
	public static $count = 0;
	protected $index;

	public function __construct ($tree) {

        $this->index = static::$count;
		static::$count++;

		$tree->get_meta();
		$this->tree = $tree;

		global $post;

	}

	public function render () {

		$id = 'oxytocin-graph-' . $this->index;

		echo "<script src='https://unpkg.com/chart.js@3'></script>";
		echo "<script src='https://unpkg.com/chartjs-chart-graph@3'></script>";
		echo "<script src='https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2'></script>";

		echo "<div class='oxytocin-graph-wrap loading'>";
			echo "<div class='chart-loader'></div>";
			echo "<canvas class='oxytocin-graph' id='{$id}' data-index='{$this->index}'></canvas>";
		echo "</div>";

		$nodes = $this->get_nodes();

		echo "<script>new_chart({$nodes}, '{$this->index}', 'tree', 'horizontal');</script>";
		
		if ($this->index == 0) {

			echo "<script>let chart_data = [{$nodes}];</script>";
			$this->context_menu();

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
				'builder'		=> Genealogist::get_builder_url($post),
				'notes'			=> $post->info,
			];

			switch ($post->type) {

				case 'template':
					$node['color']		= 'rgb(25,184,120)';//"#7bc667";//'#4bc0c1';//'rgb(25,184,120)';
					$node['type']		= 'template';
					$node['post_type']	= 'Template';
					$node['open_label']	= 'Open Template';
					break;

				case 'reusable':
					$node['color']		= 'rgb(238,122,72)';//"#ffa600";//'#ffcd56';//'rgb(238,122,72)';
					$node['type']		= 'part';
					$node['post_type']	= 'Part';
					$node['open_label']	= 'Open Reusable Part';
					break;

				default:
					$node['color']		= 'rgb(59,98,161)';//"#4bc0c1";//'#3aa8e3';//'rgb(59,98,161)';
					$post_type			= get_post_type_object($post->post_type);
					$node['type']		= 'post';
					$node['post_type']	= $post_type->labels->singular_name;
					$node['open_label']	= 'Open ' . $post_type->labels->singular_name;

			}

			$node['label_color'] = $node['current'] ? $node['color'] : '#999';

			if (property_exists($post, 'parent_node')) $node['parent'] = $post->parent_node;

			$data[] = $node;
			$i++;

		}

		return json_encode($data);

	}

	protected function context_menu () {

		echo "<nav id='chart-context-menu'>";
			echo "<div id='chart-context-inner'>";
				echo "<div class='title'>Options</div>";
				echo "<a id='chart-context-edit' href='#'>Open</a>";
				echo "<a id='chart-context-builder' href='#'>Edit with Oxygen</a>";
				echo "<div id='chart-context-info' class='title'>Info</div>";
				echo "<div id='chart-context-notes' class='info'></div>";
			echo "</div>";
		echo "</nav>";

	}

}