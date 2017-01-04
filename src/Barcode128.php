<?php
namespace Barcode128;

/**
 * Class Barcode128
 */
class Barcode128
{
	/** @var Image */
	protected $rsImage;

	protected $arrDimensions		= [
		'width'					=> false,
		'height'				=> false,
		'pixel_width'			=> false,
		'text_spacing'			=> false,
		'border_width'			=> false,
		'border_spacing'		=> false
	];

	protected $arrBoundingBox;
	protected $strCurrentSet		= '';

	protected $arrFlags				= [
		'ean_style'				=> false,
		'show_text'				=> false,
		'auto_adjust_font_size'	=> false
	];

	protected $arrText				= ['value' => '', 'data' => []];
	protected $arrEncoding			= ['value' => '', 'data' => [], 'strings' => []];
	protected $arrCheckSum			= ['value' => 0, 'data' => []];

	protected $strFontFileName		= '';
	protected $iFontSize;
	protected $iFontSizeOriginal;

	protected $arrSet				= [];
	protected $arrValue				= [];

	/**
	 * Barcode128 constructor.
	 *
	 * @param string      $strText
	 * @param int         $iHeight
	 * @param string      $strFontFileName
	 * @param int         $iFontSize
	 */
	public function __construct(string $strText = '', int $iHeight = 150, string $strFontFileName = '', int $iFontSize = 0)
	{
		//set the defaults for the barcode
		$this->setDefaults();
		$this->setText($strText);

		//some default parameters which can be overridden with the public methods
		$this->setBorderWidth(2);
		$this->setBorderSpacing(10);
		$this->setPixelWidth(1);
		$this->setEanStyle(true);
		$this->setShowText(true);
		$this->setAutoAdjustFontSize(true);
		$this->setTextSpacing(5);

		if(($strFontFileName !== '') and ($iFontSize !== 0))
		{
			$this->addFont($strFontFileName, $iFontSize);
			$this->iFontSizeOriginal	= $iFontSize;
		}

		//set the base dimensions and calculate the encoding sequence
		$this->setImageHeight($iHeight - (($this->getBorderWidth() + $this->getBorderSpacing()) * 2));
		$this->setImageWidth($this->calculateImageWidth());
	}

	/**
	 * @return bool
	 */
	public function getAutoAdjustFontSize(): bool
	{
		//gets the boolean value of auto_adjust_font_size
		return $this->arrFlags['auto_adjust_font_size'];
	}

	/**
	 * @param bool $bAutoAdjust
	 */
	public function setAutoAdjustFontSize(bool $bAutoAdjust)
	{
		//sets the boolean value of auto_adjust_font_size
		$this->arrFlags['auto_adjust_font_size']	= $bAutoAdjust;
		if ( $this->strFontFileName !== '' && $this->iFontSize !== 0 && !$this->getAutoAdjustFontSize())
		{
			$this->iFontSize		= $this->iFontSizeOriginal;
			$this->arrBoundingBox	= $this->imageTTFBoundingBoxExtended($this->getFontSize(), $this->getFont(), $this->getText());
		}
	}

	/**
	 * @return bool
	 */
	public function getEanStyle(): bool
	{
		//gets the boolean value of ean_style
		return $this->arrFlags['ean_style'];
	}

	/**
	 * @param bool $bEanStyle
	 */
	public function setEanStyle(bool $bEanStyle)
	{
		//sets the boolean value of ean_style
		$this->arrFlags['ean_style']	= $bEanStyle;
	}

	/**
	 * @return bool
	 */
	public function getShowText(): bool
	{
		//gets the boolean value of show_text
		return $this->arrFlags['show_text'];
	}

	/**
	 * @param bool $bShowText
	 */
	public function setShowText(bool $bShowText)
	{
		//sets the boolean value of show_text
		$this->arrFlags['show_text']	= $bShowText;
	}

	/**
	 * @return int
	 */
	public function getBorderSpacing(): int
	{
		//gets the spacing of the border between the border and the barcode
		return $this->arrDimensions['border_spacing'];
	}

	/**
	 * @param int $iBorderSpacingInPixels
	 */
	public function setBorderSpacing(int $iBorderSpacingInPixels)
	{
		//sets the spacing of the border between the border and the barcode
		$this->arrDimensions['border_spacing']	= $iBorderSpacingInPixels;

		//this changes the Image width so it needs to be calculated again
		$this->setImageWidth($this->calculateImageWidth());
	}

	/**
	 * @return int
	 */
	public function getBorderWidth(): int
	{
		//gets the width of the border
		return $this->arrDimensions['border_width'];
	}

	/**
	 * @param int $iBorderWidthInPixels
	 */
	public function setBorderWidth(int $iBorderWidthInPixels)
	{
		//sets the width of the border
		$this->arrDimensions['border_width']	= $iBorderWidthInPixels;

		//this changes the Image width so it needs to be calculated again
		$this->setImageWidth($this->calculateImageWidth());
	}

	/**
	 * @return int
	 */
	public function getTextSpacing(): int
	{
		//gets the size of the barcode pixel
		return $this->arrDimensions['text_spacing'];
	}

