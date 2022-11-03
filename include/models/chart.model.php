<?php

namespace Oxytocin;

class Chart extends Model {

	public static $count = 0;

	protected $index;
	protected $tree;
	protected $nodes;

	public static $node_types = ['template', 'reusable', 'post', 'section'];

	public function __construct ($tree) {

        $this->index = static::$count;
		static::$count++;

		$tree->get_meta();
		$this->tree = $tree;

		global $post;

	}

	public function render ($theme = 'light') {

		if (isset($_GET['theme'])) $theme = $_GET['theme'];

		$theme = self::theme($theme);
		$json_theme = json_encode($theme);

		$tree_info = $this->tree->get_info();
		$nodes = $this->get_nodes();
		$json = json_encode($nodes);

		jprint($tree_info);

		$id = 'oxytocin-graph-' . $this->index;
		$height = 160 + $tree_info->width * 100;
		$width = 360 * $tree_info->depth;

		echo "<script src='https://unpkg.com/chart.js@3'></script>";
		echo "<script src='https://unpkg.com/chartjs-chart-graph@3'></script>";
		echo "<script src='https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2'></script>";

		echo "<div class='oxytocin-graph-wrap loading' style='height: {$height}px;'>";
			echo "<div class='oxytocin-graph-title'>Template Map <sup>1.0</sup></div>";
			echo "<div class='chart-loader'></div>";
			echo "<canvas class='oxytocin-graph' id='{$id}' data-index='{$this->index}' style='max-width: {$width}px;'></canvas>";
		echo "</div>";

		echo "<script>new_chart({$json}, '{$this->index}', 'tree', 'horizontal', {$json_theme});</script>";
		
		if ($this->index == 0) {
			echo "<style>" . $this->generate_css($theme) . "</style>";
			$this->context_menu();
		}

	}

	public function get_nodes () {

		$this->nodes = [];

		$current_id = get_the_ID();
		$i = 0;

		jprint($this->tree->get_flat_tree());

		if ($this->tree->get_flat_tree()) foreach ($this->tree->get_flat_tree() as $post_id => $post) {

			$node = [
				'name' 			=> $post->post_title,
				'type'			=> $post->type,
				'tree_index' 	=> $i,
				'current' 		=> $post->ID === $current_id,
				'url'			=> ($post->ID === $current_id) ? null : get_edit_post_link($post->ID, 'raw'),
				'builder'		=> Genealogist::get_builder_url($post),
				'notes'			=> $post->info,
			];

			switch ($post->type) {

				case 'template':
					//$node['color']	= '#25d1a0';//'rgb(25,184,120)';//"#7bc667";//'#4bc0c1';//'rgb(25,184,120)';
					$node['post_type']	= 'Template';
					$node['open_label']	= 'Open Template';
					break;

				case 'reusable':
					//$node['color']	= '#f9bb3e';//'rgb(238,122,72)';//"#ffa600";//'#ffcd56';//'rgb(238,122,72)';
					$node['post_type']	= 'Part';
					$node['open_label']	= 'Open Reusable Part';
					break;

				case 'section':
					//$node['color']	= '#cd55fc';//'rgb(238,122,72)';//"#ffa600";//'#ffcd56';//'rgb(238,122,72)';
					$node['post_type']	= 'Section';
					$node['open_label']	= 'Open Parent';
					break;

				default:
					//$node['color']	= '#cd55fc';//'rgb(59,98,161)';//"#4bc0c1";//'#3aa8e3';//'rgb(59,98,161)';
					$post_type			= get_post_type_object($post->post_type);
					$node['post_type']	= $post_type->labels->singular_name;
					$node['open_label']	= 'Open ' . $post_type->labels->singular_name;

			}

			if (property_exists($post, 'parent_node')) $node['parent'] = $post->parent_node;

			$this->nodes[] = $node;
			$i++;

		}

		jprint($this->nodes);

		return $this->nodes;

	}

	protected function context_menu () {

		echo "<nav id='chart-context-menu'>";
			echo "<div class='chart-context-box'>";
				echo "<div class='chart-context-items'>";
					echo "<div class='title'>Options</div>";
					echo "<a id='chart-context-edit' href='#'>Open</a>";
					echo "<a id='chart-context-builder' href='#'>Edit with Oxygen</a>";
					echo "<div id='chart-context-info' class='title'>Info</div>";
					echo "<div id='chart-context-notes' class='info'></div>";
				echo "</div>";
			echo "</div>";
		echo "</nav>";

	}

	protected function generate_css ($theme) {

		$css = [
			'.oxytocin-graph-wrap' => [
				'background-color' => $theme['colors']['bg'],
			]
		];

		foreach (self::$node_types as $node_type) {

			$css["#chart-context-menu.{$node_type} a:hover"] = [
				'background-color' => $theme['colors']['node'][$node_type],
			];

		}

		return self::css($css);

	}

	protected static $themes = [
		'light' => [
			'colors' => [
				'bg'	=> '#fff',
				'edge'	=> '#fff',
				'label'	=> '#999',
				'node'	=> [
					'template'	=> '#25d1a0',
					'reusable'	=> '#f9bb3e',
					'section'	=> '#cd55fc',
					'post'		=> '#cd55fc',
				],
			],
		],
		'dark' => [
			'colors' => [
				'bg'	=> '#04000f',
				'edge'	=> '#443961',
				'label'	=> '#fbf0ff'
			],
			'node'	=> [
				'template'	=> '#25d1a0',
				'reusable'	=> '#f9bb3e',
				'section'	=> '#cd55fc',
				'post'		=> '#cd55fc',
			],
		],
	];
	protected static $theme = null;

	protected static function theme ($theme = 'light') {

		if (is_null(self::$theme)) {
			self::$theme = isset(self::$themes[$theme]) ?  self::$themes[$theme] : self::$themes['light'];
			self::$theme['name'] = $theme;
		}

		return self::$theme;

	}

	public static function css ($data) {
		
		$css = "";
		
		foreach ($data as $selector => $props) {

			$css .= $selector . " {" ;
			foreach ($props as $property => $value) $css .= "{$property}: {$value};";
			$css .= "}" ;

		}
		
		return $css;
		
	}

}