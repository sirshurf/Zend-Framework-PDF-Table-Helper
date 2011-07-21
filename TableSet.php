<?php

/**
 * Encapsulates common logic for handling creation of PDF files.
 *
 * Currently defined table options:
 * align => center, justify
 *
 * Currently defined column options:
 * align => left, right, or center : string
 * bold => true, false : boolean
 * indent-left => N : int
 * border-right => N,N,N : int,int,int - The color of the border, 0-1 scale (not 0-255)
 * colspan => N : int - Like HTML colspan.
 * color => N,N,N : int,int,int - The color of the text, 0-1 scale (not 0-255)
 *
 */
class SirShurf_Pdf_TableSet {
	/**
	 * Stores the paper size of the final PDF.
	 * Zend_Pdf_Page::SIZE_A4
	 * Zend_Pdf_Page::SIZE_A4_LANDSCAPE
	 * Zend_Pdf_Page::SIZE_LETTER
	 * Zend_Pdf_Page::SIZE_LETTER_LANDSCAPE
	 * @var int
	 */
	private $_paperSize;
	
	/**
	 * Size of margins on the page. Units are Points.
	 *
	 * @var int
	 */
	private $_sideMargin;
	private $_heightMargin;
	
	/**
	 * The Font to use for text output. Options are:
	 * Zend_Pdf_Font::FONT_COURIER
	 * Zend_Pdf_Font::FONT_COURIER_BOLD
	 * Zend_Pdf_Font::FONT_COURIER_OBLIQUE (identical to Zend_Pdf_Font::FONT_COURIER_ITALIC)
	 * Zend_Pdf_Font::FONT_COURIER_BOLD_OBLIQUE (identical to Zend_Pdf_Font::FONT_COURIER_BOLD_ITALIC)
	 * Zend_Pdf_Font::FONT_HELVETICA
	 * Zend_Pdf_Font::FONT_HELVETICA_BOLD
	 * Zend_Pdf_Font::FONT_HELVETICA_OBLIQUE (identical to Zend_Pdf_Font::FONT_HELVETICA_ITALIC)
	 * Zend_Pdf_Font::FONT_HELVETICA_BOLD_OBLIQUE (identical to Zend_Pdf_Font::FONT_HELVETICA_BOLD_ITALIC)
	 * Zend_Pdf_Font::FONT_SYMBOL
	 * Zend_Pdf_Font::FONT_TIMES_ROMAN
	 * Zend_Pdf_Font::FONT_TIMES
	 * Zend_Pdf_Font::FONT_TIMES_BOLD
	 * Zend_Pdf_Font::FONT_TIMES_ITALIC
	 * Zend_Pdf_Font::FONT_ZAPFDINGBATS
	 *
	 * @var object
	 */
	private $_font;
	private $_fontBold;
	
	/**
	 * The font size to use for text output. Units are Points.
	 * The type stores the currently used type.
	 *
	 * @var int
	 */
	private $_fontSize;
	private $_fontType;
	
	/**
	 * Height and Width of the page. Based off the paper size. Units are Points.
	 *
	 * @var int
	 * @var int
	 */
	private $_maxHeight;
	private $_maxWidth;
	
	/**
	 * The header image to be loaded as the first element in the resultant PDF.
	 *
	 * @var string
	 */
	private $_headerImage;
	
	/**
	 * The signature image to be added when the report is signed.
	 *
	 * @var string
	 */
	private $_footerImage;
	
	/**
	 * Stores the meta data for layout of the PDF in [row][column] format.
	 *
	 * @var array
	 */
	private $_tables;
	
	/**
	 * The current working table pointer.
	 *
	 * @var int
	 */
	private $_numTables;
	
	/**
	 * 
	 * Zend Pdf Object
	 * @var Zend_Pdf
	 */
	private $_pdf;
	
	private $_currentPage;
	private $_currentHeight;
	
	public function __construct(Zend_Pdf $pdf = null, $options = null) {
		if (empty ( $pdf )) {
			// Creat the PDF Object.
			$pdf = new Zend_Pdf ();
		}
		
		$this->_setOptions ( $options )->setPdf ( $pdf )->_init ();
	
	}
	
	/**
	 * 
	 * Init Global options
	 * @return SirShurf_Pdf_TableSet
	 */
	private function _init() {
		// Setup Initial Variables
		$this->_numTables = 0;
		$this->_tables = array ();
		
		if (is_null ( $this->_currentPage )) {
			// First Page
			$this->setPage ( 0 );
			$pdf->pages [$this->_currentPage] = $this->_pdf->newPage ( $this->_paperSize );
		}
		
		if (is_null ( $this->_currentHeight )) {
			// First Page
			$this->setHeight ( $this->_maxHeight );
		}
		
		return $this;
	}
	
