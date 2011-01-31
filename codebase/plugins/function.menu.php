<?php
/**
 * Extracts bucket data
 *
 */
 

class Dwoo_Plugin_menu extends PacoSites
{
	public function process($root='/', $class='paco_navigation')
	{
		$html = '<ul class="' . $class . '">';
		$map = $this->sitemap('4ce3ea8dff507872e15cdce7');
		foreach($map['sitemap'] as $page)
		{
			$html .= $this->do_pages($page);
		}
		print($html . "</ul>");
	} 
	           
	private function do_pages(array $page)
	{
		$url = rtrim($page['url'], '/');
		$title = $page['title'];
		$li = "<li><a title='" . $title . "' href='/" . $url . "'>" . $title . "</a>";
		if (array_key_exists('pages', $page))
		{
			$li .= "<ul>\n";
			foreach($page['pages'] as $p)
			{
				$li .= $this->do_pages($p);
			}
			$li .= "</ul>\n";
		}
		$li .= "</li>\n";
		return $li;
	}
}
?>
