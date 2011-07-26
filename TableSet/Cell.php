<?php

/**
 * Represents a column in a row in a table in a PDF file.
 */
class SirShurf_Pdf_TableSet_Cell {
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
	 * The Font to use for text output. 
	 *
	 * @var Zend_Pdf_Resource_Font
	 */
	private $_font;
	
	/**
	 * The Font to use for text output. 
	 *
	 * @var Zend_Pdf_Resource_Font
	 */
	private $_fontBold;
	
	/**
	 * The font size to use for text output. Units are Points.
	 * The type stores the currently used type.
	 *
	 * @var int
	 */
	private $_fontSize;
	
	/**
	 * Initialize the column
	 *
	 */
	public function __construct($text, $options = array()) {
		$this->_text = $text;
		
		if (is_array ( $options )) {
			$this->setOptions ( $options );
		} elseif ($options instanceof Zend_Config) {
			$this->setConfig ( $options );
		}
	}
	
	/**
	 * Set form state from config object
	 *
	 * @param  Zend_Config $config
	 * @return Zend_Form
	 */
	public function setConfig(Zend_Config $config) {
		return $this->setOptions ( $config->toArray () );
	}
	
	public function setOptions(array $options) {
		
		if (! isset ( $options ['fontSize'] )) {
			$options ['fontSize'] = 10; // Default to 10
		}
		if (! isset ( $options ['font'] )) {
			$options ['font'] = Zend_Pdf_Font::FONT_TIMES;
		}
		if (! isset ( $options ['fontBold'] )) {
			$options ['fontBold'] = Zend_Pdf_Font::FONT_TIMES_BOLD;
		}
		
		foreach ( $options as $key => $value ) {
			$normalized = ucfirst ( $key );
			$method = 'set' . $normalized;
			if (method_exists ( $this, $method )) {
				$this->$method ( $value );
			} else {
				$this->setOption ( $key, $value );
			}
		}
		return $this;
	}
	
	public function setOption($key, $value) {
		$this->_options [$key] = $value;
		return $this;
	}
	
	/**
	 * Sets the font and font size to use for the entire output process.
	 * Size units are in points.
	 *
	 * @param string $name The font type to use
	 * @param int $size The font size to use in units of points.
	 *
	 */
	public function setFont($type = Zend_Pdf_Font::FONT_TIMES) {
		if ($this->isZendPdfFont ( $type )) {
			$this->_font = Zend_Pdf_Font::fontWithName ( $type );
		} else {
			$this->_font = Zend_Pdf_Font::fontWithPath ( realpath ( dirname ( dirname ( __FILE__ ) ) ) . "/Fonts/" . $type );
		}
		return $this;
	}
	
	public function setFontBold($type = Zend_Pdf_Font::FONT_TIMES_BOLD) {
		if ($this->isZendPdfFont ( $type )) {
			$this->_fontBold = Zend_Pdf_Font::fontWithName ( $type );
		} else {
			$this->_fontBold = Zend_Pdf_Font::fontWithPath ( realpath ( dirname ( dirname ( __FILE__ ) ) ) . "/Fonts/" . $type );
		}
		return $this;
	}
	
