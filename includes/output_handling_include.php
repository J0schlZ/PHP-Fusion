<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) 2002 - 2011 Nick Jones
| https://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: output_handling_include.php
| Author: Max Toball (Matonor)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
namespace PHPFusion {
	//TODO: PHPDoc comments
	class OutputHandler {
		/**
		 * Associative array of meta tags
		 *
		 * @var string[]
		 */
		private static $pageMeta = array();
		
		/**
		 * The title in the "title" tag
		 *
		 * @var string
		 */
		private static $pageTitle = "";
		
		/**
		 * PHP code to execute using eval and regexps to replace anything in the output
		 *
		 * @var string
		 */
		private static $pageReplacements = "";
		
		/**
		 * PHP code to execute using eval replace anything in the output
		 *
		 * @var string
		 */
		private static $outputHandlers = "";
		
		/**
		 * Additional tags to the html head
		 *
		 * @var string 
		 */
		public static $pageHeadTags = "";
		
		/**
		 * Additional contents to the footer
		 *
		 * @var string
		 */
		public static $pageFooterTags = "";
		
		/**
		 * Additional javascripts
		 *
		 * @var string
		 */
		public static $jqueryTags = "";
		
		/**
		 * Set the new title of the page
		 * 
		 * @param string $title
		 */
		public static function setTitle($title = "") {
			self::$pageTitle = $title;
		}
		
		/**
		 * Append something to the title of the page
		 * 
		 * @param type $addition
		 */
		public static function addToTitle($addition = "") {
			self::$pageTitle .= preg_replace("/".$GLOBALS['locale']['global_200']."/", '', $addition, 1);
		}
		
		/**
		 * Set a meta tag by name
		 * 
		 * @param string $name
		 * @param string $content
		 */
		public static function setMeta($name, $content = "") {
			self::$pageMeta[$name] = $content;
		}
		
		/**
		 * Append something to a meta tag
		 * 
		 * @param string $name
		 * @param string $addition
		 */
		public static function addToMeta($name, $addition = "") {
			$settings = \fusion_get_settings();
			if (empty(self::$pageMeta)) {
				self::$pageMeta =  array(
					"description" => $settings['description'], 
					"keywords" => $settings['keywords']
				);
			}
			if (isset(self::$pageMeta[$name])) {
				self::$pageMeta[$name] .= $addition;
			}
		}
		
		/**
		 * Add content to the html head
		 * 
		 * @param string $tag
		 */
		public static function addToHead($tag = "") {
			if (!\stristr(self::$pageHeadTags, $tag)) {
				self::$pageHeadTags .= $tag."\n";
			}
		}
		
		/**
		 * Add content to the footer
		 * 
		 * @param string $tag
		 */
		public static function addToFooter($tag = "") {
			if (!stristr(self::$pageFooterTags, $tag)) {
				self::$pageFooterTags .= $tag."\n";
			}
		}
		
		/**
		 * Replace something in the output using regexp
		 * 
		 * @param string $target Regexp pattern without delimiters
		 * @param string $replace The new content
		 * @param string $modifiers Regexp modifiers
		 */
		public static function replaceInOutput($target, $replace, $modifiers = "") {
			self::$pageReplacements .= "\$output = preg_replace('^$target^$modifiers', '$replace', \$output);";
		}
		
		/**
		 * Add a new output handler function
		 * 
		 * @param string $name The name of the output handler function
		 */
		public static function addHandler($name) {
			if (!empty($name)) {
				self::$outputHandlers .= "\$output = $name(\$output);";
			}
		}
		
		/**
		 * Add handler to the $permalink object
		 * 
		 * @param string $name
		 */
		public static function addPermalinkHandler($name) {
			//TODO: test it
			$settings = \fusion_get_settings();
			if (!empty($name) && $settings['site_seo']) {
				self::$outputHandlers .= "\$permalink->AddHandler(\"$name\");";
			}
		}
		
		/**
		 * Add javascript source code to the output
		 * 
		 * @param string $tag
		 */
		public static function addToJQuery ($tag = "") {
			self::$jqueryTags .= $tag;
		}
		
