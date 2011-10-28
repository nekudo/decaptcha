<?php
/**
 * Let's play with some captchas. This is the main class containing learning
 * and solving logic to (hopefully) break a captcha.
 *
 * @author Simon Samtleben <web@lemmingzshadow.net>
 * @version 2010-11-27
 */
include LIBS . 'class.image.php';
include LIBS . 'class.area.php';

class Decaptcha
{
	private $_Image = null;
	private $_areas = array();
	private $_areasCount = 0;

	public function  __construct($imagefile)
	{
		$this->_Image = new Image($imagefile);
	}

	/**
	 * The main method for captcha training. It analyses a captcha image and
	 * saves vertial/horizontal projections of character in this captcha to
	 * database.
	 *
	 * @param sting $captchaSolution The solution of the captcha.
	 */
	public function training($captchaSolution)
	{
		$this->_prepareImage();
		$this->_determineCharAreas();
		$this->_optimizeCharAreas();
		$this->_calculateAreaProjections();

		$captchaSolutionChars = str_split($captchaSolution);
		include MODELS . 'model.decaptcha_template.php';
		for($i = 0; $i < $this->_areasCount; $i++)
		{
			$DecaptchaTemplate = new DecaptchaTemplate();
			$DecaptchaTemplate->setSolution($captchaSolutionChars[$i]);
			$DecaptchaTemplate->setHorizontalProjection($this->_areas[$i]->horizontalProjection);
			$DecaptchaTemplate->setVerticalProjection($this->_areas[$i]->verticalProjection);
			$DecaptchaTemplate->save();
			unset($DecaptchaTemplate);
		}
	}

	/**
	 * The main method to solve a cpatcha. It compares horizontal/vertical
	 * projections of chars in given image to values in database.
	 */
	public function solve()
	{
		$this->_prepareImage();
		$this->_determineCharAreas();
		$this->_optimizeCharAreas();
		$this->_calculateAreaProjections();
		$this->_doCharRecognition();
	}

	/**
	 * Put everything in this merhod to prepare an image for char-regognition.
	 * The image should become pure black chars on white ground.
	 */
	private function _prepareImage()
	{
		$this->_Image->toBlackWhiteGif();
	}


	/**
	 * Try to determine the single characters in the captcha.
	 */
	private function _determineCharAreas()
	{
		// walk trough the image horizotally and split at any point with no black pixels:
		$horizontalProjection = $this->_Image->getProjection('horizontal');
		$horizontalProjectionCount = count($horizontalProjection);
	
		$inChar = false;
		for($i = 1; $i <= $horizontalProjectionCount; $i++)
		{
			if($horizontalProjection[$i] > 0 && $inChar === false)
			{
				$inChar = true;				
				$Area = new Area();
				$Area->xStart = $i;
			}
			if($horizontalProjection[$i] === 0 && $inChar === true)
			{
				$Area->xEnd = $i - 1;
				$this->_areas[] = $Area;
				$this->_areasCount++;				
				$inChar = false;				
			}
			if($i === $horizontalProjectionCount && $inChar === true)
			{
				$Area->xEnd = $i;
				$this->_areas[] = $Area;
				$this->_areasCount++;				
			}
		}

		// adjust the hight of the single characters:
		for($i = 0; $i < $this->_areasCount; $i++)
		{
			$this->_areas[$i]->yStart = false;
			$this->_areas[$i]->yEnd = false;
			$verticalProjection = $this->_Image->getProjection('vertical', $this->_areas[$i]->xStart, $this->_areas[$i]->xEnd);
			$verticalProjectionCount = count($verticalProjection);
			for($j = 1; $j <= $verticalProjectionCount; $j++)
			{
				if($verticalProjection[$j] > 0 && $this->_areas[$i]->yStart === false)
				{
					$this->_areas[$i]->yStart = $j;
				}
				if($verticalProjection[$j] > 0 && $this->_areas[$i]->yStart !== false)
				{
					$this->_areas[$i]->yEnd = $j;
				}
			}
		}
	}

