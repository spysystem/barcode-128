<?php
namespace Barcode128;
/**
 * Class Image
 */
class Image
{
	/** @var  resource */
	protected $rsImage;
	/** @var bool  */
	protected $bStatus		= true;
	/** @var  int */
	protected $iWidth;
	/** @var  int */
	protected $iHeight;
	/** @var int[] */
	protected $arrColors = [];
	/** @var int[] */
	protected	$arrFonts = [];
	/** @var string[] */
	protected $arrErrors = [];

	const /** @noinspection SpellCheckingInspection */
		Color_White	= 'ffffff00',
		Color_Black	= '00000000';

	/**
	 * Image constructor.
	 *
	 * @param int $iWidth
	 * @param int $iHeight
	 */
	public function __construct(int $iWidth, int $iHeight)
	{
		$this->setWidth($iWidth);
		$this->setHeight($iHeight);
		$this->createImage();
	}

	public function drawImage()
	{
		if($this->getStatus())
		{
			header('Content-Type: image/png');
			imagePNG($this->rsImage);
		}
	}

	/**
	 * @param string $strFileName
	 */
	public function saveImage(string $strFileName)
	{
		if($this->getStatus())
		{
			imagePNG($this->rsImage, $strFileName);
		}
	}

	/**
	 * Creates the base Image canvas and fills the Image with white with no transparency
	 */
	protected function createImage()
	{
		$iWidth		= $this->getWidth();
		$iHeight	= $this->getHeight();

		if($iHeight <= 0)
		{
			$this->setError('createImage: image height must be greater than 0');
		}

		if($iWidth <= 0)
		{
			$this->setError('createImage: image width must be greater than 0');
		}

		if($this->getStatus())
		{
			$this->rsImage	= imagecreatetruecolor($iWidth, $iHeight);
			$iColorID        = $this->setColor(self::Color_White);
			imagefill($this->rsImage, 0, 0, $iColorID);
		}
	}

	/**
	 * @return int
	 */
	protected function getWidth(): int
	{
		return $this->iWidth;
	}

	/**
	 * @param int $iWidth
	 */
	protected function setWidth(int $iWidth)
	{
		$this->iWidth	= $iWidth;
	}

	/**
	 * @return int
	 */
	public function getHeight(): int
	{
		return $this->iHeight;
	}

	/**
	 * @param int $iHeight
	 */
	protected function setHeight(int $iHeight)
	{
		$this->iHeight		= $iHeight;
	}

	/**
	 * @param $strHexColor
	 *
	 * @return int|bool
	 */
	protected function getColor(string $strHexColor)
	{
		return $this->arrColors[$strHexColor] ?? false;
	}

	/**
	 * @param string $strHexColor
	 *
	 * @return int|bool
	 */
	protected function setColor(string $strHexColor)
	{
		$strHexColor	= strtolower($strHexColor);
		$arrColors		= &$this->arrColors;
		$iColorID		= $this->getColor($strHexColor);

		if($iColorID === false)
		{
			$arrColors[$strHexColor]	= imagecolorallocatealpha(
				$this->rsImage,
				hexdec(substr($strHexColor, 0, 2)), # Red
				hexdec(substr($strHexColor, 2, 2)), # Green
				hexdec(substr($strHexColor, 4, 2)), # Blue
				hexdec(substr($strHexColor, 6, 2))  # Alpha
			);
			$iColorID					= $arrColors[$strHexColor];
		}

		return $iColorID;
	}

	/**
	 * @param int $iX1
	 * @param int $iY1
	 * @param int $iX2
	 * @param int $iY2
	 * @param int $iThickness
	 * @param string $strHexColor
	 */
	public function drawLine(int $iX1, int $iY1, int $iX2, int $iY2, int $iThickness, string $strHexColor)
	{
		$iWidth		= $this->getWidth();
		$iWeight	= $this->getHeight();

		if(($iX1 < 0) and ($iX1 > $iWidth))
		{
			$this->setError('drawLine: the value '.$iX1.' for x1 must be between 0 and '.$iWidth);
		}

		if(($iX2 < 0) and ($iX2 > $iWidth))
		{
			$this->setError('drawLine: the value '.$iX2.' for x2 must be between 0 and '.$iWidth);
		}

		if(($iY1 < 0) and ($iY1 > $iWeight))
		{
			$this->setError('drawLine: the value '.$iY1.' for y1 must be between 0 and '.$iWeight);
		}

		if(($iY2 < 0) and ($iY2 > $iWeight))
		{
			$this->setError('drawLine: the value '.$iY2.' for y2 must be between 0 and '.$iWeight);
		}

		$iColorID	= $this->setColor($strHexColor);

		if($this->getStatus())
		{
			imagesetthickness($this->rsImage, $iThickness);
			imageline($this->rsImage, $iX1, $iY1, $iX2, $iY2, $iColorID);
		}
	}

