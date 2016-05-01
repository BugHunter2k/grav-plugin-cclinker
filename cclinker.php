<?php
namespace Grav\Plugin;

use Grav\Common\Page\Page;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use Grav\Common\GravTrait;

class CclinkerPlugin extends Plugin
{

	protected $licenses = [
                // License, Text, Image
		"0" => ["No Rights Reserved", "CC0", "cc-zero"],
		"by" => ["Attribution", "CC BY", "by"],
		"by-nd" => ["Attribution-NoDerivs", "CC BY-ND", "by-nd"],
		"by-nc-sa" => ["Attribution-NonCommercial-ShareAlike", "CC BY-NC-SA", "by-nc-sa"],
		"by-sa" => ["Attribution-ShareAlike", "CC BY-SA", "by-sa"],
		"by-nc" => ["Attribution-NonCommercial", "CC BY-NC", "by-nc"],
		"by-nc-nd" => ["Attribution-NonCommercial-NoDerivs", "CC BY-NC-ND", "by-nc-nd"],
		"publicdomain" => ["Public domain", "Public domain", "publicdomain"],
	];

	public static function getSubscribedEvents() {
	    return [
		'onPluginsInitialized' => ['onPluginsInitialized', 0],
	    ];
	}

	public function onPluginsInitialized()
	{
		$this->enable([
		    'onPageContentProcessed' => ['onPageContentProcessed', 0]
		]);
	}

	public function onPageContentProcessed(Event $event)
	{
	    $page = $event['page'];
	    $config = $this->mergeConfig($page);

            if (!$config->get('active', true)) {
		return;
            }

	    $page->setRawContent($this->translateCcCodes($page, $config));
	}

	public function translateCcCodes($page, $config) {
            if ($page && $config->get('enabled')) {
                $this->grav['assets']
                    ->addCss('plugin://cclinker/css/cclinker.css');
                
                $content = $page->getRawContent();
                $content = preg_replace_callback('#\[cc[ -]?((?:0|by)(?:-|nd|nc|sa)*)(?:\|([1-4](?:\.0)?))?\]#Ui',"self::buildLink", $content);
                return $content;
	    }
	}

	protected function buildLink($type) {
		$l = strtolower($type[1]);
		$v = "4.0";
		// Version is in type[2];
		if (!empty($type[2])) {
			$v = $type[2];
			if (strlen($v) == 1) $v .= ".0";
		}
                $locator = GravTrait::getGrav()['locator'];
                $path = "/".$locator->findResource('plugin://cclinker/images/', false)."/";
                switch($this->config->get('plugins.cclinker.outputformat')) {
                    case "text": 
                            $linktext = $this->licenses[$l][1];
                            break;
                    case "icons":
                            $linktext = "";
                            if (substr($this->licenses[$l][2],0,2) !== "cc") {
                                $linktext .= '<img src="'.$path.'icons/cc.png">';
                            } 
                            $parts = explode("-", $this->licenses[$l][2]);
                            foreach ($parts as $icon) {
                                $linktext .= '<img src="'.$path.'icons/'.$icon.'.png">';
                            }
                            break;
                    case "badge":
                            $linktext = '<img class="badge" src="'.$path.'badge/'.$this->licenses[$l][2].'.png">';
                            break;
                    default: // button
                            $linktext = '<img class="button" src="'.$path.'buttons/'.$this->licenses[$l][2].'.png">';
                            break;
                }
 		$link = "<a href=\"";
		$link .= $this->buildDeedUrls($l, $v);
		$link .= "\" class=\"cclink\" title=\"{$this->licenses[$l][0]} $v\">{$linktext}</a>";
		return $link;
	}

	protected function buildDeedUrls($license, $ver="4.0") {
		// TODO get plugin-lang or site-lang
		$lang = "en";

		// cc0 is linked different
		if ($license == "0") {
			return "https://creativecommons.org/publicdomain/zero/1.0/";
		} else {
			return "https://creativecommons.org/licenses/$license/$ver/deed.$lang";
		}
        }
}
