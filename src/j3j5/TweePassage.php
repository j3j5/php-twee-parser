<?php namespace j3j5;

/**
 * TweePassage
 *
 * An object to represent the Twee entity (passage).
 *
 * @author Julio ⚓. Foulquié
 * @version 0.1.0
 *
 * 09 May 2015
 */

class TweePassage
{
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

    public function __construct(array $passage)
    {
        if (!isset($passage['title'], $passage['text'], $passage['tags'])) {
            throw new \Exception("Wrong entity");
        }

        $this->parsePassage($passage);
    }

    private function parsePassage($passage)
    {
        $this->title        = trim($passage['title']);
        $this->modifier     = $passage['modifier'];
        $this->tags         = $passage['tags'];
        $this->created      = $passage['created'];
        $this->modified     = $passage['modified'];
        $this->links        = [];
        $this->raw_links    = [];
        $this->parseLinks($passage['text']);

        // Clean up links from the text
        foreach ($this->raw_links as $link) {
            $passage['text'] = str_replace($link, '', $passage['text']);
        }
        $this->text = $passage['text'];
    }

    /**
     * Parse all links for the given text passage based on the pattern for Twee
     *
     * @param string $text
     *
     */
    private function parseLinks($text)
    {
        $offset = 0;
        $matches = array();
        // Extract all links from the text of the passage (if they exist)
        while (preg_match($this->links_pattern, $text, $matches, 0, $offset)) {
            switch ($matches[2]) {
                case '->':
                    $this->links[] = array('text' => trim($matches[1]), 'link' => trim($matches[3]));
                    $this->raw_links[] = $matches[0];
                    break;
                case '<-':
                case '|':
                    $this->links[] = array('text' => trim($matches[3]), 'link' => trim($matches[1]));
                    $this->raw_links[] = $matches[0];
                    break;
                default:
                    throw new \RuntimeException("Something that looked like a link could not be parsed.");
            }
            $offset = mb_stripos($text, $matches[0]) + mb_strlen($matches[0]);
        }
    }
}