	/**
	 * @param $iTextSpacingInPixels
	 */
	public function setTextSpacing(int $iTextSpacingInPixels)
	{
		//sets the size of the barcode pixel
		$this->arrDimensions['text_spacing']	= $iTextSpacingInPixels;
	}

	/**
	 * @return int
	 */
	public function getPixelWidth(): int
	{
		//gets the size of the barcode pixel
		return $this->arrDimensions['pixel_width'];
	}

	/**
	 * @param int $iPixelWidthInPixels
	 */
	public function setPixelWidth(int $iPixelWidthInPixels)
	{
		//sets the size of the barcode pixel
		$this->arrDimensions['pixel_width'] = $iPixelWidthInPixels;

		//this changes the Image width so it needs to be calculated again
		$this->setImageWidth($this->calculateImageWidth());
	}

	/**
	 * @return int
	 */
	public function getImageWidth(): int
	{
		//gets the width of the Image
		return $this->arrDimensions['width'];
	}

	/**
	 * @param int $iWidthPixels
	 */
	public function setImageWidth(int $iWidthPixels)
	{
		//sets the width of the Image and recreates the canvas
		$this->arrDimensions['width']	= $iWidthPixels;
		$this->rsImage					= new Image($this->getImageWidth(), $this->getImageHeight());
		if($this->strFontFileName !== '')
		{
			$this->setFont($this->getFont());
			if($this->getAutoAdjustFontSize())
			{
				if($this->getEANStyle())
				{
					$iWidth		= ($this->getImageWidth() - (($this->getBorderWidth() + $this->getBorderSpacing()) * 2) - (($this->getPixelWidth() * 11) * 2) - ($this->getPixelWidth() * 2) - ($this->getTextSpacing() * 2));
				}
				else
				{
					$iWidth		= ($this->getImageWidth() - (($this->getBorderWidth() + $this->getBorderSpacing() + $this->getTextSpacing()) * 2));
				}
				$this->setFontSize($this->calculateTextSize($iWidth));
			}
			else
			{
				$this->arrBoundingBox	= $this->imageTTFBoundingBoxExtended($this->getFontSize(), $this->getFont(), $this->getText());
			}
		}
	}

	/**
	 * @return int
	 */
	public function getImageHeight(): int
	{
		//gets the height of the Image
		return $this->arrDimensions['height'];
	}

	/**
	 * @param int $iHeightInPixels
	 */
	public function setImageHeight(int $iHeightInPixels)
	{
		//sets the height of the Image
		$this->arrDimensions['height']	= $iHeightInPixels;
	}

	/**
	 * @return string
	 */
	public function getFont(): string
	{
		//gets the font
		return $this->strFontFileName;
	}

	/**
	 * @param string $strFontFileName
	 */
	public function setFont(string $strFontFileName)
	{
		//sets the font
		$this->strFontFileName	= $strFontFileName;
		$this->rsImage->addFont($strFontFileName);
	}

	/**
	 * @return int
	 */
	public function getFontSize(): int
	{
		//gets the font size
		return $this->iFontSize;
	}

	/**
	 * @param int $iFontSize
	 */
	public function setFontSize(int $iFontSize)
	{
		//sets the font size
		$this->iFontSize	= $iFontSize;
	}

	/**
	 * @param string $strFontFileName
	 * @param int $iFontSize
	 */
	protected function addFont(string $strFontFileName, int $iFontSize)
	{
		//adds the font and the size
		$this->setFont($strFontFileName);
		$this->setFontSize($iFontSize);
	}

	public function getBarcode()
	{
		//gets the Image
		$this->drawImage();
		$this->rsImage->drawImage();
	}

	/**
	 * @param $strFileName
	 */
	public function saveBarcode(string $strFileName)
	{
		//saves the Image to the given filename
		$this->drawImage();
		$this->rsImage->saveImage($strFileName);
	}

	/**
	 * @return string
	 */
	public function getText(): string
	{
		//gets the text
		return $this->arrText['value'];
	}

	/**
	 * @param string $strText
	 */
	public function setText(string $strText)
	{
		//sets the barcode text
		$this->arrText['value']		= $strText;
		$this->generateDataForEncoding();
	}

	/**
	 * @return int
	 */
	protected function calculateImageWidth(): int
	{
		//calculates the Image width based on the (pixel iWidth * encoded text) + border iWidth + border spacing
		return ((strlen($this->arrEncoding['value']) * $this->getPixelWidth()) + (($this->getBorderWidth() + $this->getBorderSpacing()) * 2));
	}

	protected function drawImage()
	{
		$this->setImageWidth($this->calculateImageWidth());

		//draws the border on the Image canvas
		if($this->getBorderWidth() >= 1)
		{
			$this->drawBorder();
		}

		//draws the barcode on the Image canvas
		$this->drawBarcode();

		//draws the text on the Image canvas
		if($this->getShowText())
		{
			$this->drawText();
		}
	}

	protected function drawBorder()
	{
		//draws a border around the Image

		$iMax	= $this->getBorderWidth() - 1;

		for($iCount = 0; $iCount <= $iMax; $iCount++)
		{
			$this->rsImage->drawRectangle($iCount, $iCount, $this->getImageWidth() - 1 - $iCount, $this->getImageHeight() - 1 - $iCount, 1, Image::Color_Black);
		}
	}

