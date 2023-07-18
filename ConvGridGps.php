<?php
//Source from https://gist.github.com/fronteer-kr/14d7f779d52a21ac2f16?permalink_comment_id=4175781#gistcomment-4175781

class ConvGridGps {
	const RE = 6371.00877; // 지구 반경(km)
	const GRID = 5.0; // 격자 간격(km)
	const SLAT1 = 30.0; // 투영 위도1(degree)
	const SLAT2 = 60.0; // 투영 위도2(degree)
	const OLON = 126.0; // 기준점 경도(degree)
	const OLAT = 38.0; // 기준점 위도(degree)
	const XO = 43; // 기준점 X좌표(GRID)
	const YO = 136; // 기1준점 Y좌표(GRID)

	const DEGRAD = M_PI / 180.0;
	const RADDEG = 180.0 / M_PI;

	const re = self::RE / self::GRID;
	const slat1 = self::SLAT1 * self::DEGRAD;
	const slat2 = self::SLAT2 * self::DEGRAD;
	const olon = self::OLON * self::DEGRAD;
	const olat = self::OLAT * self::DEGRAD;

	function sn(){
		$snTmp = tan(M_PI * 0.25 + self::slat2 * 0.5) / tan(M_PI * 0.25 + self::slat1 * 0.5);
		return log(cos(self::slat1) / cos(self::slat2)) / log($snTmp);
	}

	function sf(){
		$sfTmp = tan(M_PI * 0.25 + self::slat1 * 0.5);
		return pow($sfTmp, $this->sn()) * cos(self::slat1) / $this->sn();
	}

	function ro(){
		$roTmp = tan(M_PI * 0.25 + self::olat * 0.5);
		return self::re * $this->sf() / pow($roTmp, $this->sn());
	}

	function gridToGPS($v1, $v2) {
	  $rs['x'] = $v1;
	  $rs['y'] = $v2;
	  $xn = (int)($v1 - self::XO);
	  $yn = (int)($this->ro() - $v2 + self::YO);
	  $ra = sqrt($xn * $xn + $yn * $yn);
	  if ($this->sn() < 0.0) $ra = -$ra;
	  $alat = pow((self::re * $this->sf() / $ra), (1.0 / $this->sn()));
	  $alat = 2.0 * atan($alat) - M_PI * 0.5;

	  if (abs($xn) <= 0.0) {
		$theta = 0.0;
	  } else {
		if (abs($yn) <= 0.0) {
		  $theta = M_PI * 0.5;
		  if ($xn < 0.0) $theta = -$theta;
		} else
		  $theta = atan2($xn, $yn);
	  }
	  $alon = $theta / $this->sn() + self::olon;
	  $rs['lat'] = $alat * self::RADDEG;
	  $rs['lng'] = $alon * self::RADDEG;

	  return $rs;
	}

	function gpsToGRID($v1, $v2) {
	  $rs['lat'] = $v1;
	  $rs['lng'] = $v2;
	  $ra = tan(M_PI * 0.25 + ($v1) * self::DEGRAD * 0.5);
	  $ra = self::re * $this->sf() / pow($ra, $this->sn());
	  $theta = $v2 * self::DEGRAD - self::olon;
	  if ($theta > M_PI) $theta -= 2.0 * M_PI;
	  if ($theta < -M_PI) $theta += 2.0 * M_PI;
	  $theta *= $this->sn();
	  $rs['x'] = floor(($ra * sin($theta) + self::XO + 0.5));
	  $rs['y'] = floor(($this->ro() - $ra * cos($theta) + self::YO + 0.5));

	  return $rs;
	}
}
?>