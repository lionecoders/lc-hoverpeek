<?php
/**
 * Helper functions for LC HoverPeek.
 *
 * @package LC_HoverPeek
 */

namespace LCHoverPeek;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LC_HoverPeek_Helpers {

	private static $instance = null;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public static function lcho_limit_words( $text, $limit = 7 ) {
		$words = explode( ' ', wp_strip_all_tags( $text ) );
		if ( count( $words ) > $limit ) {
			return implode( ' ', array_slice( $words, 0, $limit ) ) . '...';
		}
		return implode( ' ', $words );
	}

	public static function lcho_limit_chars( $text, $limit = 90 ) {
		$text = wp_strip_all_tags( $text );
		if ( mb_strlen( $text ) > $limit ) {
			return mb_substr( $text, 0, $limit ) . '...';
		}
		return $text;
	}

}