	protected function drawBarcode()
	{
		//draws the barcode on the Image
		$strString		= $this->arrEncoding['value'];
		$rsImage		= &$this->rsImage;

		//set the position to start drawing the bars from
		$iX		= ($this->getBorderWidth() + $this->getBorderSpacing());
		$iMax	= strlen($strString) - 1;

		for($iCount = 0; $iCount <= $iMax; $iCount++)
		{
			$strHexColor	= Image::Color_White;
			if( (int)$strString{$iCount} === 1 )
			{
				$strHexColor	= Image::Color_Black;
			}
			$iMax2	= $this->getPixelWidth() - 1;
			for($iCount2 = 0; $iCount2 <= $iMax2; $iCount2++)
			{
				if($this->getShowText())
				{
					//need to work out the dimensions of the font being used if auto or grab them if not auto
					if($this->getEANStyle())
					{
						if( ( $iCount >= 0 && $iCount <= 10 ) || ( ( $iCount >= (strlen($strString) - 13) ) && ( $iCount <= (strlen($strString) - 1) ) ) )
						{
							//this is to draw the start and end characters differently if using ean style
							$iY1	= ($this->getBorderWidth() + $this->getBorderSpacing());
							$iY2	= ($rsImage->getHeight() - ($this->getBorderWidth() + $this->getBorderSpacing())) - ($this->arrBoundingBox['height'] / 2);
						}
						else
						{
							$iY1	= ($this->getBorderWidth() + $this->getBorderSpacing());
							$iY2	= ($rsImage->getHeight() - ($this->getBorderWidth() + $this->getBorderSpacing() + $this->getTextSpacing())) - $this->arrBoundingBox['height'];
						}
					}
					else
					{
						$iY1	= ($this->getBorderWidth() + $this->getBorderSpacing());
						$iY2	= ($rsImage->getHeight() - ($this->getBorderWidth() + $this->getBorderSpacing() + $this->getTextSpacing()) - ($this->arrBoundingBox['height']));
					}
				}
				else
				{
					if($this->getEANStyle())
					{
						if( ( $iCount >= 0 && $iCount <= 10 ) || ( ($iCount >= (strlen($strString) - 13)) && ($iCount <= (strlen($strString) - 1) ) ) )
						{
							//this is to draw the start and end characters differently if using ean style
							$iY1	= ($this->getBorderWidth() + $this->getBorderSpacing());
							$iY2	= ($rsImage->getHeight() - ($this->getBorderWidth() + $this->getBorderSpacing()));
						}
						else
						{
							$iY1	= ($this->getBorderWidth() + $this->getBorderSpacing());
							$iY2	= ($rsImage->getHeight() - ($this->getBorderWidth() + $this->getBorderSpacing())) - (($rsImage->getHeight() - ($this->getBorderWidth() + $this->getBorderSpacing())) * 0.25);
						}
					}
					else
					{
						$iY1	= ($this->getBorderWidth() + $this->getBorderSpacing());
						$iY2	= ($rsImage->getHeight() - ($this->getBorderWidth() + $this->getBorderSpacing()));
					}
				}
				$rsImage->drawLine($iX, $iY1, $iX, $iY2, 1, $strHexColor);
				$iX++;
			}
		}
	}

	protected function drawText()
	{
		$this->rsImage->drawText(
			( ( ( $this->getImageWidth() - $this->arrBoundingBox['width'] ) / 2 ) - abs($this->arrBoundingBox['x']) ),
			$this->getImageHeight() - abs($this->arrBoundingBox[1]) - $this->getBorderWidth() - $this->getBorderSpacing(),
			0,
			$this->getFont(),
			$this->getFontSize(),
			Image::Color_Black,
			$this->getText()
		);
	}

	/**
	 * @param int    $iSize
	 * @param string $strFontFileName
	 * @param string $strText
	 *
	 * @return array
	 */
	protected function imageTTFBoundingBoxExtended(int $iSize, string $strFontFileName, string $strText)
	{
		$arrBoundingBox		= imagettfbbox($iSize, 0, $strFontFileName, $strText);

		if($arrBoundingBox[0] >= -1)
		{
			$arrBoundingBox['x']	= abs($arrBoundingBox[0] + 1) * -1;
		}
		else
		{
			$arrBoundingBox['x']	= abs($arrBoundingBox[0] + 2);
		}

		$arrBoundingBox['width']	= abs($arrBoundingBox[2] - $arrBoundingBox[0]);

		if($arrBoundingBox[0] < -1)
		{
			$arrBoundingBox['width']	= abs($arrBoundingBox[2]) + abs($arrBoundingBox[0]) - 1;
		}

		$arrBoundingBox['y']		= abs($arrBoundingBox[5] + 1);
		$arrBoundingBox['height']	= abs($arrBoundingBox[7]) - abs($arrBoundingBox[1]);

		if($arrBoundingBox[3] > 0)
		{
			$arrBoundingBox['height']	= abs($arrBoundingBox[7] - $arrBoundingBox[1]) - 1;
		}

		return $arrBoundingBox;
	}

