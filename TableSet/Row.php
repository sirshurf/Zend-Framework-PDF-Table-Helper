<?php

/**
 * Represents a column in a row in a table in a PDF file.
 */
class SirShurf_Pdf_TableSet_Row implements Iterator, Countable {
	/**
	 * Array containing all columns in the row.
	 *
	 * @var array
	 */
	private $_cols;
	
	/**
	 * The number of columns in the row.
	 *
	 * @var int
	 */
	private $_numCols;
	
	/**
	 * Array of Widths of columns
	 * 
	 * @var array
	 */
	private $_colWidths;
	
	/**
	 * 
	 * Pdf Table Set Object
	 * @var SirShurf_Pdf_TableSet
	 */
	private $_pdfTableSet;
	
	private $_sideMargin;
	
	private $_intLineHeight = 0;
	
	/**
	 * Instantiates the row class.
	 *
	 *
	 */
	public function __construct() {
		$this->_cols = array ();
		$this->_numCols = 0;
	}
	
	/**
	 * Adds a new col to the model. Moves the col pointer to the next col.
	 *
	 *
	 */
	public function addCol($text = "", $options = array()) {
		$this->_cols [$this->_numCols] = new SirShurf_Pdf_TableSet_Cell ( $text, $options );
		
		$this->_numCols ++;
		
		return $this;
	}
	
	/**
	 * Gets the number of columns.
	 *
	 * @return $_numCols
	 */
	public function getNumCols() {
		return $this->_numCols;
	}
	
	/**
	 * Returns the maximum height needed for the row.
	 */
	public function getHeight() {
		$maxHeight = 0;
		foreach ( $this->_cols as $col ) {
			if ($maxHeight <= $col->getOption ( 'size' )) {
				$maxHeight = $col->getOption ( 'size' );
			}
		}
		
		// We reduce the size calculated by 10% to save on vertical space.
		return $maxHeight * 0.9;
	}
	
	/** 
	 * Array of column widths (Calculated)
	 * 
	 * @param array $colWidths
	 * @return SirShurf_Pdf_TableSet_Row
	 */
	public function setColWidths(array $colWidths) {
		$this->_colWidths = $colWidths;
		return $this;
	}
	
	/** 
	 * Side Margin
	 * 
	 * @param array $colWidths
	 * @return SirShurf_Pdf_TableSet_Row
	 */
	public function setSideMargin($sideMargin) {
		$this->_sideMargin = $sideMargin;
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
	
	public function render() {
		// The real key tracks the column to use during colspanned rows.
		$realKey = 0;
		
		$currentRowPosition = $this->_sideMargin;
		
		foreach ( $this as $col ) {
			
			$col->setPdfTableSet ( $this->_pdfTableSet );
			
			$col->setWidth ( $this->_colWidths [$realKey] );
			
			$currentRowPosition = $col->render ( $currentRowPosition );
			
			// Column spanning.
			// Must calculate before wrapping text.
			// :TODO FIX HERE, COLSPANING!!!!
			if ($col->getOption ( 'colspan' )) {
				$colspan = $col->getOption ( 'colspan' );
				if ($colspan > $this->_numCols) {
					$colspan = $this->_numCols;
				}
				$size = 0;
				for($i = 0; $i < $colspan; $i ++) {
					$index = $realKey + $i;
					if (isset ( $this->_colWidths [$index] )) {
						$size += $this->_colWidths [$index];
					}
				}
				$offset = $size;
				$realKey += $colspan;
			} else {
				$realKey ++;
			}
			
			$this->findMaxHeight ( $col->getColumngHeight () );
		}
		$this->setCurrentHeight ();
//		return $this->_currentHeight;
	}
	
	public function findMaxHeight($intHeight) {
		if ($this->_intLineHeight < $intHeight) {
			$this->_intLineHeight = $intHeight;
		}
		return $this;
	}
	
	public function setCurrentHeight() {
		$this->_pdfTableSet->setHeight ( $this->_pdfTableSet->getCurrentRow () - $this->_intLineHeight );
		return $this;
	}
	
	// Interfaces: Iterator, Countable
	

	/**
	 * Current Table Columns
	 *
	 * @return SirShurf_Pdf_TableSet_Column
	 */
	public function current() {
		//        $this->_sort();
		current ( $this->_cols );
		$key = key ( $this->_cols );
		
		if (isset ( $this->_cols [$key] )) {
			return $this->_cols [$key];
		} else {
			// :TODO CORRECT EXCEPTION
			require_once 'Zend/Form/Exception.php';
			throw new Zend_Form_Exception ( sprintf ( 'Corruption detected in form; invalid key ("%s") found in internal iterator', ( string ) $key ) );
		}
	}
	
	/**
	 * Current column name
	 *
	 * @return string
	 */
	public function key() {
		//        $this->_sort();
		return key ( $this->_cols );
	}
	
	/**
	 * Move pointer to next element/subform/display group
	 *
	 * @return void
	 */
	public function next() {
		//        $this->_sort();
		next ( $this->_cols );
	}
	
	/**
	 * Move pointer to beginning of element/subform/display group loop
	 *
	 * @return void
	 */
	public function rewind() {
		//        $this->_sort();
		reset ( $this->_cols );
	}
	
	/**
	 * Determine if current element/subform/display group is valid
	 *
	 * @return bool
	 */
	public function valid() {
		//        $this->_sort();
		return (current ( $this->_cols ) !== false);
	}
	
	/**
	 * Count of elements/subforms that are iterable
	 *
	 * @return int
	 */
	public function count() {
		return count ( $this->_cols );
	}

}