	public function setPage($page) {
		$this->_currentPage = $page;
		return $this;
	}
	
	public function setHeight($height) {
		$this->_currentHeight = $height;
		return $this;
	}
	
	public function getPage() {
		return $this->_currentPage;
	}
	
	/**
	 * 
	 * Set Zend Pdf Object
	 * @param Zend_Pdf $pdf
	 * @return SirShurf_Pdf_TableSet
	 */
	public function setPdf(Zend_Pdf $pdf) {
		$this->_pdf = $pdf;
		return $this;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $options
	 * @return SirShurf_Pdf_TableSet
	 */
	private function _setOptions($options = null) {
		
		$this->setFont ();
		$this->setPaperSize ();
		$this->setMargin ();
		
		return $this;
	}
	
	public function render() {
		
		// Add the header image.
		if (! empty ( $this->_headerImage )) {
			$this->_drowHeaderImage ();
		} else {
			// If no header, set the first line below the margin.
			$this->_currentHeight = $this->_maxHeight - $this->_heightMargin;
		}
		
		// Layout all columns.
		foreach ( $this->_tables as $table ) {
			$table->render();
		}
		
		// Add the signature
		if (! empty ( $this->_signatureFile )) {
			$this->_drowFooterImage ( $this->_currentHeight, $this->_currentPage );
		}
		
		return $this->_pdf;
	}
	
	private function _drowHeaderImage() {
		$image = Zend_Pdf_Image::imageWithPath ( $this->_headerImage );
		
		// Convert from pixels to points.
		$height = $image->getPixelHeight () * 0.75;
		$width = $image->getPixelWidth () * 0.75;
		
		// If the image is bigger than our space.
		if ($width > $this->_maxWidth) {
			$proportion = $width / ($this->_maxWidth - ($this->_sideMargin * 2));
			$width /= $proportion;
			$height /= $proportion;
		}
		
		// Parameters go in: Left, Bottom, Right, Top : X1, Y2, X2, Y1
		// The offset is how far to shift the image right from 0 to achieve centering on the X axis.
		$offset = ($this->_maxWidth - $width) / 2;
		$x1 = $offset + 0;
		$y1 = $this->_maxHeight - ($this->_heightMargin / 2);
		$x2 = $offset + $width;
		$y2 = $y1 - $height;
		
		// Draw the header.
		$pdf->pages [$this->_currentPage]->drawImage ( $image, $x1, $y2, $x2, $y1 );
		
		$this->_currentHeight = $y2 - ($this->_fontSize * 2);
	
	}
	
	private function _drowFooterImage() {
		$image = Zend_Pdf_Image::imageWithPath ( $this->_footerImage );
		
		// Convert from pixels to points.
		$height = $image->getPixelHeight () * 0.75;
		$width = $image->getPixelWidth () * 0.75;
		
		$maxWidth = 150;
		
		// If the image is bigger than our space.
		if ($width > $maxWidth) {
			$proportion = $width / $maxWidth;
			$width /= $proportion;
			$height /= $proportion;
		}
		
		// Parameters go in: Left, Bottom, Right, Top : X1, Y2, X2, Y1
		// The offset is how far to shift the image right from 0 to achieve centering on the X axis.
		$offset = $this->_sideMargin;
		$x1 = $offset + 0;
		$y1 = $this->_currentHeight - 5;
		$x2 = $offset + $width;
		$y2 = $y1 - $height;
		
		// Draw the signature.
		$this->_pdf->pages [$this->_currentPage]->drawImage ( $image, $x1, $y2, $x2, $y1 );
		
		return $this;
	}
	
	/**
	 * Render and save object
	 *
	 */
	public function build($fileName) {
		// Save it.
		$this->render ()->save ( $fileName );
	}
	
	/**
	 * Sets the font and font size to use for the entire output process.
	 * Size units are in points.
	 *
	 * @param string $name The font type to use
	 * @param int $size The font size to use in units of points.
	 *
	 */
	public function setFont($type = 3, $size = 10) {
		$types = array (
			'1' => Zend_Pdf_Font::FONT_COURIER, '1b' => Zend_Pdf_Font::FONT_COURIER_BOLD, '2' => Zend_Pdf_Font::FONT_HELVETICA, '2b' => Zend_Pdf_Font::FONT_HELVETICA_BOLD, '3' => Zend_Pdf_Font::FONT_TIMES, '3b' => Zend_Pdf_Font::FONT_TIMES_BOLD 
		);
		
		$this->_font = Zend_Pdf_Font::fontWithName ( $types [$type] );
		$this->_fontBold = Zend_Pdf_Font::fontWithName ( $types [$type . 'b'] );
		$this->_fontType = $type;
		$this->_fontSize = $size;
	}
	
	/**
	 * Sets the font to use for the entire output process.
	 * Units are points.
	 *
	 * @param int $this->_sideMargin The margin size to use for the left/right sides in units of points.
	 * @param int $this->_heightMargin The margin size to use for the top/bottom in units of points.
	 */
	public function setMargin($sideMargin = 36, $heightMargin = 54) {
		$this->_sideMargin = $sideMargin;
		$this->_heightMargin = $heightMargin;
	}
	
	/**
	 * Sets the font to use for the entire output process.
	 *
	 *
	 */
	public function setPaperSize($size = 1) {
		
		$sizes = array (
			'1' => Zend_Pdf_Page::SIZE_A4, '2' => Zend_Pdf_Page::SIZE_A4_LANDSCAPE, '3' => Zend_Pdf_Page::SIZE_LETTER, '4' => Zend_Pdf_Page::SIZE_LETTER_LANDSCAPE 
		);
		
		$this->_paperSize = $sizes [$size];
		$hw = explode ( ":", $this->_paperSize );
		$this->_maxWidth = $hw [0];
		$this->_maxHeight = $hw [1];
	}
	
	/**
	 * Sets the header image to be used in the PDF.
	 *
	 * @return SirShurf_Pdf_TableSet
	 */
	public function setHeaderImage($filename) {
		$this->_headerImage = $filename;
		return $this;
	}
	
	/**
	 * Sets the footer image to be used in the PDF.
	 *
	 * @return SirShurf_Pdf_TableSet
	 */
	public function setFooterImage($filename) {
		$this->_footerImage = $filename;
		return $this;
	}
	
	/**
	 * Adds a new row to the model with no columns. Moves the row pointer to the new row.
	 *
	 * @param object $param - An object of type Zend_Pdf_Table
	 */
	public function addTable(array $options = array()) {
		$this->_tables [$this->_numTables] = new SirShurf_Pdf_TableSet_Table ( $options );
		$table = $this->_tables [$this->_numTables];
		$this->_numTables ++;
		return $table;
	}
	
	/**
	 * Wraps the given text to the colWidth provided.
	 *
	 * @param string text - The text to wrap
	 * @param int colWidth - The width of a column
	 * @param object font - The font to use.
	 * @param int fontSize - The font size in use.
	 *
	 * @return array - An array of wrapped text, one line per row.
	 */
	private function _wrapText($text, $colWidth, $font, $fontSize) {
		// Return if empty string.
		if (strlen ( $text ) == 0) {
			return array ();
		}
		
		// Find the length of the entire string in points.
		$length = $this->_getWidth ( $text, $font, $fontSize );
		$length10 = $this->_getWidth ( $text, $font, 10 );
		
		// Find out the average length of an individual character.
		$avg = intval ( ($length / strlen ( $text )) + 0.5 );
		
		// If something is horribly wrong
		if ($avg == 0) {
			return array ();
		}
		
		// How many characters to wrap at, given the size of the cell.
		$numToWrap = intval ( ($colWidth / $avg) + 0.5 );
		
		// Tolerance within 4 characters:
		if (strlen ( $text ) - $numToWrap <= 4) {
			$numToWrap = strlen ( $text );
		}
		
		$newText = explode ( '<br>', wordwrap ( $text, $numToWrap, '<br>' ) );
		
		return $newText;
	}
	
	/**
	 * Returns the width of the string, in points.
	 *
	 * @param string text - The text to wrap
	 * @param object font - The font to use.
	 * @param int fontSize - The font size in use.
	 *
	 */
	private function _getWidth($text, $font, $fontSize) {
		// Collect information on each character.
		$characters2 = str_split ( $text );
		$characters = array_map ( 'ord', str_split ( $text ) );
		
		// Find out the units being used for the current font.
		$glyphs = $font->glyphNumbersForCharacters ( $characters );
		$widths = $font->widthsForGlyphs ( $glyphs );
		//$units  = ($font->getUnitsPerEm() * $fontSize) / 10;
		$units = $font->getUnitsPerEm ();
		
		// Calculate the length of the string.
		$length = intval ( (array_sum ( $widths ) / $units) + 0.5 ) * $fontSize;
		
		foreach ( $characters as $num => $character ) {
			$ratio [$num] = $widths [$num] / $units;
		}
		
		return intval ( array_sum ( $ratio ) * $fontSize );
	
		//return $length;
	}
}