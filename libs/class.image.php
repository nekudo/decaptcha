<?php
class Image
{	
	protected $Imgick = null;
	private $_blackWhiteMatrix = false;


	public function  __construct($imagefile)
	{
		/**
		 * ATTENTION: There is a php/imagick bug which prevents Imagick from beeing extented!
		 * @see http://pecl.php.net/bugs/bug.php?id=21229
		 */
		$this->Imgick = new Imagick($imagefile);
	}

	/**
	 * Converts an image to pure black/white.
	 */
	public function toBlackWhiteGif()
	{		
		$this->Imgick->setImageFormat('gif');
		$this->Imgick->blackThresholdImage('#808080');
		$this->Imgick->whiteThresholdImage('#808080');
	}	

	/**
	 * Calculates a horizontal or vertical projection of a given area in an image.
	 * This is done by simply counting all black pixels in one "row" or "column".
	 *
	 * @param string $type Type of projection (can be horozontal or vertical)
	 * @param int $xStart horizontal start pixel of area.
	 * @param int $xEnd horizontal end pixel of area.
	 * @param int $yStart vertial start pixel of area.
	 * @param int $yEnd vertical end pixel of area.
	 * @return array The horizontal or vertical projection.
	 */
	public function getProjection($type, $xStart = false, $xEnd = false, $yStart = false, $yEnd = false)
	{
		if(empty($type))
		{
			return false;
		}

		$xStart = ($xStart === false) ? 1 : $xStart;
		$xEnd = ($xEnd === false) ? $this->Imgick->getImageWidth() : $xEnd;
		$yStart = ($yStart === false) ? 1 : $yStart;
		$yEnd = ($yEnd === false) ? $this->Imgick->getImageHeight() : $yEnd;

		// generate the b/w matrix if not already done:
		if($this->_blackWhiteMatrix === false)
		{
			$this->_generateBlackWhiteMatrix();
		}

		$projection = array();
		$key = 1;
		if($type == 'vertical')
		{			
			for($y = $yStart; $y <= $yEnd; $y++)
			{
				$projection[$key] = 0;
				for($x = $xStart; $x <= $xEnd; $x++)
				{
					$projection[$key] = ($this->_blackWhiteMatrix[$x][$y] === true) ? $projection[$key] + 1 : $projection[$key];
				}
				$key++;
			}			
		}
		elseif($type == 'horizontal')
		{			
			for($x = $xStart; $x <= $xEnd; $x++)
			{
				$projection[$key] = 0;
				for($y = $yStart; $y <= $yEnd; $y++)
				{
					$projection[$key] = ($this->_blackWhiteMatrix[$x][$y] === true) ? $projection[$key] + 1 : $projection[$key];
				}
				$key++;
			}			
		}
		return $projection;
	}

	/**
	 * Saves an image to file.
	 * @param string $filename Filename (with path) to save image to.
	 */
	public function save($filename)
	{
		$this->Imgick->writeImage($filename);
	}

	/**
	 * Draws a reactangle into the image.
	 *
	 * @param int $x1 Horizontal start.
	 * @param int $y1 Vertical start.
	 * @param int $x2 Horizontal end.
	 * @param int $y2 Vertical end.
	 */
	public function drawRectangle($x1,$y1,$x2,$y2)
	{
		$draw = new ImagickDraw();
		$draw->setFillColor('wheat');    // Set up some colors to use for fill and outline
		$draw->setFillOpacity(0);
		$draw->setStrokeColor( new ImagickPixel( 'green' ) );
		$draw->rectangle( $x1,$y1,$x2,$y2 );    // Draw the rectangle
		$this->Imgick->drawImage($draw);
	}
	/**
	 * Runs trough every pixel of an b/w image and checks if pixel is black or
	 * white. Black ones are represented by boolean true. White ones are false.
	 */
	private function _generateBlackWhiteMatrix()
	{		
		$this->_blackWhiteMatrix = array();
		$imageWidth = $this->Imgick->getImageWidth();
		$imageHeight = $this->Imgick->getImageHeight();
		for($x = 1; $x <= $imageWidth; $x++)
		{
			for($y = 1; $y <= $imageHeight; $y++)
			{
				$pixel = $this->Imgick->getImagePixelColor($x, $y);
				$pixelColor = $pixel->getColorAsString();
				$this->_blackWhiteMatrix[$x][$y] = ($pixelColor === 'rgb(0,0,0)') ? true : false;
			}
		}
	}
}