<?php

class SSWP_File_Regex_Pattern_Creator {

	private $extensions;
	private $extensionMap;

	public function __construct( array $extensions = array(), array $extensionMap = array() ) {
		$this->extensions   = $extensions;
		$this->extensionMap = $extensionMap;
	}

	/**
	 * Get the extensions array.
	 *
	 * @return array|null The extensions array or null if not set.
	 */
	public function getExtensions(): ?array {
		return $this->extensions;
	}

	/**
	 * Set the extensions array.
	 *
	 * @param array $extensions The extensions array to set.
	 * @return $this
	 */
	public function setExtensions( array $extensions ): self {
		$this->extensions = $extensions;
		return $this;
	}

	/**
	 * Get the extension map array.
	 *
	 * @return array|null The extension map array or null if not set.
	 */
	public function getExtensionMap(): ?array {
		return $this->extensionMap;
	}

	/**
	 * Set the extension map array.
	 *
	 * @param array $extensionMap The extension map array to set.
	 * @return $this
	 */
	public function setExtensionMap( array $extensionMap ): self {
		$this->extensionMap = $extensionMap;
		return $this;
	}



	/**
	 * Generate a regular expression for use in Apache 2.4 <Files> directive
	 * that matches files with any of the given extensions.
	 *
	 * @return string A regular expression pattern compatible with Apache 2.4.
	 *
	 * @example
	 * Input extensions: ['jpg', 'png', 'gif']
	 * Input extension map: ['jpg' => 'jpe?g', 'tif' => 'tiff?']
	 * Output: '\.(jpe?g|png|gif)$'
	 *
	 * This regex can be used in an Apache configuration like this:
	 * <Files ~ "\.(jpe?g|png|gif)$">
	 *     # Your Apache directives here
	 * </Files>
	 *
	 * It will match:
	 * - "image.jpg"
	 * - "photo.PNG"
	 * - "animation.gif"
	 *
	 * It will not match:
	 * - "document.pdf"
	 * - "script.js"
	 * - "file.jpgg"
	 */
	public function generateApacheExtensionRegex(): string {
		if ( empty( $this->extensions ) ) {
			throw new \InvalidArgumentException( 'Extensions array cannot be empty' );
		}

		$processedExtensions = array_map(
			function ( $ext ) {
				$lowerExt = strtolower( $ext );
				return $this->extensionMap[ $lowerExt ] ?? preg_quote( $lowerExt, '/' );
			},
			$this->extensions
		);

		// Remove duplicates that might have been introduced by the mapping
		$processedExtensions = array_unique( $processedExtensions );

		// Join the extensions with the OR operator
		$extensionPattern = implode( '|', $processedExtensions );

		// Create the final regex pattern
		$regexPattern = '\.(' . $extensionPattern . ')$';

		return $regexPattern;
	}
}