	public function isZendPdfFont($type) {
		
		if (! Zend_Registry::isRegistered ( "Zend_Pdf_Font_Constants" )) {
			$objReflection = new ReflectionClass ( "Zend_Pdf_Font" );
			$arrConstants = $objReflection->getConstants ();
			Zend_Registry::set ( "Zend_Pdf_Font_Constants", $arrConstants );
		}
		
		$arrZendPdfFontsConstans = Zend_Registry::get ( "Zend_Pdf_Font_Constants" );
		
		if (in_array ( $type, $arrZendPdfFontsConstans, TRUE )) {
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function setFontSize($size = 10) {
		$this->_fontSize = $size;
		return $this;
	}

	public function getFontSize() {
		return $this->_fontSize;
	}

	public function getFont() {
		return $this->_font;
	}
	
	public function getFontBold() {
		return $this->_fontBold;
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
		$this->_drowCellBorder ( $x, $offset );
		
		// Draw the text.
		// Perform the alignment calculations. Has to be done after text-wrapping.
		$align = $this->getOption ( 'align' );
		
		$length = $this->getWidth ( $this->_getLongestString ( $text ), $font, $this->_fontSize );
		//		$length10 = $this->getWidth ( $this->getText (), $font, 10 );
		

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
		$tempHeight = $this->_pdfTableSet->getCurrentRow () + intval ( ($this->_font->getLineHeight () / $this->_font->getUnitsPerEm ()) );
		foreach ( $text as $key => $line ) {
			$this->_pdfTableSet->getCurrentObject ()->drawText ( $line, $leftBound + $this->getOption ( 'indent-left' ), $tempHeight, 'UTF-8' );
			if ($key < ($numLines - 1)) {
				// Move the line pointer down the page.
				$tempHeight -= $this->getHeight ();
			}
		}
		// Move the x-axis cursor, plus any padding.
		$x += $offset;
		
		// Move the line height pointer by the number of actual lines drawn (> 1 when line wrapping).
		$this->setLineHeight ( $numLines );
		
		return $x;
	}
	
	/**
	 * 
	 * Drow Cell Border 
	 * 
	 * @param int $startLine
	 * @param int $width
	 * 
	 * @return SirShurf_Pdf_TableSet_Cell
	 */
	private function _drowCellBorder($startLine, $width) {
		
		$lineLeading = intval ( ($this->_font->getLineHeight () / $this->_font->getUnitsPerEm ()) );
		
		if ($this->getOption ( 'border-left' )) {
			$colors = explode ( ',', $this->getOption ( 'border-left' ) );
			$this->_pdfTableSet->getCurrentObject ()->setLineColor ( new Zend_Pdf_Color_Rgb ( $colors [0], $colors [1], $colors [2] ) );
			// Draw the right border.
			$top = $this->_pdfTableSet->getCurrentRow () + $this->_fontSize;
			$this->_pdfTableSet->getCurrentObject ()->drawLine ( $startLine, $top, $startLine, $this->_pdfTableSet->getCurrentRow () - $lineLeading );
		}
		
		if ($this->getOption ( 'border-right' )) {
			$colors = explode ( ',', $this->getOption ( 'border-right' ) );
			$this->_pdfTableSet->getCurrentObject ()->setLineColor ( new Zend_Pdf_Color_Rgb ( $colors [0], $colors [1], $colors [2] ) );
			// Draw the right border.
			$top = $this->_pdfTableSet->getCurrentRow () + $this->_fontSize;
			$this->_pdfTableSet->getCurrentObject ()->drawLine ( $startLine + $width, $top, $startLine + $width, $this->_pdfTableSet->getCurrentRow () - $lineLeading );
		}
		
		if ($this->getOption ( 'border-top' )) {
			$colors = explode ( ',', $this->getOption ( 'border-top' ) );
			$this->_pdfTableSet->getCurrentObject ()->setLineColor ( new Zend_Pdf_Color_Rgb ( $colors [0], $colors [1], $colors [2] ) );
			// Draw the top border.
			$top = $this->_pdfTableSet->getCurrentRow () + $this->_fontSize;
			$this->_pdfTableSet->getCurrentObject ()->drawLine ( $startLine, $top, $startLine + $width, $top );
		}
		
		if ($this->getOption ( 'border-buttom' )) {
			$colors = explode ( ',', $this->getOption ( 'border-buttom' ) );
			$this->_pdfTableSet->getCurrentObject ()->setLineColor ( new Zend_Pdf_Color_Rgb ( $colors [0], $colors [1], $colors [2] ) );
			// Draw the buttom border.
			

			$this->_pdfTableSet->getCurrentObject ()->drawLine ( $startLine, $this->_pdfTableSet->getCurrentRow () - $lineLeading, $startLine + $width, $this->_pdfTableSet->getCurrentRow () - $lineLeading );
		}
		
		return $this;
	}
	
	/**
	 * Returns the maximum height needed for the row.
	 */
	public function getHeight() {
		
		$maxHeight = $this->getFontSize ();
		
		// We reduce the size calculated by 10% to save on vertical space.
		return $maxHeight;
		return $maxHeight * 0.9;
	}
	
	private function setLineHeight($numLines = 1) {
		if (empty ( $numLines )) {
			$numLines = 1;
		}
		
		$lineLeading = intval ( ($this->_font->getLineHeight () / $this->_font->getUnitsPerEm ()) );
		
		$this->_currentHeight = $this->getHeight () * $numLines + $lineLeading;
		return $this;
	}
	
	public function getColumngHeight() {
		return $this->_currentHeight;
	}
	
	private function _getLongestString($array) {
		if (count ( $array ) > 0) {
			$mapping = array_combine ( $array, array_map ( 'mb_strlen', $array ) );
			return current( array_keys ( $mapping, max ( $mapping ) ));
		}
		return "";
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
		if (mb_strlen ( $text ) == 0) {
			return array ();
		}
		
		// Find the length of the entire string in points.
		$length = $this->getWidth ( $text, $font, $fontSize );
		$length10 = $this->getWidth ( $text, $font, 10 );
		
		// Find out the average length of an individual character.
		$avg = intval ( ($length / mb_strlen ( $text )) + 0.5 );
		
		// If something is horribly wrong
		if ($avg == 0) {
			return array ();
		}
		
		// How many characters to wrap at, given the size of the cell.
		$numToWrap = intval ( ($colWidth / $avg) + 0.5 );
		
		// Tolerance within 4 characters:
		if (mb_strlen ( $text ) - $numToWrap <= 4) {
			$numToWrap = mb_strlen ( $text );
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
	public function getWidth($text, $font, $fontSize) {
		
		// Collect information on each character.
		$characters2 = self::mb_str_split ( $text );
		$characters = array_map ( 'ord', self::mb_str_split ( $text ) );
		
		// Find out the units being used for the current font.
		$glyphs = $font->glyphNumbersForCharacters ( $characters );
		$widths = $font->widthsForGlyphs ( $glyphs );
		//$units  = ($font->getUnitsPerEm() * $fontSize) / 10;
		$units = $font->getUnitsPerEm ();
		
		// Calculate the length of the string.
		$length = intval ( (array_sum ( $widths ) / $units) + 0.5 ) * $fontSize;
		
		$ratio = array ();
		foreach ( $characters as $num => $character ) {
			$ratio [$num] = $widths [$num] / $units;
		}
		
		return intval ( array_sum ( $ratio ) * $fontSize );
	
		//return $length;
	}
	
	public static function mb_str_split($string) {
		# Split at all position not after the start: ^
		# and not before the end: $
		return preg_split ( '/(?<!^)(?!$)/u', $string );
	}

}