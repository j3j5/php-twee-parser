<?php

/**
 * TweeParser ✍
 *
 * A parser for JSON Twee files to create a meaningful object oriented approach.
 *
 * When using the library, you can know a story has ended by checking the current passage
 * links array, if it is empty it means the current passage is the last one of that storyline.
 *
 * @author Julio ⚓. Foulquie
 * @version 0.1.0
 *
 * 09 May 2015
 */

namespace j3j5;

class TweeStory
{
    private $title;
    private $passages;

    private $scripts;
    private $stylesheets;

    private $current_passage;

    /**
     *
     */
    private $max_history_size;

    /**
     *
     */
    private $history;

    /**
     *
     */
    private $history_offset;

    /**
     *
     */
    private $storyline;

    public function __construct($json)
    {
        $this->history_offset = 0;
        $this->max_history_size = 50;

        if (!empty($json) && is_string($json)) {
            $result = $this->processJson($json);
            if (empty($result)) {
                throw new \Exception("Error processing");
            }
        }
    }

    /**
     *              ⎈⎈⎈⎈⎈⎈⎈⎈⎈⎈
     *              NAVIGATION
     *              ⎈⎈⎈⎈⎈⎈⎈⎈⎈⎈
     */

    /**
     * Get the current position on the story
     *
     * @param void
     *
     * @return TweePassage $current_passage The current passage on the story.
     *
     * @author Julio Foulquie <jfoulquie@gmail.com>
     */
    public function getCurrentPassage()
    {
        return $this->passages[$this->current_passage];
    }

    /**
     * Undo the last action from the user. You can use this function
     * to browse back the actions of the user.
     *
     * @param void
     *
     * @return TweePassage|Bool
     *
     * @author Julio Foulquie <jfoulquie@gmail.com>
     */
    public function undo()
    {
        end($this->history);
        for ($i=0; $i<$this->history_offset; $i++) {
            prev($this->history);
        }
        $index = prev($this->history);
        if (!empty($index)) {
            $this->current_passage = $index;
            $this->history_offset++;
            $this->storyline = array_slice($this->storyline, 0, -1);
            return $this->getCurrentPassage();
        }
        return false;
    }

    /**
     * Redo the last undo from the user. You can use this function
     * to browse forward all the undos of the user.
     *
     * @param void
     *
     * @return TweePassage|Bool
     *
     * @author Julio Foulquie <jfoulquie@gmail.com>
     */
    public function redo()
    {
        end($this->history);
        for ($i=0; $i<$this->history_offset-1; $i++) {
            prev($this->history);
        }
        $index = current($this->history);
        if (!empty($index)) {
            $this->current_passage = $index;
            $this->history_offset--;
            $this->storyline[] = $this->current_passage;
        }
        return $this->getCurrentPassage();
    }

    /**
     * Follow a given link to move on the story.
     *
     * @param String $next_passage The name of the next passage the story should move on
     *
     * @return TweePassage|Bool The new passage on the story or FALSE if it does not exist.
     *
     * @author Julio Foulquie <jfoulquie@gmail.com>
     */
    public function followLink($next_passage)
    {
        // Check whether that's a valid next passage
        $current = $this->getCurrentPassage();
        $valid_link = false;
        if (is_array($current->links)) {
            foreach ($current->links as $link) {
                if ($link['link'] == $next_passage) {
                    $valid_link = true;
                }
            }
        }

        if ($valid_link && isset($this->passages[$next_passage])) {
            $this->current_passage = $next_passage;
            $this->history[] = $this->current_passage;
            $this->storyline[] = $this->current_passage;

            // Reset the redo history
            if ($this->history_offset != 0) {
                $this->history = array_slice($this->history, 0, -$this->history_offset);
                $this->history_offset = 0;
            }
            return $this->getCurrentPassage();
        }
    }

    /**
     * Move the story to a given passage, specially useful to start from the given point.
     *
     * @param string $passage
     *
     * @return bool|TweePassage
     */
    public function moveTo($passage)
    {
        if (isset($this->passages[$passage])) {
            $this->current_passage = $passage;
            $this->history[] = $this->current_passage;
            $this->storyline[] = $this->current_passage;
            return $this->getCurrentPassage();
        }
        return false;
    }

    /**
     *              ☣☣☣☣☣☣☣☣☣☣☣☣
     *              Internal functions
     *              ☣☣☣☣☣☣☣☣☣☣☣☣
     */

    /**
     * Process the JSON exported from Twee and create the story object
     *
     * @param String $json The string containig the JSON object
     *
     * @return void
     *
     * @author Julio Foulquie <jfoulquie@gmail.com>
     */
    private function processJson($json)
    {
        $twee_array = json_decode($json, true);

        if (!isset($twee_array['data']) or empty($twee_array['data'])) {
            throw new RuntimeException("The provided JSON did not decode or is empty.");
        }

        foreach ($twee_array['data'] as $entity) {
            $type = $this->getTypeFromTags($entity['tags']);
            if (empty($type)) {
                $type = $this->getTypeFromTitle($entity['title']);
            }
            switch ($type) {
                case 'passage':
                    $passage = new TweePassage($entity);
                    $this->passages[$passage->title] = $passage;
                    // Set the start passage
                    if (in_array('start', $passage->tags)) {
                        $this->current_passage = $passage->title;
                        $this->history[] = $this->current_passage;
                        $this->storyline[] = $this->current_passage;
                    }
                    break;
                case 'title':
                    $this->title = $entity['text'];
                    break;
                case 'script':
                    $this->scripts[] = $entity['text'];
                    break;
                case 'stylesheet':
                    $this->stylesheets[] = $entity['text'];
                    break;
                default:
                    throw new \RuntimeException("Unknown entity type!");
            }
        }
        return true;
    }

    /**
     * Get the type of entity based on its tags.
     *
     * @param Array $tags The tags of the entity
     *
     * @return String|Bool The type of entity or FALSE
     *
     * @author Julio Foulquie <jfoulquie@gmail.com>
     */
    private function getTypeFromTags($tags)
    {
        $tag_types = array('stylesheet', 'script');

        foreach ($tag_types as $tag) {
            if (in_array($tag, $tags)) {
                return $tag;
            }
        }

        return false;
    }

    /**
     * Get the type of entity based on its title.
     *
     * @param String $title The title of the entity
     *
     * @return String The type of entity
     *
     * @author Julio Foulquie <jfoulquie@gmail.com>
     */
    private function getTypeFromTitle($title)
    {
        $title_types = array(
            'stylesheet'    => 'UserStylesheet',
            'script'        => 'UserScript',
            'title'         => 'StoryTitle',
        );

        foreach ($title_types as $type => $name) {
            if ($title == $name) {
                return $type;
            }
        }

        // Let's assume if the title isn't on the list, the entity is a passage
        return 'passage';
    }
}
