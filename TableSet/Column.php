<?php

/**
 * Represents a column in a row in a table in a PDF file.
 */
class SirShurf_Pdf_TableSet_Column {
	/**
	 * The text value of the column
	 *
	 * @var string
	 */
	private $_text;
	
	/**
	 * Column Options
	 *
	 * @var array
	 */
	private $_options;
	
	/**
	 * 
	 * Pdf Table Set Object
	 * @var SirShurf_Pdf_TableSet
	 */
	private $_pdfTableSet;
	
	/**
	 * 
	 * Column Width
	 * @var int
	 */
	private $_width;
	
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
	 * Initialize the column
	 *
	 */
	public function __construct($text, array $options = array()) {
		if (! isset ( $options ['size'] )) {
			$options ['size'] = 10; // Default to 10
		}
		
		$this->_text = $text;
		$this->_options = $options;
		
		$this->setFont();
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
	 * 
	 * Set Zend Pdf Object
	 * @param SirShurf_Pdf_TableSet $pdf
	 * @return SirShurf_Pdf_TableSet
	 */
	public function setPdfTableSet(SirShurf_Pdf_TableSet $pdfTableSet) {
		$this->_pdfTableSet = $pdfTableSet;
		return $this;
	}
	
	public function setWidth($width) {
		$this->_width = $width;
		return $this;
	}
	
	/**
	 * Sets the text for the column
	 *
	 * @param string $text - The text to display in the column.
	 */
	public function getText() {
		return $this->_text;
	}
	
	/**
	 * Returns the options for the cell.
	 */
	public function getOptions() {
		return $this->_options;
	}
	
	/**
	 * Returns the options for the cell.
	 */
	public function getOption($option) {
		if (isset ( $this->_options [$option] )) {
			return $this->_options [$option];
		} else {
			return null;
		}
	}
	
	public function render($x) {
		
		// Font Size
		if ($this->getOption ( 'size' )) {
			$this->setFont ( $this->_fontType, $this->getOption ( 'size' ) );
		}
		
		// Set the font.
		if ($this->getOption ( 'bold' )) {
			$font = $this->_fontBold;
		} else {
			$font = $this->_font;
		}
		
		// How far to move it on the X axis for the next column.
		$offset = $this->_width;
		
		// Wrap the text if necessary
		$text = $this->_wrapText ( $this->getText (), $offset, $font, $this->_fontSize );
		$numLines = count ( $text );
		
		// Set Text Color
		if ($this->getOption ( 'color' )) {
			$colors = explode ( ',', $this->getOption ( 'color' ) );
			$this->_pdfTableSet->getCurrentObject ()->setFillColor ( new Zend_Pdf_Color_Rgb ( $colors [0], $colors [1], $colors [2] ) );
		} else {
			$this->_pdfTableSet->getCurrentObject ()->setFillColor ( new Zend_Pdf_Color_Rgb ( 0, 0, 0 ) );
		}
		
		// Set the font to be used.
		$this->_pdfTableSet->getCurrentObject ()->setFont ( $font, $this->_fontSize );
		
		// Safe to add any borders now.
		// Border-Right
		if ($this->getOption ( 'border-right' )) {
			$colors = explode ( ',', $this->getOption ( 'border-right' ) );
			$this->_pdfTableSet->getCurrentObject ()->setLineColor ( new Zend_Pdf_Color_Rgb ( $colors [0], $colors [1], $colors [2] ) );
			// Draw the right border.
			$top = $this->_pdfTableSet->getCurrentRow() + $this->_fontSize;
			$this->_pdfTableSet->getCurrentObject ()->drawLine ( $x + $offset, $top, $x + $offset, $this->_pdfTableSet->getCurrentRow() );
		}
		
		// Draw the text.
		// Perform the alignment calculations. Has to be done after text-wrapping.
		$align = $this->getOption ( 'align' );
		$length = $this->_getWidth ( $this->getText (), $font, $this->_fontSize );
		$length10 = $this->_getWidth ( $this->getText (), $font, 10 );
		
		switch ($align) {
			case 'center' :
				// Center Align
				$leftBound = $x + (($offset - $length) / 2);
				break;
			case 'right' :
				// Right Align
				$leftBound = $x + (($offset - $length));
				break;
			default :
				// Left Align
				$leftBound = $x;
				break;
		}
		
		// Border @todo: make this an option later. Mostly for debuging position.
		/*$borderHeight = $this->_currentHeight;
                    foreach($text as $key => $line) {
                        $top = $borderHeight + $row->getHeight();
                        $pdf->pages[$this->_currentPage]->drawRectangle($x, $top, $x + $offset, $borderHeight, $fillType = Zend_Pdf_Page::SHAPE_DRAW_STROKE);
                        if($key < ($numLines-1)) {
                            // Move the line pointer down the page.
                            $borderHeight -= $row->getHeight();
                        }
                    }*/
		
		// Underline: @todo: make this an option later.
		//$pdf->pages[$this->_currentPage]->drawLine($x, $this->_currentHeight-1, $x + $offset, $this->_currentHeight-1);
		

		// Finally, draw the text in question.
		$tempHeight = $this->_pdfTableSet->getCurrentRow();
		foreach ( $text as $key => $line ) {
			$this->_pdfTableSet->getCurrentObject ()->drawText ( $line, $leftBound + $this->getOption ( 'indent-left' ), $tempHeight, 'UTF-8' );
			if ($key < ($numLines - 1)) {
				// Move the line pointer down the page.
				$tempHeight -= $this->getHeight ();
			}
		}
		// Move the x-axis cursor, plus any padding.
		$x += $offset;
		
		// Restore Font Size to default.
		if ($this->getOption ( 'size' )) {
			$this->setFont ();
		}
		
		// Move the line height pointer by the number of actual lines drawn (> 1 when line wrapping).
		$this->setLineHeight($numLines);
		
		return $x;
	}
	
	
	/**
	 * Returns the maximum height needed for the row.
	 */
	public function getHeight() {

		$maxHeight = $this->getOption ( 'size' );
		
		// We reduce the size calculated by 10% to save on vertical space.
		return $maxHeight * 0.9;
	}
	
	private function setLineHeight($numLines = 1){
		if (empty($numLines)){
			$numLines = 1;
		}
		$this->_currentHeight = $this->getHeight () * $numLines;
		return $this;
	}
	
	public function getColumngHeight(){
		return $this->_currentHeight;
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