	/**
	 * Sometimes one charactes is splitted into two parts. This method tries to
	 * fix this.
	 */
	private function _optimizeCharAreas()
	{
		for($i = 0; $i < $this->_areasCount; $i++)
		{
			$areaSize = ($this->_areas[$i]->xEnd - $this->_areas[$i]->xStart) * ($this->_areas[$i]->yEnd - $this->_areas[$i]->yStart);			
			if($areaSize > MIN_AREA_LIMIT)
			{
				continue;
			}

			if($i == 0)
			{
				$spaceRight = $this->_areas[$i+1]->xStart - $this->_areas[$i]->xEnd;
				if($spaceRight > MAX_CHAR_SPACE)
				{
					continue;
				}
				$Area = $this->_combineAreas($this->_areas[0], $this->_areas[1]);
				unset($this->_areas[0]);
				$this->_areas[1] = $Area;
				unset($Area);
			}
			elseif($i == $this->_areasCount - 1)
			{
				$spaceLeft = $this->_areas[$i]->xStart - $this->_areas[$i-1]->xEnd;
				if($spaceLeft > MAX_CHAR_SPACE)
				{
					continue;
				}
				$Area = $this->_combineAreas($this->_areas[$i-1], $this->_areas[$i]);
				unset($this->_areas[$i]);
				$this->_areas[$i-1] = $Area;
				unset($Area);
			}
			else
			{
				$spaceLeft = $this->_areas[$i]->xStart - $this->_areas[$i-1]->xEnd;
				$spaceRight = $this->_areas[$i+1]->xStart - $this->_areas[$i]->xEnd;

				if($spaceLeft < $spaceRight)
				{
					if($spaceLeft > MAX_CHAR_SPACE)
					{
						continue;
					}
					$Area = $this->_combineAreas($this->_areas[$i-1], $this->_areas[$i]);
					unset($this->_areas[$i]);
					$this->_areas[$i-1] = $Area;
					unset($Area);
				}
				else
				{
					if($spaceRight > MAX_CHAR_SPACE)
					{
						continue;
					}
					$Area = $this->_combineAreas($this->_areas[$i], $this->_areas[$i+1]);
					unset($this->_areas[$i+1]);
					$this->_areas[$i] = $Area;
					unset($Area);
					$i++;
				}
			}
		}

		$this->_areas = array_merge($this->_areas, array());
		$this->_areasCount = count($this->_areas);

		for($i = 0; $i < $this->_areasCount; $i++)
		{
			$this->_Image->drawRectangle($this->_areas[$i]->xStart, $this->_areas[$i]->yStart, $this->_areas[$i]->xEnd, $this->_areas[$i]->yEnd);
		}
		$this->_Image->save(PROJECT_ROOT . 'gfx/last_analyzed.gif');
	}

	/**
	 * Calculate the horizontal and vertical projection for every character.
	 */
	private function _calculateAreaProjections()
	{
		for($i = 0; $i < $this->_areasCount; $i++)
		{
			$this->_areas[$i]->horizontalProjection = $this->_Image->getProjection('horizontal', $this->_areas[$i]->xStart, $this->_areas[$i]->xEnd, $this->_areas[$i]->yStart, $this->_areas[$i]->yEnd);
			$this->_areas[$i]->verticalProjection = $this->_Image->getProjection('vertical', $this->_areas[$i]->xStart, $this->_areas[$i]->xEnd, $this->_areas[$i]->yStart, $this->_areas[$i]->yEnd);
			$this->_areas[$i]->pixelCount = array_sum($this->_areas[$i]->horizontalProjection);
		}	
	}

