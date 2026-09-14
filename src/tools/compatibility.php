<?php

/**
 * partial copy of wp-includes/compat.php
 * WordPress implementation for PHP functions either missing from older PHP versions or not included by default.
 */

if ( ! function_exists( 'array_find' ) ) {
	/**
	 * Polyfill for `array_find()` function added in PHP 8.4.
	 *
	 * Searches an array for the first element that passes a given callback.
	 *
	 * @since 6.8.0
	 *
	 * @param array    $array    The array to search.
	 * @param callable $callback The callback to run for each element.
	 * @return mixed|null The first element in the array that passes the `$callback`, otherwise null.
	 */
	function array_find( array $array, callable $callback ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound
		foreach ( $array as $key => $value ) {
			if ( $callback( $value, $key ) ) {
				return $value;
			}
		}

		return null;
	}
}

if ( ! function_exists( 'array_find_key' ) ) {
	/**
	 * Polyfill for `array_find_key()` function added in PHP 8.4.
	 *
	 * Searches an array for the first key that passes a given callback.
	 *
	 * @since 6.8.0
	 *
	 * @param array    $array    The array to search.
	 * @param callable $callback The callback to run for each element.
	 * @return int|string|null The first key in the array that passes the `$callback`, otherwise null.
	 */
	function array_find_key( array $array, callable $callback ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound
		foreach ( $array as $key => $value ) {
			if ( $callback( $value, $key ) ) {
				return $key;
			}
		}

		return null;
	}
}

if ( ! function_exists( 'array_any' ) ) {
	/**
	 * Polyfill for `array_any()` function added in PHP 8.4.
	 *
	 * Checks if any element of an array passes a given callback.
	 *
	 * @since 6.8.0
	 *
	 * @param array    $array    The array to check.
	 * @param callable $callback The callback to run for each element.
	 * @return bool True if any element in the array passes the `$callback`, otherwise false.
	 */
	function array_any( array $array, callable $callback ): bool { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound
		foreach ( $array as $key => $value ) {
			if ( $callback( $value, $key ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'array_all' ) ) {
	/**
	 * Polyfill for `array_all()` function added in PHP 8.4.
	 *
	 * Checks if all elements of an array pass a given callback.
	 *
	 * @since 6.8.0
	 *
	 * @param array    $array    The array to check.
	 * @param callable $callback The callback to run for each element.
	 * @return bool True if all elements in the array pass the `$callback`, otherwise false.
	 */
	function array_all( array $array, callable $callback ): bool { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound
		foreach ( $array as $key => $value ) {
			if ( ! $callback( $value, $key ) ) {
				return false;
			}
		}

		return true;
	}
}

if ( ! function_exists( 'array_first' ) ) {
	/**
	 * Polyfill for `array_first()` function added in PHP 8.5.
	 *
	 * Returns the first element of an array.
	 *
	 * @since 6.9.0
	 *
	 * @param array $array The array to get the first element from.
	 * @return mixed|null The first element of the array, or null if the array is empty.
	 */
	function array_first( array $array ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound
		if ( empty( $array ) ) {
			return null;
		}

		foreach ( $array as $value ) {
			return $value;
		}
	}
}

if ( ! function_exists( 'array_last' ) ) {
	/**
	 * Polyfill for `array_last()` function added in PHP 8.5.
	 *
	 * Returns the last element of an array.
	 *
	 * @since 6.9.0
	 *
	 * @param array $array The array to get the last element from.
	 * @return mixed|null The last element of the array, or null if the array is empty.
	 */
	function array_last( array $array ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound
		if ( empty( $array ) ) {
			return null;
		}

		return $array[ array_key_last( $array ) ];
	}
}