		/**
		 * Add a link to the breadcrumb
		 * 
		 * @global array $breadcrumbs
		 * @param array $link Keys: link, title
		 */
		public static function addToBreadCrumbs(array $link = array()) {
			//TODO: remove global variable
			global $breadcrumbs;
			if (!empty($link)) {
				$breadcrumbs[] = $link;
			}
		}
		
		/**
		 * Execute the output handlers
		 * 
		 * @global \PermalinksDisplay $permalink
		 * @param string $output
		 * @return string
		 */
		public static function handleOutput($output) {
			//TODO: remove global variables
			global $permalink;
			$settings = \fusion_get_settings();
			
			if (!empty(self::$pageHeadTags)) {
				$output = preg_replace("#</head>#", self::$pageHeadTags."</head>", $output, 1);
			}

			if (self::$pageTitle != $settings['sitename']) {
				$output = preg_replace("#<title>.*</title>#i", "<title>".self::$pageTitle.$GLOBALS['locale']['global_200'].$settings['sitename']."</title>", $output, 1);
			}

			if (!empty(self::$pageMeta)) {
				foreach (self::$pageMeta as $name => $content) {
					$output = preg_replace("#<meta (http-equiv|name)='$name' content='.*' />#i", "<meta \\1='".$name."' content='".$content."' />", $output, 1);
				}
			}

			if (!empty(self::$pageReplacements)) {
				eval(self::$pageReplacements);
			}
			if (!empty(self::$outputHandlers)) {
				eval(self::$outputHandlers);
			}

			return $output;
		}
	}
}
namespace {
	use PHPFusion\OutputHandler;
	/**
	 * Set the new title of the page
	 * 
	 * @param string $title
	 */
	function set_title($title = "") {
		OutputHandler::setTitle($title);
	}

	/**
	 * Append something to the title of the page
	 * 
	 * @param type $addition
	 */
	function add_to_title($addition = "") {
		OutputHandler::addToTitle($addition);
	}

	/**
	 * Set a meta tag by name
	 * 
	 * @param string $name
	 * @param string $content
	 */
	function set_meta($name, $content = "") {
		OutputHandler::setMeta($name, $content);
	}

	/**
	 * Append something to a meta tag
	 * 
	 * @param string $name
	 * @param string $addition
	 */
	function add_to_meta($name, $addition = "") {
		OutputHandler::addToMeta($name, $addition);
	}

	/**
	 * Add content to the html head
	 * 
	 * @param string $tag
	 */
	function add_to_head($tag = "") {
		OutputHandler::addToHead($tag);
	}

	/**
	 * Add content to the footer
	 * 
	 * @param string $tag
	 */
	function add_to_footer($tag = "") {
		OutputHandler::addToFooter($tag);
	}

	/**
	 * Replace something in the output using regexp
	 * 
	 * @param string $target Regexp pattern without delimiters
	 * @param string $replace The new content
	 * @param string $modifiers Regexp modifiers
	 */
	function replace_in_output($target, $replace, $modifiers = "") {
		OutputHandler::replaceInOutput($target, $replace, $modifiers);
	}

	/**
	 * Add a new output handler function
	 * 
	 * @param string $name The name of the output handler function
	 */
	function add_handler($name) {
		OutputHandler::addHandler($name);
	}

	/**
	 * Add handler to the $permalink object
	 * 
	 * @param string $name
	 */
	function add_permalink_handler($name) {
		OutputHandler::addPermalinkHandler($name);
	}

	/**
	 * Execute the output handlers
	 * 
	 * @param string $output
	 * @return string
	 */
	function handle_output($output) {
		return OutputHandler::handleOutput($output);
	}

	/**
	 * Add javascript source code to the output
	 * 
	 * @param string $tag
	 */
	function add_to_jquery($tag = "") {
		OutputHandler::addToJQuery($tag);
	}
	
	/**
	 * Add a link to the breadcrumb
	 * 
	 * @global array $breadcrumbs
	 * @param array $link Keys: link, title
	 */
	function add_to_breadcrumbs(array $link=array()) {
		OutputHandler::addToBreadCrumbs($link);
	}
}
?>