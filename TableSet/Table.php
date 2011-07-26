<?php

/**
 * Represents a Rowumn in a row in a table in a PDF file.
 */
class SirShurf_Pdf_TableSet_Table implements IteratorAggregate {
	/**
	 * Array containing all Rows in the row.
	 *
	 * @var array
	 */
	private $_rows;
	
	/**
	 * The number of Rows in the row.
	 *
	 * @var int
	 */
	private $_numRows;
	
	/**
	 * Table Options
	 *
	 * @var array
	 */
	private $_options;
	
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
		
	private $_heightMargin;
	private $_sideMargin;
	private $_currentHeight;
	private $_maxHeight;
	private $_maxWidth;
	
	
	/**
	 * 
	 * Pdf Table Set Object
	 * @var SirShurf_Pdf_TableSet
	 */
	private $_pdfTableSet;
	
	
	/**
	 * Instantiates the row class.
	 *
	 *
	 */
	public function __construct(array $options = array()) {
		$this->_rows = array ();
		$this->_numRows = 0;
		$this->_options = $options;
		
		$this->setFont ();
	}
	
	/**
	 * Adds a new Row to the model. Moves the Row pointer to the next Row.
	 *
	 *
	 */
	public function addRow() {
		$this->_rows [$this->_numRows] = new SirShurf_Pdf_TableSet_Row ();
		$row = $this->_rows [$this->_numRows];
		$this->_numRows ++;
		return $row;
	}
	
	/**
	 * Adds a spacer(empty) row.
	 *
	 * @param object $table - Object of type Zend_Pdf_Table to store the results in.
	 *
	 * @return void
	 */
	public function addSpacerRow() {
		$this->addRow ()->addCol ( '' );
	}
	
	/**
	 * Returns the number of Rows in the row.
	 */
	public function getNumRows() {
		return $this->_numRows;
	}
	
	/**
	 * Returns the highest column count for a single row
	 */
	public function getMaxCols() {
		$maxCols = 0;
		
		foreach ( $this->_rows as $row ) {
			$numCols = $row->getNumCols ();
			if ($maxCols < $numCols) {
				$maxCols = $numCols;
			}
		}
		return $maxCols;
	}
	
	/**
	 * Returns the columns widths for each column.
	 * You must pass in the Normal and Bold Zend_Pdf_Font objects for the current font
	 * in order to determine the width of characters.
	 *
	 * @var $font - Normal, non-bolded font.
	 * @var $fontBold - Bolded font.
	 * @var $maxWidth - The maximim drawing width of the page.
	 */
	public function getColWidths($font, $fontBold, $maxWidth) {
		$sizes = array ();
		$maxCols = $this->getMaxCols ();
		
		foreach ( $this as $row ) {
			$realKey = 0;
			foreach ( $row as $key => $col ) {
				$usedFont = $col->getFont();
				$options = $col->getOptions ();
				
				// If using a bold weight font, the widths change.
				if ($col->getOption ( 'bold' )) {
					$usedFont = $col->getFontBold();
				}
				
				$length = $col->getWidth ( $col->getText (), $usedFont, $col->getOption ( 'size' ) ) + $col->getOption ( 'indent-left' );
				
				// If we are doing a colspan for this column, move the column pointer forward the number spanned.
//				if ($col->getOption ( 'colspan' ) && $this->getNumRows () > 2) {
				if ($col->getOption ( 'colspan' ) && $this->getNumRows () > 1) {
					// Number of columns to be spanned.
					$numSpanned = $col->getOption ( 'colspan' );
					
					// For each spanned column, take the total length and divide by the number spanned. That becomes the width of
					// each spanned column.
					for($i = 0; $i < $numSpanned; $i ++) {
						$keySim = $realKey + $i;
						$partLength = intval ( $length * $numSpanned );
						
						if (isset ( $sizes [$keySim] )) {
							if ($sizes [$keySim] < $partLength) {
								$sizes [$keySim] = $partLength + 5;
							}
						} else {
							$sizes [$keySim] = $partLength + 5;
						}
					}
					// Advance the realkey pointer past the spanned columns.
					$realKey += $col->getOption ( 'colspan' );
				} else {
					// Otherwise, no spanning being done, so we can use straight length.
					if (isset ( $sizes [$realKey] )) {
						if ($sizes [$realKey] < $length) {
							$sizes [$realKey] = $length + 5;
						}
					} else {
						$sizes [$realKey] = $length + 5;
					}
					
					$realKey ++;
				}
			}
		}
		
		// We need to account for text wrapping. To do this, we need to make sure the total width
		// does not exceed the allowable space. We calculate how much the line is over, then divide that
		// by the number of columns in the row. This tells us how much space to remove from each column.
		

		$totalWidth = array_sum ( $sizes );
		$difference = 0;
		$i = 0;
		while ( $totalWidth > $maxWidth ) {
			$i ++;
			// The amount to remove from each column.
			$difference = intval ( ($totalWidth - $maxWidth) / $maxCols );
			$maxPerCell = intval ( $maxWidth / $maxCols );
			
			// We want to stop the situation where $difference is 0, but $totalWidth > $maxWidth
			// Example happens when $totalWidth = 525 and $maxWidth = 523 with 3 columns.
			// 525-523 = 2. 2/3 gives an intval of 0.
			if ($difference == 0 && $totalWidth > $maxWidth) {
				$difference = 1;
			}
			
			if ($difference > 0) {
				foreach ( $sizes as $key => $value ) {
					// Don't allow it to set a size <= 0
					if ($value > $maxPerCell) {
						$sizes [$key] = $value - $difference;
					}
				}
				$totalWidth = array_sum ( $sizes );
			}
			// Protection against an infinite loop.
			if ($i > 1000) {
				die ( "An error has occured. Please contact your administrator." );
			}
		}
		
		return $sizes;
	}
	
