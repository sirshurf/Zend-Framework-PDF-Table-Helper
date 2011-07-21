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
	 * Initialize the column
	 *
	 */
	public function __construct($text, array $options = array()) {
		if (! isset ( $options ['size'] )) {
			$options ['size'] = 10; // Default to 10
		}
		
		$this->_text = $text;
		$this->_options = $options;
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
	
	public function render() {
		// Font Size$pdf
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
		$offset = $colWidths [$realKey];
		
		// Column spanning.
		// Must calculate before wrapping text.
		if ($this->getOption ( 'colspan' )) {
			$colspan = $this->getOption ( 'colspan' );
			if ($colspan > $numCols) {
				$colspan = $numCols;
			}
			$size = 0;
			for($i = 0; $i < $colspan; $i ++) {
				$index = $realKey + $i;
				if (isset ( $colWidths [$index] )) {
					$size += $colWidths [$index];
				}
			}
			$offset = $size;
			$realKey += $colspan;
		} else {
			$realKey ++;
		}
		// Wrap the text if necessary
		$text = $this->_wrapText ( $this->getText (), $offset, $font, $this->_fontSize );
		$numLines = count ( $text );
		
		// Set Text Color
		if ($this->getOption ( 'color' )) {
			$colors = explode ( ',', $this->getOption ( 'color' ) );
			$pdf->pages [$this->_currentPage]->setFillColor ( new Zend_Pdf_Color_Rgb ( $colors [0], $colors [1], $colors [2] ) );
		} else {
			$pdf->pages [$this->_currentPage]->setFillColor ( new Zend_Pdf_Color_Rgb ( 0, 0, 0 ) );
		}
		
		// Set the font to be used.
		$pdf->pages [$this->_currentPage]->setFont ( $font, $this->_fontSize );
		
		// Safe to add any borders now.
		// Border-Right
		if ($this->getOption ( 'border-right' )) {
			$colors = explode ( ',', $this->getOption ( 'border-right' ) );
			$pdf->pages [$this->_currentPage]->setLineColor ( new Zend_Pdf_Color_Rgb ( $colors [0], $colors [1], $colors [2] ) );
			// Draw the right border.
			$top = $this->_currentHeight + $this->_fontSize;
			$pdf->pages [$this->_currentPage]->drawLine ( $x + $offset, $top, $x + $offset, $this->_currentHeight );
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
		$tempHeight = $this->_currentHeight;
		foreach ( $text as $key => $line ) {
			$this->_pdf->pages [$this->_currentPage]->drawText ( $line, $leftBound + $this->getOption ( 'indent-left' ), $tempHeight );
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
	}
	

}