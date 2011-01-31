<?php
/**
 * Provides a sitemap either as HTML format, or XMLs
 *
 * @author Dave Bullough
 */
class Dwoo_Plugin_sitemap extends PacoSites
{
	public function process($format='html', $class='sitemap')
	{
		$map = $this->sitemap('4ce3ea8dff507872e15cdce7');
		
		switch ($format)
		{
		case 'html':
			$html = '<ul class="' . $class . '">';
			foreach($map['sitemap'] as $page)
			{
				$html .= $this->do_pages($page);
			}
			print($html . '</ul>');
			break;
		case 'xml':
			// xml
			//header("Content-type: text/xml");
			$xml = new DomDocument('1.0', 'utf-8');			
			$root = $xml->appendChild(new DomElement('urlset'));
			$root->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
			$root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
			$root->setAttribute('xmlns:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
			/*
			<url> 
				<loc>http://harrogate.advertag.com/ad/2015/childrens-social-worker</loc> 
				<lastmod>2009-11-16T15:17:30+00:00</lastmod> 
				<changefreq>monthly</changefreq> 
				<priority>0.8</priority> 
			</url> 
			*/
			foreach($map['sitemap'] as $page)
			{
				$this->append_node($root, $page);
			}
			print($xml->saveXML());
			break;
		}
	} 
	
	private function append_node($p, array $data)
	{
		$url = $p->appendChild(new DomElement('url'));
		$url->appendChild(new DomElement('loc', rtrim($this->get_host() . '/' . $data['url'], '/')));
		$url->appendChild(new DomElement('lastmod', date('c')));
		$url->appendChild(new DomElement('changefreq', 'weekly'));
		$url->appendChild(new DomElement('priority', 0.8));
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