	/**
	 * @param int $iWidth
	 *
	 * @return int
	 */
	protected function calculateTextSize(int $iWidth): int
	{
		//loop up from point size 1 until the font exceeds the width and then return the size to use
		$iCount		= 1;
		$bContinue	= true;
		while($bContinue)
		{
			$this->arrBoundingBox		= $this->imageTTFBoundingBoxExtended($iCount, $this->getFont(), $this->getText());
			if($this->arrBoundingBox['width'] < $iWidth)
			{
				$iCount++;
			}
			else
			{
				$iCount--;
				$this->arrBoundingBox	= $this->imageTTFBoundingBoxExtended($iCount, $this->getFont(), $this->getText());
				$bContinue		= false;
			}
		}

		return $iCount;
	}

	/**
	 * @param $strSet
	 */
	protected function setStartSet(string $strSet)
	{
		//sets the starting set
		$this->strCurrentSet	= $strSet;
		switch($strSet)
		{
			case 'A':
				$this->addChecksum('103');
				$this->addEncode('103');
				break;
			case 'B':
				$this->addChecksum('104');
				$this->addEncode('104');
				break;
			case 'C':
				$this->addChecksum('105');
				$this->addEncode('105');
				break;
		}
	}

	/**
	 * @param string $strSet
	 */
	protected function changeSet(string $strSet)
	{
		//changes the set being used
		$this->strCurrentSet	= $strSet;
		switch($this->strCurrentSet)
		{
			case 'A':
				$this->addChecksum('101');
				$this->addEncode('101');
				break;
			case 'B':
				$this->addChecksum('100');
				$this->addEncode('100');
				break;
			case 'C':
				$this->addChecksum('99');
				$this->addEncode('99');
				break;
		}
	}

	protected function generateDataForEncoding()
	{
		//generates the data to be encoded
		$strText	= $this->getText();
		$arrData	= &$this->arrText['data'];

		$iMax	= strlen($strText) - 1;
		for($iCount = 0; $iCount <= $iMax; $iCount++)
		{
			if($iCount === $iMax )
			{
				//last character
				$strValue	= $strText{$iCount};
				if($strText{$iCount} === ' ')
				{
					$strValue	= 'SP';
				}
				$arrData[]		= $strValue;
			}
			else
			{
				if ( (is_numeric($strText{$iCount})) && ( is_numeric( $strText{($iCount + 1)} ) ) )
				{
					//looks for double digit values
					$arrData[]	= $strText{$iCount} . $strText{($iCount + 1)};
					$iCount++;
				}
				else
				{
					$strValue	= $strText{$iCount};
					if($strText{$iCount} === ' ')
					{
						$strValue	= 'SP';
					}
					$arrData[]	= $strValue;
				}
			}
		}

		//generates the barcode data for each of the characters
		$this->setStartSet($this->getCharacterSet($arrData[0]));

		$iMax	= count($arrData) - 1;
		for($iCount = 0; $iCount <= $iMax; $iCount++)
		{
			$strSet		= $this->getCharacterSet($arrData[$iCount]);
			if($strSet !== $this->strCurrentSet)
			{
				$this->changeSet($strSet);
			}
			$strValue	= $this->getCharacterValue($strSet, $arrData[$iCount]);
			$this->addChecksum($strValue);
			$this->addEncode($strValue);
		}
		$this->addEncode((string)($this->arrCheckSum['value'] % 103));
		$this->addEncode('106');
	}

	/**
	 * @param string $strSet
	 * @param string $strCharacter
	 *
	 * @return string
	 */
	protected function getCharacterValue(string $strSet, string $strCharacter): string
	{
		//gets the value of the character for the given set
		return $this->arrSet[$strSet][$strCharacter];
	}

	/**
	 * @param string $strCharacter
	 *
	 * @return string
	 */
	protected function getCharacterSet(string $strCharacter): string
	{
		//gets the set that the given character is contained in.  Checks current arrSet before searching the alternate sets

		$arrSets		= ['A', 'B', 'C'];

		if($this->strCurrentSet !== '')
		{
			if(array_key_exists($strCharacter, $this->arrSet[$this->strCurrentSet]))
			{
				return $this->strCurrentSet;
			}

			$iIndex		= array_search($this->strCurrentSet, $arrSets);
			unset($arrSets[$iIndex]);
			sort($arrSets);
		}
		$iMax	= count($arrSets) - 1;
		for($iIndex = 0; $iIndex <= $iMax; $iIndex++)
		{
			if(array_key_exists($strCharacter, $this->arrSet[$arrSets[$iIndex]]))
			{
				return $arrSets[$iIndex];
			}
		}
		return $arrSets[0];
	}

	/**
	 * @param string $strValue
	 */
	protected function addEncode(string $strValue)
	{
		$this->arrEncoding['data'][]		 = $strValue;
		$this->arrEncoding['value']			.= $this->getValue($strValue);
		$this->arrEncoding['strings'][]		 = $this->getValue($strValue);
	}

	/**
	 * @param string $strValue
	 */
	protected function addChecksum($strValue)
	{
		//adds the checksum
		if(count($this->arrCheckSum['data']) === 0)
		{
			$this->arrCheckSum['data'][]	= $strValue;
		}
		else
		{
			$this->arrCheckSum['data'][]	= ($strValue * (count($this->arrCheckSum['data'])));
		}
		$this->arrCheckSum['value']			= array_sum($this->arrCheckSum['data']);
	}

