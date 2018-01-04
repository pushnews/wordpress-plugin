<?php

/*
Author:             Pushnews <developers@pushnews.eu>
License:            GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**
 * Class PushnewsBase64Url was adapted from spomky-labs/base64url
 * @url https://github.com/Spomky-Labs/base64url
 */
class PushnewsBase64Url {

	/**
	 * @param string $data The data to encode
	 * @param bool $use_padding If true, the "=" padding at end of the encoded value are kept, else it is removed
	 *
	 * @return string The data encoded
	 */
	public static function encode( $data, $use_padding = false ) {
		$encoded = strtr( base64_encode( $data ), '+/', '-_' );

		return true === $use_padding ? $encoded : rtrim( $encoded, '=' );
	}

	/**
	 * @param string $data The data to decode
	 *
	 * @return string The data decoded
	 */
	public static function decode( $data ) {
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}

}