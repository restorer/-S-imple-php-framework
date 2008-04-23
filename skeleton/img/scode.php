<?php

function RandomColor($st_mono, $st_clr)
{
	$g = rand(0, $st_mono);
	$gr = $g + rand(0, $st_clr);
	$gg = $g + rand(0, $st_clr);
	$gb = $g + rand(0, $st_clr);
	$cl = ($gr << 16) + ($gg << 8) + $gb;
	return $cl;
}

function SubColors($cl1, $cl2)
{
	$r1 = ($cl1 >> 16) & 0xFF;
	$g1 = ($cl1 >> 8) & 0xFF;
	$b1 = $cl1 & 0xFF;

	$r2 = ($cl2 >> 16) & 0xFF;
	$g2 = ($cl2 >> 8) & 0xFF;
	$b2 = $cl2 & 0xFF;

	$r = ($r2>$r1)?0:($r1-$r2);
	$g = ($g2>$g1)?0:($g1-$g2);
	$b = ($b2>$b1)?0:($b1-$b2);

	$cl = ($r << 16) + ($g << 8) + $b;
	return $cl;
}

function AddColors($cl1, $cl2)
{
	$r1 = ($cl1 >> 16) & 0xFF;
	$g1 = ($cl1 >> 8) & 0xFF;
	$b1 = $cl1 & 0xFF;

	$r2 = ($cl2 >> 16) & 0xFF;
	$g2 = ($cl2 >> 8) & 0xFF;
	$b2 = $cl2 & 0xFF;

	$r = (($r1+$r2)>255)?255:($r1+$r2);
	$g = (($g1+$g2)>255)?255:($g1+$g2);
	$b = (($b1+$b2)>255)?255:($b1+$b2);

	$cl = ($r << 16) + ($g << 8) + $b;
	return $cl;
}

function GenerateNoise($img, $width, $height, $st_mono, $st_clr, $is_back)
{
	if ($is_back)
	{
		for ($i = 0; $i < 8; $i++)
		{
			$cl = SubColors(0xFFFFFF, RandomColor($st_mono, $st_clr));
			$x1 = rand(0, $width/2-1);
			$y1 = rand(0, $height/2-1);
			$x2 = rand($width/2, $width-1);
			$y2 = rand($height/2, $height-1);
			ImageFilledRectangle($img, $x1, $y1, $x2, $y2, $cl);
		}

		for ($i = 0; $i < $width/16; $i++)
		{
			$cl = SubColors(0xFFFFFF, RandomColor($st_mono*4, $st_clr*8));
			ImageLine($img, $i*16, 0, $i*16, $height-1, $cl);
		}

		for ($i = 0; $i < $height/16; $i++)
		{
			$cl = SubColors(0xFFFFFF, RandomColor($st_mono*4, $st_clr*8));
			ImageLine($img, 0, $i*16, $width-1, $i*16, $cl);
		}

		for ($i = 0; $i < 16; $i++)
		{
			$y = rand(0, $height-1);
			$x1 = rand(0, $width/2-1);
			$x2 = rand($width/2, $width-1);
			$clr = RandomColor($st_mono, $st_clr);

			for ($j = $x1; $j <= $x2; $j++)
			{
				$cl = SubColors(ImageColorAt($img, $j, $y), $clr);
				ImageSetPixel($img, $j, $y, $cl);
			}	
		}

		for ($i = 0; $i < 32; $i++)
		{
			$x = rand(0, $width-1);
			$y1 = rand(0, $height/2-1);
			$y2 = rand($height/2, $height-1);
			$clr = RandomColor($st_mono, $st_clr);

			for ($j = $y1; $j <= $y2; $j++)
			{
				$cl = SubColors(ImageColorAt($img, $x, $j), $clr);
				ImageSetPixel($img, $x, $j, $cl);
			}
		}
	}
	else
	{
		for ($i = 0; $i < 16; $i++)
		{
			$y = rand(0, $height-1);
			$x1 = rand(0, $width/2-1);
			$x2 = rand($width/2, $width-1);
			$clr = RandomColor($st_mono*4, $st_clr*4);

			for ($j = $x1; $j <= $x2; $j++)
			{
				$cl = AddColors(ImageColorAt($img, $j, $y), $clr);
				ImageSetPixel($img, $j, $y, $cl);
			}	
		}

		for ($i = 0; $i < 32; $i++)
		{
			$x = rand(0, $width-1);
			$y1 = rand(0, $height/2-1);
			$y2 = rand($height/2, $height-1);
			$clr = RandomColor($st_mono*4, $st_clr*4);

			for ($j = $y1; $j <= $y2; $j++)
			{
				$cl = AddColors(ImageColorAt($img, $x, $j), $clr);
				ImageSetPixel($img, $x, $j, $cl);
			}
		}
	}
}

function GeneratePicture($str)
{
	$f_sz = 24;
	$f_name = "../fonts/verdana.ttf";

	$spc = 15;
	$bord = 20;

	$arr = ImageTTFBBox($f_sz, 0, $f_name, $str);
	$f_hgt = - $arr[5] - $arr[1];

	$f_wdt = 0;
	for ($i = 0; $i < strlen($str); $i++) {
		$arr = ImageTTFBBox($f_sz, 0, $f_name, $str{$i});
		$f_wdt += $arr[4] - $arr[0] + $spc;
	}

	$wdt = $f_wdt+$bord*2;
	$hgt = $f_hgt+$bord*2;

	$imgf = ImageCreateTrueColor($wdt, $hgt);
	ImageFilledRectangle($imgf, 0, 0, $wdt-1, $hgt-1, 0xFFFFFF);
	GenerateNoise($imgf, $wdt, $hgt, 30, 10, true);

	$x = $bord;
	$y = $f_hgt + $bord;

	for ($i = 0; $i < strlen($str); $i++)
	{
		$ang = rand(-20, 20);
		$sz = $f_sz + rand(-4, 4);

		ImageTTFText($imgf, $sz-rand(2, 4), $ang, $x+rand(-5, 5), $y+rand(-5, 5), AddColors(0x404040, RandomColor(0, 100)), $f_name, $str{$i});
		ImageTTFText($imgf, $sz, $ang, $x, $y, RandomColor(0, 128), $f_name, $str{$i});
		$arr = ImageTTFBBox($f_sz, 0, $f_name, $str{$i});
		$x += $arr[4] - $arr[0] + $spc;
	}

	GenerateNoise($imgf, $wdt, $hgt, 10, 10, false);
	return $imgf;
}

function GenerateCode($len)
{
	$gs = "2345689ABCDEFHIKLMNPQRSTUVWXYZ";

	$str = "";
	for ($i = 0; $i < $len; $i++) $str .= $gs{rand(0, strlen($gs)-1)};

	return (array("0"=>GeneratePicture($str), "1"=>$str));
}

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

session_start();
$arr = GenerateCode(8);
$_SESSION["secret_code"] = $arr[1];
header("Content-type: image/jpeg");
ImageJPEG($arr[0], '', 50);
ImageDestroy($arr[0]);

?>