	/**
	 * @param string $strValue
	 *
	 * @return string
	 */
	protected function getValue(string $strValue): string
	{
		//gets the value string for a given arrValue
		return $this->arrValue[$strValue];
	}

	protected function setDefaults()
	{
		//sets the default sets and values
		$arrSet		= &$this->arrSet;
		$arrValue	= &$this->arrValue;

		$arrSet['A']['SP']     = '0';
		$arrSet['A']['!']      = '1';
		$arrSet['A']['"']      = '2';
		$arrSet['A']['#']      = '3';
		$arrSet['A']['$']      = '4';
		$arrSet['A']['%']      = '5';
		$arrSet['A']['&']      = '6';
		$arrSet['A']["'"]      = '7';
		$arrSet['A']['(']      = '8';
		$arrSet['A'][')']      = '9';
		$arrSet['A']['*']      = '10';
		$arrSet['A']['+']      = '11';
		$arrSet['A'][',']      = '12';
		$arrSet['A']['-']      = '13';
		$arrSet['A']['.']      = '14';
		$arrSet['A']['/']      = '15';
		$arrSet['A']['0']      = '16';
		$arrSet['A']['1']      = '17';
		$arrSet['A']['2']      = '18';
		$arrSet['A']['3']      = '19';
		$arrSet['A']['4']      = '20';
		$arrSet['A']['5']      = '21';
		$arrSet['A']['6']      = '22';
		$arrSet['A']['7']      = '23';
		$arrSet['A']['8']      = '24';
		$arrSet['A']['9']      = '25';
		$arrSet['A'][':']      = '26';
		$arrSet['A'][';']      = '27';
		$arrSet['A']['<']      = '28';
		$arrSet['A']['=']      = '29';
		$arrSet['A']['>']      = '30';
		$arrSet['A']['?']      = '31';
		$arrSet['A']['@']      = '32';
		$arrSet['A']['A']      = '33';
		$arrSet['A']['B']      = '34';
		$arrSet['A']['C']      = '35';
		$arrSet['A']['D']      = '36';
		$arrSet['A']['E']      = '37';
		$arrSet['A']['F']      = '38';
		$arrSet['A']['G']      = '39';
		$arrSet['A']['H']      = '40';
		$arrSet['A']['I']      = '41';
		$arrSet['A']['J']      = '42';
		$arrSet['A']['K']      = '43';
		$arrSet['A']['L']      = '44';
		$arrSet['A']['M']      = '45';
		$arrSet['A']['N']      = '46';
		$arrSet['A']['O']      = '47';
		$arrSet['A']['P']      = '48';
		$arrSet['A']['Q']      = '49';
		$arrSet['A']['R']      = '50';
		$arrSet['A']['S']      = '51';
		$arrSet['A']['T']      = '52';
		$arrSet['A']['U']      = '53';
		$arrSet['A']['V']      = '54';
		$arrSet['A']['W']      = '55';
		$arrSet['A']['X']      = '56';
		$arrSet['A']['Y']      = '57';
		$arrSet['A']['Z']      = '58';
		$arrSet['A']['[']      = '59';
		$arrSet['A']["\\"]     = '60';
		$arrSet['A'][']']      = '61';
		$arrSet['A']['^']      = '62';
		$arrSet['A']['_']      = '63';
		$arrSet['A']['NUL']    = '64';
		$arrSet['A']['SOH']    = '65';
		$arrSet['A']['STX']    = '66';
		$arrSet['A']['ETX']    = '67';
		$arrSet['A']['EOT']    = '68';
		$arrSet['A']['ENQ']    = '69';
		$arrSet['A']['ACK']    = '70';
		$arrSet['A']['BEL']    = '71';
		$arrSet['A']['BS']     = '72';
		$arrSet['A']['HT']     = '73';
		$arrSet['A']['LF']     = '74';
		$arrSet['A']['VT']     = '75';
		$arrSet['A']['FF']     = '76';
		$arrSet['A']['CR']     = '77';
		$arrSet['A']['SO']     = '78';
		$arrSet['A']['SI']     = '79';
		$arrSet['A']['DLE']    = '80';
		$arrSet['A']['DC1']    = '81';
		$arrSet['A']['DC2']    = '82';
		$arrSet['A']['DC3']    = '83';
		$arrSet['A']['DC4']    = '84';
		$arrSet['A']['NAK']    = '85';
		$arrSet['A']['SYN']    = '86';
		$arrSet['A']['ETB']    = '87';
		$arrSet['A']['CAN']    = '88';
		$arrSet['A']['EM']     = '89';
		$arrSet['A']['SUB']    = '90';
		$arrSet['A']['ESC']    = '91';
		$arrSet['A']['FS']     = '92';
		$arrSet['A']['GS']     = '93';
		$arrSet['A']['RS']     = '94';
		$arrSet['A']['US']     = '95';
		$arrSet['A']['FNC3']   = '96';
		$arrSet['A']['FNC2']   = '97';
		$arrSet['A']['SHIFT']  = '98';
		$arrSet['A']['CodeC']  = '99';
		$arrSet['A']['CodeB']  = '100';
		$arrSet['A']['FNC4']   = '101';
		$arrSet['A']['FNC1']   = '102';
		/** @noinspection SpellCheckingInspection */
		$arrSet['A']['STARTA'] = '103';
		/** @noinspection SpellCheckingInspection */
		$arrSet['A']['STARTB'] = '104';
		/** @noinspection SpellCheckingInspection */
		$arrSet['A']['STARTC'] = '105';
		$arrSet['A']['STOP']   = '106';

		$arrSet['B']['SP']     = '0';
		$arrSet['B']['!']      = '1';
		$arrSet['B']['"']      = '2';
		$arrSet['B']['#']      = '3';
		$arrSet['B']['$']      = '4';
		$arrSet['B']['%']      = '5';
		$arrSet['B']['&']      = '6';
		$arrSet['B']["'"]      = '7';
		$arrSet['B']['(']      = '8';
		$arrSet['B'][')']      = '9';
		$arrSet['B']['*']      = '10';
		$arrSet['B']['+']      = '11';
		$arrSet['B'][',']      = '12';
		$arrSet['B']['-']      = '13';
		$arrSet['B']['.']      = '14';
		$arrSet['B']['/']      = '15';
		$arrSet['B']['0']      = '16';
		$arrSet['B']['1']      = '17';
		$arrSet['B']['2']      = '18';
		$arrSet['B']['3']      = '19';
		$arrSet['B']['4']      = '20';
		$arrSet['B']['5']      = '21';
		$arrSet['B']['6']      = '22';
		$arrSet['B']['7']      = '23';
		$arrSet['B']['8']      = '24';
		$arrSet['B']['9']      = '25';
		$arrSet['B'][':']      = '26';
		$arrSet['B'][';']      = '27';
		$arrSet['B']['<']      = '28';
		$arrSet['B']['=']      = '29';
		$arrSet['B']['>']      = '30';
		$arrSet['B']['?']      = '31';
		$arrSet['B']['@']      = '32';
		$arrSet['B']['A']      = '33';
		$arrSet['B']['B']      = '34';
		$arrSet['B']['C']      = '35';
		$arrSet['B']['D']      = '36';
		$arrSet['B']['E']      = '37';
		$arrSet['B']['F']      = '38';
		$arrSet['B']['G']      = '39';
		$arrSet['B']['H']      = '40';
		$arrSet['B']['I']      = '41';
		$arrSet['B']['J']      = '42';
		$arrSet['B']['K']      = '43';
		$arrSet['B']['L']      = '44';
		$arrSet['B']['M']      = '45';
		$arrSet['B']['N']      = '46';
		$arrSet['B']['O']      = '47';
		$arrSet['B']['P']      = '48';
		$arrSet['B']['Q']      = '49';
		$arrSet['B']['R']      = '50';
		$arrSet['B']['S']      = '51';
		$arrSet['B']['T']      = '52';
		$arrSet['B']['U']      = '53';
		$arrSet['B']['V']      = '54';
		$arrSet['B']['W']      = '55';
		$arrSet['B']['X']      = '56';
		$arrSet['B']['Y']      = '57';
		$arrSet['B']['Z']      = '58';
		$arrSet['B']['[']      = '59';
		$arrSet['B']["\\"]     = '60';
		$arrSet['B'][']']      = '61';
		$arrSet['B']['^']      = '62';
		$arrSet['B']['_']      = '63';
		$arrSet['B']['`']      = '64';
		$arrSet['B']['a']      = '65';
		$arrSet['B']['b']      = '66';
		$arrSet['B']['c']      = '67';
		$arrSet['B']['d']      = '68';
		$arrSet['B']['e']      = '69';
		$arrSet['B']['f']      = '70';
		$arrSet['B']['g']      = '71';
		$arrSet['B']['h']      = '72';
		$arrSet['B']['i']      = '73';
		$arrSet['B']['j']      = '74';
		$arrSet['B']['k']      = '75';
		$arrSet['B']['l']      = '76';
		$arrSet['B']['m']      = '77';
		$arrSet['B']['n']      = '78';
		$arrSet['B']['o']      = '79';
		$arrSet['B']['p']      = '80';
		$arrSet['B']['q']      = '81';
		$arrSet['B']['r']      = '82';
		$arrSet['B']['s']      = '83';
		$arrSet['B']['t']      = '84';
		$arrSet['B']['u']      = '85';
		$arrSet['B']['v']      = '86';
		$arrSet['B']['w']      = '87';
		$arrSet['B']['x']      = '88';
		$arrSet['B']['y']      = '89';
		$arrSet['B']['z']      = '90';
		$arrSet['B']['{']      = '91';
		$arrSet['B']['|']      = '92';
		$arrSet['B']['}']      = '93';
		$arrSet['B']['~']      = '94';
		$arrSet['B']['DEL']    = '95';
		$arrSet['B']['FNC3']   = '96';
		$arrSet['B']['FNC2']   = '97';
		$arrSet['B']['SHIFT']  = '98';
		$arrSet['B']['CodeC']  = '99';
		$arrSet['B']['FNC4']   = '100';
		$arrSet['B']['CodeA']  = '101';
		$arrSet['B']['FNC1']   = '102';
		/** @noinspection SpellCheckingInspection */
		$arrSet['B']['STARTA'] = '103';
		/** @noinspection SpellCheckingInspection */
		$arrSet['B']['STARTB'] = '104';
		/** @noinspection SpellCheckingInspection */
		$arrSet['B']['STARTC'] = '105';
		$arrSet['B']['STOP']   = '106';

		$arrSet['C']['00']     = '0';
		$arrSet['C']['01']     = '1';
		$arrSet['C']['02']     = '2';
		$arrSet['C']['03']     = '3';
		$arrSet['C']['04']     = '4';
		$arrSet['C']['05']     = '5';
		$arrSet['C']['06']     = '6';
		$arrSet['C']['07']     = '7';
		$arrSet['C']['08']     = '8';
		$arrSet['C']['09']     = '9';
		$arrSet['C']['10']     = '10';
		$arrSet['C']['11']     = '11';
		$arrSet['C']['12']     = '12';
		$arrSet['C']['13']     = '13';
		$arrSet['C']['14']     = '14';
		$arrSet['C']['15']     = '15';
		$arrSet['C']['16']     = '16';
		$arrSet['C']['17']     = '17';
		$arrSet['C']['18']     = '18';
		$arrSet['C']['19']     = '19';
		$arrSet['C']['20']     = '20';
		$arrSet['C']['21']     = '21';
		$arrSet['C']['22']     = '22';
		$arrSet['C']['23']     = '23';
		$arrSet['C']['24']     = '24';
		$arrSet['C']['25']     = '25';
		$arrSet['C']['26']     = '26';
		$arrSet['C']['27']     = '27';
		$arrSet['C']['28']     = '28';
		$arrSet['C']['29']     = '29';
		$arrSet['C']['30']     = '30';
		$arrSet['C']['31']     = '31';
		$arrSet['C']['32']     = '32';
		$arrSet['C']['33']     = '33';
		$arrSet['C']['34']     = '34';
		$arrSet['C']['35']     = '35';
		$arrSet['C']['36']     = '36';
		$arrSet['C']['37']     = '37';
		$arrSet['C']['38']     = '38';
		$arrSet['C']['39']     = '39';
		$arrSet['C']['40']     = '40';
		$arrSet['C']['41']     = '41';
		$arrSet['C']['42']     = '42';
		$arrSet['C']['43']     = '43';
		$arrSet['C']['44']     = '44';
		$arrSet['C']['45']     = '45';
		$arrSet['C']['46']     = '46';
		$arrSet['C']['47']     = '47';
		$arrSet['C']['48']     = '48';
		$arrSet['C']['49']     = '49';
		$arrSet['C']['50']     = '50';
		$arrSet['C']['51']     = '51';
		$arrSet['C']['52']     = '52';
		$arrSet['C']['53']     = '53';
		$arrSet['C']['54']     = '54';
		$arrSet['C']['55']     = '55';
		$arrSet['C']['56']     = '56';
		$arrSet['C']['57']     = '57';
		$arrSet['C']['58']     = '58';
		$arrSet['C']['59']     = '59';
		$arrSet['C']['60']     = '60';
		$arrSet['C']['61']     = '61';
		$arrSet['C']['62']     = '62';
		$arrSet['C']['63']     = '63';
		$arrSet['C']['64']     = '64';
		$arrSet['C']['65']     = '65';
		$arrSet['C']['66']     = '66';
		$arrSet['C']['67']     = '67';
		$arrSet['C']['68']     = '68';
		$arrSet['C']['69']     = '69';
		$arrSet['C']['70']     = '70';
		$arrSet['C']['71']     = '71';
		$arrSet['C']['72']     = '72';
		$arrSet['C']['73']     = '73';
		$arrSet['C']['74']     = '74';
		$arrSet['C']['75']     = '75';
		$arrSet['C']['76']     = '76';
		$arrSet['C']['77']     = '77';
		$arrSet['C']['78']     = '78';
		$arrSet['C']['79']     = '79';
		$arrSet['C']['80']     = '80';
		$arrSet['C']['81']     = '81';
		$arrSet['C']['82']     = '82';
		$arrSet['C']['83']     = '83';
		$arrSet['C']['84']     = '84';
		$arrSet['C']['85']     = '85';
		$arrSet['C']['86']     = '86';
		$arrSet['C']['87']     = '87';
		$arrSet['C']['88']     = '88';
		$arrSet['C']['89']     = '89';
		$arrSet['C']['90']     = '90';
		$arrSet['C']['91']     = '91';
		$arrSet['C']['92']     = '92';
		$arrSet['C']['93']     = '93';
		$arrSet['C']['94']     = '94';
		$arrSet['C']['95']     = '95';
		$arrSet['C']['96']     = '96';
		$arrSet['C']['97']     = '97';
		$arrSet['C']['98']     = '98';
		$arrSet['C']['99']     = '99';
		$arrSet['C']['CodeB']  = '100';
		$arrSet['C']['CodeA']  = '101';
		$arrSet['C']['FNC1']   = '102';
		/** @noinspection SpellCheckingInspection */
		$arrSet['C']['STARTA'] = '103';
		/** @noinspection SpellCheckingInspection */
		$arrSet['C']['STARTB'] = '104';
		/** @noinspection SpellCheckingInspection */
		$arrSet['C']['STARTC'] = '105';
		$arrSet['C']['STOP']   = '106';

		$arrValue['0'] = '11011001100';
		$arrValue['1'] = '11001101100';
		$arrValue['2'] = '11001100110';
		$arrValue['3'] = '10010011000';
		$arrValue['4'] = '10010001100';
		$arrValue['5'] = '10001001100';
		$arrValue['6'] = '10011001000';
		$arrValue['7'] = '10011000100';
		$arrValue['8'] = '10001100100';
		$arrValue['9'] = '11001001000';
		$arrValue['10'] = '11001000100';
		$arrValue['11'] = '11000100100';
		$arrValue['12'] = '10110011100';
		$arrValue['13'] = '10011011100';
		$arrValue['14'] = '10011001110';
		$arrValue['15'] = '10111001100';
		$arrValue['16'] = '10011101100';
		$arrValue['17'] = '10011100110';
		$arrValue['18'] = '11001110010';
		$arrValue['19'] = '11001011100';
		$arrValue['20'] = '11001001110';
		$arrValue['21'] = '11011100100';
		$arrValue['22'] = '11001110100';
		$arrValue['23'] = '11101101110';
		$arrValue['24'] = '11101001100';
		$arrValue['25'] = '11100101100';
		$arrValue['26'] = '11100100110';
		$arrValue['27'] = '11101100100';
		$arrValue['28'] = '11100110100';
		$arrValue['29'] = '11100110010';
		$arrValue['30'] = '11011011000';
		$arrValue['31'] = '11011000110';
		$arrValue['32'] = '11000110110';
		$arrValue['33'] = '10100011000';
		$arrValue['34'] = '10001011000';
		$arrValue['35'] = '10001000110';
		$arrValue['36'] = '10110001000';
		$arrValue['37'] = '10001101000';
		$arrValue['38'] = '10001100010';
		$arrValue['39'] = '11010001000';
		$arrValue['40'] = '11000101000';
		$arrValue['41'] = '11000100010';
		$arrValue['42'] = '10110111000';
		$arrValue['43'] = '10110001110';
		$arrValue['44'] = '10001101110';
		$arrValue['45'] = '10111011000';
		$arrValue['46'] = '10111000110';
		$arrValue['47'] = '10001110110';
		$arrValue['48'] = '11101110110';
		$arrValue['49'] = '11010001110';
		$arrValue['50'] = '11000101110';
		$arrValue['51'] = '11011101000';
		$arrValue['52'] = '11011100010';
		$arrValue['53'] = '11011101110';
		$arrValue['54'] = '11101011000';
		$arrValue['55'] = '11101000110';
		$arrValue['56'] = '11100010110';
		$arrValue['57'] = '11101101000';
		$arrValue['58'] = '11101100010';
		$arrValue['59'] = '11100011010';
		$arrValue['60'] = '11101111010';
		$arrValue['61'] = '11001000010';
		$arrValue['62'] = '11110001010';
		$arrValue['63'] = '10100110000';
		$arrValue['64'] = '10100001100';
		$arrValue['65'] = '10010110000';
		$arrValue['66'] = '10010000110';
		$arrValue['67'] = '10000101100';
		$arrValue['68'] = '10000100110';
		$arrValue['69'] = '10110010000';
		$arrValue['70'] = '10110000100';
		$arrValue['71'] = '10011010000';
		$arrValue['72'] = '10011000010';
		$arrValue['73'] = '10000110100';
		$arrValue['74'] = '10000110010';
		$arrValue['75'] = '11000010010';
		$arrValue['76'] = '11001010000';
		$arrValue['77'] = '11110111010';
		$arrValue['78'] = '11000010100';
		$arrValue['79'] = '10001111010';
		$arrValue['80'] = '10100111100';
		$arrValue['81'] = '10010111100';
		$arrValue['82'] = '10010011110';
		$arrValue['83'] = '10111100100';
		$arrValue['84'] = '10011110100';
		$arrValue['85'] = '10011110010';
		$arrValue['86'] = '11110100100';
		$arrValue['87'] = '11110010100';
		$arrValue['88'] = '11110010010';
		$arrValue['89'] = '11011011110';
		$arrValue['90'] = '11011110110';
		$arrValue['91'] = '11110110110';
		$arrValue['92'] = '10101111000';
		$arrValue['93'] = '10100011110';
		$arrValue['94'] = '10001011110';
		$arrValue['95'] = '10111101000';
		$arrValue['96'] = '10111100010';
		$arrValue['97'] = '11110101000';
		$arrValue['98'] = '11110100010';
		$arrValue['99'] = '10111011110';
		$arrValue['100'] = '10111101110';
		$arrValue['101'] = '11101011110';
		$arrValue['102'] = '11110101110';
		$arrValue['103'] = '11010000100';
		$arrValue['104'] = '11010010000';
		$arrValue['105'] = '11010011100';
		$arrValue['106'] = '1100011101011';
	}
}

