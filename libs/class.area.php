<?php
/**
 * An area object.
 * Defines an area in an image and contains vertical and horizontal
 * projection of this area.
 */
class Area
{
	public $xStart = 0;
	public $xEnd = 0;
	public $yStart = 0;
	public $yEnd = 0;
	public $horizontalProjection = array();
	public $verticalProjection = array();
	public $pixelCount = 0;
}