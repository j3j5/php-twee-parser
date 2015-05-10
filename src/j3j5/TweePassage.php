<?php

/**
 * TweePassage
 *
 * An object to represent the Twee entity (passage).
 *
 * @author Julio Foulquié
 * @version 0.1.0
 *
 * 09 May 2015
 */

namespace j3j5;

class TweePassage {

	private $log;
	private $raw_links;

	public $title;
	public $text;
	public $links;
	public $modifier;
	public $tags;
	public $created;
	public $modified;

	/**
	 * Valid link patterns for Title="Open the cage door" and Action="Devoured by Lions" are:
	 * 	-) [[Open the cage door->Devoured by Lions]]
	 * 	-) [[Devoured by Lions<-Open the cage door]]
	 * 	-) [[Devoured by Lions|Open the cage door]]
	 */
	private $links_pattern = "/\[\[(.*?)([<\->]{2}|\|)(.*?)\]\]/i";

	public function __construct(array $passage, $log) {
		if(!isset($passage['title'], $passage['text'], $passage['tags'])) {
			throw new \Exception("Wrong entity");
		}

		$this->log = $log;
		$this->parse_passage($passage);
	}

	private function parse_passage($passage) {
		$this->title		= $passage['title'];
		$this->modifier		= $passage['modifier'];
		$this->tags			= $passage['tags'];
		$this->created		= $passage['created'];
		$this->modified		= $passage['modified'];
		$this->links		= array();
		$this->raw_links	= array();
		$this->parse_links($passage['text']);
		// Clean up links from the text
		foreach($this->raw_links AS $link) {
			$passage['text'] = str_replace($link, '', $passage['text']);
		}
		$this->text		= $passage['text'];
	}

	private function parse_links($text) {
		$offset = 0;
		$matches = array();
		// Extract all links from the text of the passage (if they exist)
		while(preg_match($this->links_pattern, $text, $matches, 0, $offset)) {
			switch($matches[2]) {
				case '->':
					$this->links[] = array('text' => $matches[1], 'link' => $matches[3]);
					$this->raw_links[] = $matches[0];
					break;
				case '<-':
				case '|':
					$this->links[] = array('text' => $matches[3], 'link' => $matches[1]);
					$this->raw_links[] = $matches[0];
					break;
				default:
					$this->log->addWarning("Something that looked like a link could not be parsed.");
					$this->log->addWarning($text);
					break;
			}
			$offset = mb_stripos($text, $matches[0]) + mb_strlen($matches[0]);
		}
	}

}