	/**
	 * @param int    $iX1
	 * @param int    $iY1
	 * @param int    $iX2
	 * @param int    $iY2
	 * @param int    $iThickness
	 * @param string $strHexColor
	 */
	public function drawRectangle(int $iX1, int $iY1, int $iX2, int $iY2, int $iThickness, string $strHexColor)
	{
		$iWidth		= $this->getWidth();
		$iHeight	= $this->getHeight();

		if(($iX1 < 0) and ($iX1 > $iWidth))
		{
			$this->setError('drawLine: the value '.$iX1.' for x1 must be between 0 and '.$iWidth);
		}

		if(($iX2 < 0) and ($iX2 > $iWidth))
		{
			$this->setError('drawLine: the value '.$iX2.' for x2 must be between 0 and '.$iWidth);
		}

		if(($iY1 < 0) and ($iY1 > $iHeight))
		{
			$this->setError('drawLine: the value '.$iY1.' for y1 must be between 0 and '.$iHeight);
		}

		if(($iY2 < 0) and ($iY2 > $iHeight))
		{
			$this->setError('drawLine: the value '.$iY2.' for y2 must be between 0 and '.$iHeight);
		}

		$iColorID	= $this->setColor($strHexColor);

		if($this->getStatus())
		{
			imagesetthickness($this->rsImage, $iThickness);

			$iCeilThickness		= ceil($iThickness / 2);
			$iFloorThickness	= floor($iThickness / 2);

			imageline($this->rsImage, ($iX1 - $iFloorThickness), $iY1, ($iX2 - $iCeilThickness), $iY1, $iColorID);
			imageline($this->rsImage, $iX1, ($iY1 + $iCeilThickness), $iX1, ($iY2 + $iFloorThickness), $iColorID);
			if($iCeilThickness === $iFloorThickness)
			{
				imageline($this->rsImage, ($iX1 + $iCeilThickness), ($iY2 + 1), ($iX2 + $iFloorThickness), ($iY2 + 1), $iColorID);
				imageline($this->rsImage, ($iX2 + 1), ($iY1 - $iFloorThickness), ($iX2 + 1), ($iY2 - $iCeilThickness), $iColorID);
			}
			else
			{
				imageline($this->rsImage, ($iX1 + $iCeilThickness), $iY2, ($iX2 + $iFloorThickness), $iY2, $iColorID);
				imageline($this->rsImage, $iX2, ($iY1 - $iFloorThickness), $iX2, ($iY2 - $iCeilThickness), $iColorID);
			}
		}
	}

	/**
	 * @return bool
	 */
	protected function getStatus(): bool
	{
		return $this->bStatus;
	}

	/**
	 * @param bool $bStatus
	 */
	protected function setStatus(bool $bStatus)
	{
		$this->bStatus = $bStatus;
	}

	/**
	 * @param string $strMessage
	 */
	protected function setError(string $strMessage)
	{
		$this->arrErrors[]	= $strMessage;
		$this->setStatus(false);
	}

	/**
	 * @param int    $iX
	 * @param int    $iY
	 * @param float  $fAngle
	 * @param string $strFontFileName
	 * @param float  $fFontSize
	 * @param string $strHexColor
	 * @param string $strText
	 */
	public function drawText(int $iX, int $iY, float $fAngle, string $strFontFileName, float $fFontSize, string $strHexColor, string $strText)
	{
		$strHexColor = $this->setColor($strHexColor);
		imagettftext($this->rsImage, $fFontSize, $fAngle, $iX, $iY, $strHexColor, $strFontFileName, $strText);
	}

	/**
	 * @param string $strFontFileName
	 */
	public function addFont(string $strFontFileName)
	{
		if (!in_array($strFontFileName, $this->arrFonts))
		{
			$this->arrFonts[]	= $strFontFileName;
		}
	}
}
