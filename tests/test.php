<?php


require dirname(__DIR__) . '/vendor/autoload.php'; // Autoload files using Composer autoload

use j3j5\TweeStory;
use j3j5\TweePassage;


    $log = '';
	$file = __DIR__ . "/aquienvoto.json";
	try {
		$story = new TweeStory($file);
	} catch(Exception $e) {
		var_dump($e->getMessage());
		exit;
	}

	$current = FALSE;
	while($current = $story->get_current_passage()) {
		echo $current->text . PHP_EOL;
		if(!empty($current->links) && is_array($current->links)) {
			echo 'You can do:'.PHP_EOL;
			foreach($current->links AS $link) {
				echo $link['text'] . PHP_EOL;
			}

			echo PHP_EOL . PHP_EOL;
			$text_next = prompt_answer();
            echo $log;
			$next = search_link($text_next);
			if(!empty($next)) {
				$story->follow_link($next);
			}
		} else {
			break;
		}
		echo PHP_EOL . PHP_EOL;
	}


	function prompt_answer() {
		global $current, $story, $log;
		$handle = fopen ("php://stdin","r");
		$answer = trim(fgets($handle));

		if($answer === 'undo') {
			$story->undo();
			return FALSE;	// The while will pick up the current passage and returning FALSE will avoid the call to follow_link()
		}

		if($answer === 'redo') {
			$story->redo();
			return FALSE;	// The while will pick up the current passage and returning FALSE will avoid the call to follow_link()
		}

        // Is it one of the links?
		foreach($current->links AS $link) {
			if( !empty($answer) && strcasecmp($answer, $link['text']) === 0 ) {
				fclose($handle);
				return $answer;
			}
            $log .= print_r($answer, TRUE) . PHP_EOL;
            $log .= print_r($link['text'], TRUE) . PHP_EOL;
            $log .= print_r(strcasecmp($answer, $link['text']), TRUE) . PHP_EOL;
		}
		fclose($handle);
		return FALSE;
	}

	function search_link($text) {
		global $current;

		foreach($current->links AS $link) {
			if($link['text'] == $text) {
				return $link['link'];
			}
		}
		return FALSE;
	}