	/**
	 * Returns the options for the cell.
	 */
	public function getOptions() {
		return $this->_options;
	}
	
	/**
	 * Returns the specified option value.
	 */
	public function getOption($option) {
		if (isset ( $this->_options [$option] )) {
			return $this->_options [$option];
		} else {
			return null;
		}
	}
	
	/**
	 * Allows for iteration over individual rows
	 *
	 * @return SirShurf_Pdf_TableSet_Row
	 */
	public function getIterator() {
		return new ArrayIterator ( $this->_rows );
	}
	
	public function setMax($maxHeight, $maxWidth){
		$this->_maxHeight = $maxHeight;
		$this->_maxWidth = $maxWidth;
		return $this;
	}
	
	public function render() {
		// Maximum usable space for a row.
		$maxWidth = ($this->_maxWidth - ($this->_sideMargin * 2));
		
		// Gather some information about the table.
		/**
		 * @todo incorrect width calculation...
		 */
		$colWidths = $this->getColWidths ( $this->_font, $this->_fontBold, $maxWidth );
		
		// Highest number of columns in a single row.
		$numCols = count ( $colWidths );
		
		// Amount of horizontal space necessary to draw the table.
		$tableWidth = array_sum ( $colWidths );
		
		// Justify the table if the flag is set.
		if ($this->getOption ( 'align' ) == 'justify') {
			$difference = $maxWidth - $tableWidth;
			if ($difference > 0 && count ( $colWidths ) > 0) {
				$addToEach = intval ( ($difference / count ( $colWidths )) + 0.5 );
				foreach ( $colWidths as $num => $value ) {
					$colWidths [$num] = $value + $addToEach;
				}
			}
		}
			// Center the table if the flag is set.
		if ($this->getOption ( 'align' ) == 'center') {
			// Calculate the distance between the width of the table and the margins.
			$difference = ($maxWidth - $tableWidth) / 2;
			if ($difference < 0) {
				$difference = 0;
			}
			$x = $this->_sideMargin + $difference;
		} else {
			$x = $this->_sideMargin;
		}
		
		// Wrap the page if necessary.
		if ($this->_currentHeight <= ($this->_heightMargin / 2)) {
			$this->_currentPage ++;
			$pdf->pages [$this->_currentPage] = $this->_pdf->newPage ( $this->_paperSize );
			$this->setCurrentHeight($this->_maxHeight - ($this->_heightMargin));
		}
		
		foreach ( $this as $row ) {
			$row->setPdfTableSet($this->_pdfTableSet);	
			$row->setSideMargin($x);
			$row->setColWidths($colWidths);
			$row->render();
//			$this->setCurrentHeight();
		}
		
		return $this->_currentHeight;	
	}
	
	public function setCurrentHeight($currentHeight){
		$this->_currentHeight = $currentHeight;
		return $this;
	}
	
	public function setMargins($sideMargin, $heightMargin){
		$this->_sideMargin = $sideMargin;
		$this->_heightMargin = $heightMargin;
		return $this;
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

}