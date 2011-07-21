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
	private $_currentWidth;
	
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
			$this->_pdf->pages [$this->_currentPage] = $this->_pdf->newPage ( $this->_paperSize );
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
		
		$this->setPaperSize ();
		$this->setMargin ();
		
		return $this;
	}
	
	public function render() {
		
		// Add the header image.
		if (! empty ( $this->_headerImage )) {
			$this->_drawHeaderImage ();
		} else {
			// If no header, set the first line below the margin.
			$this->_currentHeight = $this->_maxHeight - $this->_heightMargin;
		}
		
		// Layout all columns.
		foreach ( $this->_tables as $table ) {
			$table->setPdfTableSet($this);
			$table->setMax($this->_maxHeight, $this->_maxWidth);
			$table->setCurrentHeight($this->_currentHeight);
			$table->setMargins($this->_sideMargin, $this->_heightMargin);
			$table->render();
//			$this->setHeight();
		}
		
		// Add the signature
		if (! empty ( $this->_signatureFile )) {
			$this->_drawFooterImage ( $this->_currentHeight, $this->_currentPage );
		}
		
		return $this->_pdf;
	}
	
	private function _drawHeaderImage() {
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
	
	private function _drawFooterImage() {
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
	 * @param array $options
	 * @return SirShurf_Pdf_TableSet_Table
	 */
	public function addTable(array $options = array()) {
		$table = new SirShurf_Pdf_TableSet_Table ( $options );
		$this->_tables [] = $table;		
		return $table;
	}
	
	/** 
	 * Get Object with Current Page
	 * 
	 * @return Zend_Pdf_Page
	 */
	public function getCurrentObject(){
		return $this->_pdf->pages [$this->_currentPage];
	}
	
	public function getCurrentRow(){
		return $this->_currentHeight;
	}

	public function getCurrentPosition(){
		return $this->_currentWidth;
	}
	public function getCurrentPage(){
		return $this->_currentPage;
	}
	
}