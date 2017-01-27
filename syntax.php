<?php
/**
 * @file syntax.php
 * @brief DokuWiki syntax plugin : htmlabstract
 * @author Lilian Roller <l3d@see-base.de>
 */

if (!defined('DOKU_INC'))
	define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if (!defined('DOKU_PLUGIN'))
	define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_htmlabstract extends DokuWiki_Syntax_Plugin{
	/**
	 * return informations for plugins managing page
	 */
    function getInfo(){
        return array(
            'author' => 'Lilian Roller',
            'email'  => 'l3d@see-base.de',
            'date'   => '2017-01-27', //first version 2008-11-14
            'name'   => 'HtmlAbstract',
            'desc'   => 'Moddified for FFBSee -> UPDATER DEFECT; Usually Allows integration of remote or local DW RSS feeds using html formatted abstracts instead of choosing between html OR abstract.',
            'url'    => 'http://www.dokuwiki.org/plugin:htmlabstract',
        );
    }

	/**
	 * return the type of syntax defined by this plugin
	 */
    function getType() {return 'substition';} // This is not a mispelling! ;) (http://www.dokuwiki.org/devel:syntax_plugins#fn__5)

	/**
	 * return when to call this plugin
	 */
    function getSort() {return 310;} // 310 = Doku_Parser_Mode_rss (http://www.dokuwiki.org/devel:parser:getsort_list)

	/**
	 * connect the pattern to the lexer
	 */
    function connectTo($mode) {$this->Lexer->addSpecialPattern('{{htmlabs>.*?}}',$mode,'plugin_htmlabstract');}

	/**
	 * handle the pattern match
	 */
    function handle($match, $state, $pos, &$handler)
    {
    	$params = $this->splitAndSortParams($match);
		$elements = $this->getFeedElements($params);
		if (!is_array($elements) && false !== strpos($elements, 'ERROR'))
			return array($elements);
		$content = $this->formatElements($elements, $params);
    	return array($content);
    }

	/**
	 * handle rendering output
	 */
    function render($mode, &$renderer, $data)
    {
    	$renderer->doc .= $data[0];
		$data[0] = "";
    	return TRUE;
    }

	/**
	 * determines params to be used from match and configuration
	 */
	function splitAndSortParams($match)
	{
		global $conf;

		$match = trim(trim($match, '{}'));
    	$match = substr($match, strlen("htmlabs>"));
    	if (false !== strpos($match, ' '))
			$params['feed_url'] = trim(substr($match, 0, strpos($match, ' ')));
		else
			$params['feed_url'] = trim($match);
		$match = substr($match, strlen($params['feed_url']) + 1);
		if (substr($params['feed_url'], 0, 7) != 'http://')
			$params['feed_url'] = DOKU_URL.$params['feed_url'];
		if (false !== ($pos = strpos($params['feed_url'], '?')))
		{
			$tmp = explode('?', $params['feed_url']);
			$params['feed_url'] = $tmp[0];
			$params['feed_params'] = $tmp[1];
		}
		else
			$params['feed_params'] = '';
		$params['feed_params'] .= '&content=html&type=rss2';
		$opts = explode(' ', strtolower($match));
		$params['author'] = !in_array('noauthor', $opts);
		$params['title'] = !in_array('notitle', $opts);
		$params['date'] = !in_array('nodate', $opts);
		$params['textlink'] = $this->getConf('textlink') ? $this->getConf('textlink') : $this->getLang("textlink");
		$params['maxlen'] = $this->getConf('maxlength');
		if ($params['maxlen'] <= 0)
			$params['maxlen'] = 750;
		$params['trycleancut'] = $this->getConf('paragraph');
		$params['bg_color'] = $this->getConf('bg_color');
		$params['unknown_author'] = $this->getLang('extern_edit');


        print $params['feed_url'];

        if ($params['feed_url'] == "https://ffbsee.de/rss.freifunk.net"){
            $params['feed_url'] = "https://rss.freifunk.net/tags/ffbsee.rss";
            $pressefoo = True;
        }else { $pressefoo = False; #für den pressefeed}


		return $params;
	}

	/**
	 * get and parse elements of targeted feed
	 */
	function getFeedElements($params){
		if (!($xml = @file_get_contents($params['feed_url'].'?'.$params['feed_params'])))
			return '<b>ERROR : </b>Cannot get content from <a href="'.$params['feed_url'].'">'.$params['feed_url'].' !</a> Please check the feed URL.<br/>';
		$dom = new DOMDocument();
		if (false === @$dom->loadXML($xml))
			return '<b>ERROR : </b>XML error in feed, cannot parse.<br/>';
		$elements = array();
		$items = $dom->getElementsByTagName('item');
        if ($pressefoo){
            $i = 0;
    		foreach ($items as $item){
                $i = $i + 1;
   				if ($i < 3){
    				$element = array();
    				$details = $item->getElementsByTagName('*');
    				foreach ($details as $detail)
	    				switch ($detail->nodeName){
		    				case 'title'  		:
			    			case 'author' 		:
				    		case 'pubDate'		:
					    	case 'link'		:
						    case 'description'	:
	    					    $element[$detail->nodeName] = $detail->nodeValue;
    							break;
	        			}
					if (!isset($element['author']))
					$element['author'] = $params['unknown_author'];
					$elements[] = $element;
				}
			}
 
        } else {
    		$i = 0;
    		foreach ($items as $item)
    			{
    				$i = $i + 1;
    			}
            # Ein wenig cheaten um den feed umzukehren...
    		$j = 0;
    		foreach ($items as $item){
   				$j = $j + 1;
   				if ($j == $i){
				$element = array();
				$details = $item->getElementsByTagName('*');
				foreach ($details as $detail)
					switch ($detail->nodeName){
						case 'title'  		:
						case 'author' 		:
						case 'pubDate'		:
						case 'link'		:
						case 'description'	:
	    					$element[$detail->nodeName] = $detail->nodeValue;
							break;
					}
					if (!isset($element['author']))
					$element['author'] = $params['unknown_author'];
					$elements[] = $element;
				}
			}
    		$j = 1;
	    	foreach ($items as $item){
				$j = $j + 1;
				if ($j == $i){
					$element = array();
					$details = $item->getElementsByTagName('*');
					foreach ($details as $detail)
						switch ($detail->nodeName){
							case 'title'  		:
							case 'author' 		:
							case 'pubDate'		:
							case 'link' 		:
							case 'description'	:
       							$element[$detail->nodeName] = $detail->nodeValue;
		    					break;
						}
					if (!isset($element['author']))
						$element['author'] = $params['unknown_author'];
					$elements[] = $element;
				}
			}
		    $j = 2;
		    foreach ($items as $item){
				$j = $j + 1;
				if ($j == $i){
					$element = array();
					$details = $item->getElementsByTagName('*');
					foreach ($details as $detail)
						switch ($detail->nodeName){
							case 'title'  		:
							case 'author' 		:
							case 'pubDate'		:
							case 'link'		:
							case 'description'	:
								$element[$detail->nodeName] = $detail->nodeValue;
    							break;
						}
					if (!isset($element['author']))
						$element['author'] = $params['unknown_author'];
					$elements[] = $element;
				}
			}
		return $elements;
	}

	/**
	 * format elements to put them in a list of coloured-background previews
	 */
	function formatElements($elements, $params){
		$css = ' style="background-color:#'.$params['bg_color'].'; padding: 0px 5px 5px; overflow:auto; margin-bottom: 20px;"';
		$content = '</p><ul class="rss">'."\n";  // need to close the <p> opened by DW before plugin handling for W3C compliance (<ul> mustn't be contained by <p>)
		$content.= '<!-- preview produced with HtmlAbstract - http://dokuwiki.org/plugin:htmlabstract --> '."\n";
		foreach ($elements as $element){
			$item = '<li>'."\n";
			$item .= '<div class="li"'.$css.'>'."\n";
			if ($params['title'])
				$item .= '<a href="'.$element['link'].'" class="wikilink1" title="'.$element['title'].'">'.$element['title'].'</a>';
			if ($params['author'])
				$item .= $this->getLang('author').$element['author'];
			if ($params['date'])
				$item .= ' '.$this->formatDate($element['pubDate']);
			$item .= "\n";
			$item .= '<div class="detail">'."\n".trim($this->formatDescription($element['description'], $params))."\n".'</div>'."\n";
			$item .= '<div class="level1" style="clear:both;"><strong><a href="'.$element['link'].'">'.$params['textlink'].'</a></strong></div>'."\n";
			$item .= '</div>';
			$item .= '</li>'."\n\n";
			$content .= $item;
		}
		$content .= '</ul><p>';
		return $content;
	}

//TODO à refaire pour les windowsiens!
	 /**
	 * format feed items'dates to adapt them to local wiki config (config:dformat)
	 */
	function formatDate($date){
		global $conf;

		if (false !== strpos($_SERVER['SERVER_SOFTWARE'], 'Win32') ||
			false !== strpos($_SERVER['SERVER_SOFTWARE'], 'Win64'))
			return $date; //strptime() is not implemented on Windows platforms, sorry!

		$decomposed_date = strptime($date, '%a, %d %b %Y %H:%M:%S %z');
		extract($decomposed_date);
		$date = strftime($conf['dformat'],
				mktime($tm_hour, $tm_min, $tm_sec, $tm_mon + 1, $tm_mday, 1900 + $tm_year));
		return $date;
	}

	/**
	 * cut abstracts to desired length, searches the cleaner cut, and close broken tags
 	 */
	function formatDescription($text, $params){
		$cut = $this->cutTextToLength($text, $params['maxlen']);
		$text = preg_replace('/<([a-z]+[^>]*) id="([^>]+)"/', '<$1 id="${2}_htmlabstract_'.microtime(true).'"', $text);
			//time() is used here for W3C compliance (avoid duplicated id's)
		if ($cut && $params['trycleancut']){
			$min = ($params['maxlen'] > 400) ? (200) : ($params['maxlen'] / 2);
			if (FALSE !== ($pos = strrpos($text, "</p>")) && $pos >= $min)
				$text = substr($text, 0, 4 + $pos);
		}
		if ($cut)
			$text .= '... ';
		$text = $this->closeBrokenTags($text);
		return $text;
	}

	/**
	 * brutally cut abstract to desired length without considering html tags
	 */
	function cutTextToLength(&$text, $maxlen){
		$intag = false;
		$i = -1;
		$len = 0;
		$textlen = strlen($text);
		while (++$i < $textlen)
			if ('<' == $text[$i])
				$intag = true;
			elseif ('>' == $text[$i])
				$intag = false;
			elseif (!$intag)
				if ($maxlen == ++$len)
				{
					$text = substr($text, 0, $i);
					return true;
				}
		return false;
	}

	/**
	 * search tags broken by brutal cut and close them
	 */
	function closeBrokenTags($text){
		$tags = array();
		$i = -1;
		$textlen = strlen($text);
		while (++$i < $textlen)
			if ($text[$i] == '<')
			{
				if ($text[$i + 1] != '/' && $text[$i + 1] != '!') //opening tag
				{
					$j = $i;
					while ($text[++$j] != ' ' && $text[$j] != '>');
					array_push($tags, substr($text, $i + 1, $j - $i - 1));
				}
				elseif ($text[$i + 1] == '/') //closing tag
				{
					$j = $i + 1;
					while ($text[++$j] != ' ' && $text[$j] != '>');
					$closed_tag = substr($text, $i + 2, $j - $i - 2);
					while ($tags[count($tags) - 1] != $closed_tag)
						array_pop($tags);
					array_pop($tags);
				}
			}
		while (count($tags))
			$text .= '</'.array_pop($tags).'>';
		return $text;
	}

}

?>