	/**
	 * This method tries to recognize a character when solving a captcha. Therefore
	 * it compares the horizontal and vertical projecitions of the caracter with
	 * templates in database by calculation the correlation coefficient.
	 */
	public function _doCharRecognition()
	{
		include MODELS . 'model.decaptcha_template.php';
		$DecaptchaTemplate = new DecaptchaTemplate();
		$templates = $DecaptchaTemplate->getAll();		
		$templatesCount = count($templates);

		foreach($this->_areas as $Area)
		{
			$ccH = 0;
			$ccV = 0;
			$ccMax = 0;
			$char = null;
			for($i = 0; $i < $templatesCount; $i++)
			{
				$ccH =  $this->_getCorrelationCoefficient($Area->horizontalProjection, unserialize($templates[$i]['horizontalProjection']));
				$ccV =  $this->_getCorrelationCoefficient($Area->verticalProjection, unserialize($templates[$i]['verticalProjection']));				
				$ccH = ($ccH < 0) ? $ccH * -1 : $ccH;
				$ccV = ($ccV < 0) ? $ccV * -1 : $ccV;
				$cc = ($ccH > $ccV) ? $ccH : $ccV;
				if($cc > $ccMax)
				{
					$ccMax = $cc;
					$char = $templates[$i]['solution'];
				}
			}
			echo $char . ' ' . $ccMax."<br />";
		}
	}

	/**
	 * Merges two areas into one.
	 *
	 * @param object $Area1
	 * @param object $Area2
	 * @return Area The resulting area.
	 */
	private function _combineAreas($Area1, $Area2)
	{
		$Area = new Area();
		$Area->xStart = ($Area1->xStart < $Area2->xStart) ? $Area1->xStart : $Area2->xStart;
		$Area->xEnd = ($Area1->xEnd > $Area2->xEnd) ? $Area1->xEnd : $Area2->xEnd;
		$Area->yStart = ($Area1->yStart < $Area2->yStart) ? $Area1->yStart : $Area2->yStart;
		$Area->yEnd = ($Area1->yEnd > $Area2->yEnd) ? $Area1->yEnd : $Area2->yEnd;

		return $Area;
	}

	/**
	 * Calculates the correlation coefficient for two given matrixes. (2 dimensions)
	 *
	 * @param array $matrix1
	 * @param array $matrix2
	 * @return float correlation coefficient of the two matrixes.
	 */
	private function _getCorrelationCoefficient($matrix1, $matrix2)
	{
		$matrix1 = array_merge($matrix1, array());
		$matrix2 = array_merge($matrix2, array());
		$matrix1Count = count($matrix1);
		$matrix2Count = count($matrix2);

		if($matrix1Count > $matrix2Count)
		{
			$matrixDiff = $matrix1Count - $matrix2Count;
			$pre = ceil($matrixDiff / 2);
			$post = $matrixDiff - $pre;
			$matrix2 = array_pad($matrix2, $matrix2Count + $post, 0);
			$matrix2 = array_pad($matrix2, $matrix1Count * -1, 0);
		}
		if($matrix2Count > $matrix1Count)
		{
			$matrixDiff = $matrix2Count - $matrix1Count;
			$pre = ceil($matrixDiff / 2);
			$post = $matrixDiff - $pre;
			$matrix1 = array_pad($matrix1, $matrix1Count + $post, 0);
			$matrix1 = array_pad($matrix1, $matrix2Count * -1, 0);
		}		

		$matrixCount = count($matrix1);
		$matrix1Mean = $this->getMean($matrix1);
		$matrix2Mean = $this->getMean($matrix2);		

		$numerator = 0;
		$denumerator_exp1 = 0;
		$denumerator_exp2 = 0;
		for($i = 0; $i < $matrixCount; $i++)
		{
			$numerator = $numerator + (($matrix1[$i] - $matrix1Mean) * ($matrix2[$i] - $matrix2Mean));
			$denumerator_exp1 = $denumerator_exp1 + pow(($matrix1[$i] - $matrix1Mean),2);
			$denumerator_exp2 = $denumerator_exp2 + pow(($matrix2[$i] - $matrix2Mean),2);
		}
		$denumerator = sqrt($denumerator_exp1 * $denumerator_exp2);
		$correlationCoefficient = $numerator / $denumerator;

		return $correlationCoefficient;
	}

	/**
	 * Calculates the mean of given values.
	 *
	 * @param array $data Values to calculate mean of.
	 * @return decimal The mean of the given values
	 */
	private function getMean($data)
	{
		if(empty($data) || !is_array($data))
		{
			return false;
		}
		return array_sum($data) / count($data);
	}
}