<?php

/**
 * Represents a column in a row in a table in a PDF file.
 */
class SirShurf_Pdf_TableSet_Row implements ArrayAccess, IteratorAggregate {
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
		$this->_cols [$this->_numCols] = new SirShurf_Pdf_TableSet_Column( $text, $options );
		
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
	
    public function getIterator()
    {
        return new ArrayIterator((array) $this->_data);
    }
    
    /**
     * Proxy to __isset
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * Proxy to __get
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @return string
     */
     public function offsetGet($offset)
     {
         return $this->__get($offset);
     }

     /**
      * Proxy to __set
      * Required by the ArrayAccess implementation
      *
      * @param string $offset
      * @param mixed $value
      */
     public function offsetSet($offset, $value)
     {
         $this->__set($offset, $value);
     }

     /**
      * Proxy to __unset
      * Required by the ArrayAccess implementation
      *
      * @param string $offset
      */
     public function offsetUnset($offset)
     {
         return $this->__unset($offset);
     }
	
	public function render() {
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
			$this->_currentHeight = $this->_maxHeight - ($this->_heightMargin);
		}
		
		// The real key tracks the column to use during colspanned rows.
		$realKey = 0;
		
		foreach ( $this as $key => $col ) {
			$col->render();
		}
		
		// Move the line height pointer by the number of actual lines drawn (> 1 when line wrapping).
		if ($numLines > 0) {
			$this->_currentHeight -= $this->getHeight () * $numLines;
		} else {
			$this->_currentHeight -= $this->getHeight ();
		}
	}
}