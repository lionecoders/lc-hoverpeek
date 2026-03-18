<?php
/**
 * Core functionality for LC HoverPeek.
 *
 * @package LC_HoverPeek
 */

namespace LCHoverPeek;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LC_HoverPeek_Core {

	private static $instance = null;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'lchp_enqueue_assets' ] );
		add_filter( 'the_content', [ $this, 'lchp_inject_post_id' ] );
		add_action( 'wp_ajax_lc_hoverpeek_preview', [ $this, 'lchp_preview' ] );
		add_action( 'wp_ajax_nopriv_lc_hoverpeek_preview', [ $this, 'lchp_preview' ] );
		add_action( 'wp_ajax_lc_hoverpeek_batch', [ $this, 'lchp_batch' ] );
		add_action( 'wp_ajax_nopriv_lc_hoverpeek_batch', [ $this, 'lchp_batch' ] );
	}

	public function lchp_enqueue_assets() {
		// Performance optimization: only load on singular pages and if the filter is active.
		if ( ! is_singular() || ! has_filter( 'the_content', [ $this, 'lchp_inject_post_id' ] ) ) {
			return;
		}

		$supported_types = get_option( 'lchp_types', [ 'post' ] );
		if ( ! is_array( $supported_types ) ) {
			$supported_types = [];
		}

		if ( ! in_array( get_post_type(), $supported_types, true ) ) {
			return;
		}

		wp_enqueue_style(
			'lchp-style',
			LCHP_PLUGIN_URL . 'assets/hoverpeek.css',
			[],
			LCHP_VERSION
		);

		wp_enqueue_script(
			'lchp-script',
			LCHP_PLUGIN_URL . 'assets/hoverpeek.js',
			[ 'jquery' ],
			LCHP_VERSION,
			true
		);

		wp_localize_script(
			'lchp-script',
			'LC_HOVERPEEK',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'lc_hoverpeek_nonce' ),
			]
		);

		/* Dynamic color settings */
		$bg_color      = get_option( 'lchp_bg_color', '#111111' );
		$title_color   = get_option( 'lchp_title_color', '#ffffff' );
		$excerpt_color = get_option( 'lchp_excerpt_color', '#ffffff' );
		$link_color    = get_option( 'lchp_link_color', '#4da3ff' );

		$custom_css = "
			.lchp-popup { background: " . esc_html( $bg_color ) . "; }
			.lchp-popup h4 { color: " . esc_html( $title_color ) . "; }
			.lchp-popup p { color: " . esc_html( $excerpt_color ) . "; }
			.lchp-more { color: " . esc_html( $link_color ) . "; }
		";

		wp_add_inline_style( 'lchp-style', $custom_css );
	}

	public function lchp_inject_post_id( $content ) {
		if ( is_admin() || ! is_main_query() ) {
			return $content;
		}

		$supported_types = get_option( 'lchp_types', [ 'post' ] );
		if ( ! is_array( $supported_types ) ) {
			$supported_types = [];
		}

		if ( ! in_array( get_post_type(), $supported_types, true ) ) {
			return $content;
		}

		if ( false === strpos( $content, '<a' ) ) {
			return $content;
		}

		$site_url = get_site_url();
		$enable_internal = get_option( 'lchp_enable_internal', 1 );
		$enable_external = get_option( 'lchp_enable_external', 0 );

		return preg_replace_callback(
			'/<a\s+([^>]*?)href=["\']([^"\']+)["\']([^>]*)>/i',
			function ( $matches ) use ( $site_url, $enable_internal, $enable_external ) {

				$url     = $matches[2];
				$post_id = url_to_postid( $url );

				if ( $post_id ) {
					if ( ! $enable_internal ) {
						return $matches[0];
					}

					return sprintf(
						'<a data-lchp-post="%1$d"%2$shref="%3$s"%4$s>',
						absint( $post_id ),
						$matches[1],
						esc_url( $url ),
						$matches[3]
					);
				}

				if ( strpos( $url, $site_url ) === false && strpos( $url, 'http' ) === 0 ) {
					if ( ! $enable_external ) {
						return $matches[0];
					}

					return sprintf(
						'<a data-lchp-url="%1$s"%2$shref="%3$s"%4$s>',
						esc_url( $url ),
						$matches[1],
						esc_url( $url ),
						$matches[3]
					);
				}

				return $matches[0];
			},
			$content
		);
	}

	public function lchp_preview() {
		check_ajax_referer( 'lc_hoverpeek_nonce', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$url     = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		if ( $post_id ) {
			$cache_key = 'lchp_int_' . $post_id;
			$cached    = get_transient( $cache_key );

			if ( $cached ) {
				wp_send_json_success( $cached );
			}

			$post = get_post( $post_id );

			if ( ! $post ) {
				wp_send_json_error();
			}

			$data = [
				'post_id' => $post_id,
				'title'   => wp_trim_words( get_the_title( $post ), 10 ),
				'excerpt' => wp_trim_words( get_the_excerpt( $post ), 20 ),
				'link'    => get_permalink( $post ),
				'image'   => get_the_post_thumbnail_url( $post, 'large' ),
			];

			set_transient( $cache_key, $data, 2 * HOUR_IN_SECONDS );
			wp_send_json_success( $data );
		}

		if ( $url ) {
			$cache_key = 'lchp_ext_' . md5( $url );
			$cached    = get_transient( $cache_key );

			if ( $cached ) {
				wp_send_json_success( $cached );
			}

			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 6,
					'redirection' => 5,
					'httpversion' => '1.1',
					'headers'     => array(
						'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36'
					)
				)
			);

			if ( is_wp_error( $response ) ) {
				wp_send_json_error();
			}

			$body = wp_remote_retrieve_body( $response );

			preg_match( '/<title>(.*?)<\/title>/is', $body, $title );
			preg_match( '/<meta name="description" content="(.*?)"/is', $body, $desc );
			preg_match( '/<meta property="og:image" content="(.*?)"/is', $body, $img );

			$data = [
				'post_id' => 0,
				'title'   => $title[1] ?? '',
				'excerpt' => $desc[1] ?? '',
				'link'    => $url,
				'image'   => $img[1] ?? '',
			];

			set_transient( $cache_key, $data, 2 * HOUR_IN_SECONDS );
			wp_send_json_success( $data );
		}

		wp_send_json_error();
	}

	public function lchp_batch() {
		check_ajax_referer( 'lc_hoverpeek_nonce', 'nonce' );

		$result = [];
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$links  = isset( $_POST['links'] ) ? (array) wp_unslash( $_POST['links'] ) : [];

		foreach ( $links as $item ) {
			$post_id = absint( $item['post_id'] );
			$url     = esc_url_raw( $item['url'] );

			if ( $post_id ) {
				$cache_key = 'lchp_int_' . $post_id;
				$cached    = get_transient( $cache_key );

				if ( $cached ) {
					$result[] = $cached;
					continue;
				}

				$post = get_post( $post_id );
				if ( $post ) {
					$data = [
						'post_id' => $post_id,
						'title'   => wp_trim_words( get_the_title( $post ), 10 ),
						'excerpt' => wp_trim_words( get_the_excerpt( $post ), 20 ),
						'link'    => get_permalink( $post ),
						'image'   => get_the_post_thumbnail_url( $post, 'large' ),
					];

					set_transient( $cache_key, $data, 2 * HOUR_IN_SECONDS );
					$result[] = $data;
				}
			}

			if ( $url ) {
				$cache_key = 'lchp_ext_' . md5( $url );
				$cached    = get_transient( $cache_key );

				if ( $cached ) {
					$result[] = $cached;
					continue;
				}

				$response = wp_remote_get(
					$url,
					array(
						'timeout'     => 6,
						'redirection' => 5,
						'httpversion' => '1.1',
						'headers'     => array(
							'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36'
						)
					)
				);

				if ( is_wp_error( $response ) ) {
					continue;
				}

				$body = wp_remote_retrieve_body( $response );

				preg_match( '/<title>(.*?)<\/title>/is', $body, $title );
				preg_match( '/<meta name="description" content="(.*?)"/is', $body, $desc );
				preg_match( '/<meta property="og:image" content="(.*?)"/is', $body, $img );

				$data = [
					'post_id' => 0,
					'title'   => $title[1] ?? '',
					'excerpt' => $desc[1] ?? '',
					'link'    => $url,
					'image'   => $img[1] ?? '',
				];

				set_transient( $cache_key, $data, 2 * HOUR_IN_SECONDS );
				$result[] = $data;
			}
		}

		wp_send_json_success( $result );
	}
}
