<?php
/*
Plugin Name: PHP Article Gallery Plugin
Plugin URI: http://codecanyon.net/item/php-auto-gallery-with-expanding-preview/4519220
Description: PHP Article Gallery
Author: Vu Khanh Truong
Version: 1.0
Author URI:
*/

function catch_that_image($post_content) {
	$first_img = '';
	ob_start();
	ob_end_clean();
	$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post_content, $matches);
	$first_img = $matches [1] [0];
	//echo '<pre>';print_r($matches);echo '</pre>';
	if(empty($first_img)){ //Defines a default image
		$first_img = "http://placehold.it/250x250";
	}
	return $first_img;
}

/**
 * Truncates text.
 *
 * Cuts a string to the length of $length and replaces the last characters
 * with the ellipsis if the text is longer than length.
 *
 * ### Options:
 *
 * - `ellipsis` Will be used as Ending and appended to the trimmed string (`ending` is deprecated)
 * - `exact` If false, $text will not be cut mid-word
 * - `html` If true, HTML tags would be handled correctly
 *
 * @param string $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param array $options An array of html attributes and options.
 * @return string Trimmed string.
 */
function wp_article_truncate($text, $length = 100, $options = array()) {
	$default = array(
		'ellipsis' => '...', 'exact' => true, 'html' => false
	);
	if (isset($options['ending'])) {
		$default['ellipsis'] = $options['ending'];
	} elseif (!empty($options['html']) && Configure::read('App.encoding') == 'UTF-8') {
		$default['ellipsis'] = "\xe2\x80\xa6";
	}
	$options = array_merge($default, $options);
	extract($options);

	if (!function_exists('mb_strlen')) {
		class_exists('Multibyte');
	}

	if ($html) {
		if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
			return $text;
		}
		$totalLength = mb_strlen(strip_tags($ellipsis));
		$openTags = array();
		$truncate = '';

		preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
		foreach ($tags as $tag) {
			if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
				if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
					array_unshift($openTags, $tag[2]);
				} elseif (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
					$pos = array_search($closeTag[1], $openTags);
					if ($pos !== false) {
						array_splice($openTags, $pos, 1);
					}
				}
			}
			$truncate .= $tag[1];

			$contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
			if ($contentLength + $totalLength > $length) {
				$left = $length - $totalLength;
				$entitiesLength = 0;
				if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
					foreach ($entities[0] as $entity) {
						if ($entity[1] + 1 - $entitiesLength <= $left) {
							$left--;
							$entitiesLength += mb_strlen($entity[0]);
						} else {
							break;
						}
					}
				}

				$truncate .= mb_substr($tag[3], 0 , $left + $entitiesLength);
				break;
			} else {
				$truncate .= $tag[3];
				$totalLength += $contentLength;
			}
			if ($totalLength >= $length) {
				break;
			}
		}
	} else {
		if (mb_strlen($text) <= $length) {
			return $text;
		}
		$truncate = mb_substr($text, 0, $length - mb_strlen($ellipsis));
	}
	if (!$exact) {
		$spacepos = mb_strrpos($truncate, ' ');
		if ($html) {
			$truncateCheck = mb_substr($truncate, 0, $spacepos);
			$lastOpenTag = mb_strrpos($truncateCheck, '<');
			$lastCloseTag = mb_strrpos($truncateCheck, '>');
			if ($lastOpenTag > $lastCloseTag) {
				preg_match_all('/<[\w]+[^>]*>/s', $truncate, $lastTagMatches);
				$lastTag = array_pop($lastTagMatches[0]);
				$spacepos = mb_strrpos($truncate, $lastTag) + mb_strlen($lastTag);
			}
			$bits = mb_substr($truncate, $spacepos);
			preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
			if (!empty($droppedTags)) {
				if (!empty($openTags)) {
					foreach ($droppedTags as $closingTag) {
						if (!in_array($closingTag[1], $openTags)) {
							array_unshift($openTags, $closingTag[1]);
						}
					}
				} else {
					foreach ($droppedTags as $closingTag) {
						$openTags[] = $closingTag[1];
					}
				}
			}
		}
		$truncate = mb_substr($truncate, 0, $spacepos);
	}
	$truncate .= $ellipsis;

	if ($html) {
		foreach ($openTags as $tag) {
			$truncate .= '</' . $tag . '>';
		}
	}

	return $truncate;
}

/**
 * Extracts an excerpt from the text surrounding the phrase with a number of characters on each side
 * determined by radius.
 *
 * @param string $text String to search the phrase in
 * @param string $phrase Phrase that will be searched for
 * @param integer $radius The amount of characters that will be returned on each side of the founded phrase
 * @param string $ellipsis Ending that will be appended
 * @return string Modified string
  */
function wp_article_excerpt($text, $phrase, $radius = 100, $ellipsis = '...') {
	if (empty($text) || empty($phrase)) {
		return wp_article_truncate($text, $radius * 2, array('ellipsis' => $ellipsis));
	}

	$append = $prepend = $ellipsis;

	$phraseLen = mb_strlen($phrase);
	$textLen = mb_strlen($text);

	$pos = mb_strpos(mb_strtolower($text), mb_strtolower($phrase));
	if ($pos === false) {
		return mb_substr($text, 0, $radius) . $ellipsis;
	}

	$startPos = $pos - $radius;
	if ($startPos <= 0) {
		$startPos = 0;
		$prepend = '';
	}

	$endPos = $pos + $phraseLen + $radius;
	if ($endPos >= $textLen) {
		$endPos = $textLen;
		$append = '';
	}

	$excerpt = mb_substr($text, $startPos, $endPos - $startPos);
	$excerpt = $prepend . $excerpt . $append;

	return $excerpt;
}

include 'shortcodes.php';
?>
