<?php
/*运营推荐在线升级版联系QQ97908527*/
namespace App\Extensions;

class Form
{
	/**
     * 表单数据
     * @var array
     */
	protected $data = array();
	/**
     * 错误消息
     * @var string
     */
	protected $errorMsg = '';

	public function __construct($data = array())
	{
		if (empty($data)) {
			$data = array_merge((array) $_GET, (array) $_POST);
		}

		$this->data = $this->filterData($data);
	}

	protected function filterData($data)
	{
		if (is_array($data)) {
			foreach ($data as $k => $v) {
				$data[$k] = $this->filterData($v);
			}

			return $data;
		}
		else {
			if (get_magic_quotes_gpc()) {
				$data = stripslashes($data);
			}

			return $this->htmlEncode($data, 1);
		}
	}

	public function setData($data = array())
	{
		$this->data = $data;
	}

	public function getData($field, $type = 0)
	{
		if ($type) {
			$data = $field;
		}
		else {
			$data = $this->data[$field];
		}

		return $data;
	}

	public function getVal($name = NULL, $default = NULL)
	{
		if (empty($name)) {
			return $this->data;
		}

		if (!isset($this->data[$name])) {
			return $default;
		}

		return $this->data[$name];
	}

	public function isArray($field, $type = 0)
	{
		$data = $this->getData($field, $type);

		if (is_array($data)) {
			if (empty($data)) {
				return false;
			}
			else {
				return true;
			}
		}
		else {
			return false;
		}
	}

	public function isEmpty($field, $type = 0)
	{
		$data = $this->getData($field, $type);

		if (!empty($data)) {
			return true;
		}
		else {
			return false;
		}
	}

	public function isEmail($field, $type = 0)
	{
		return $this->isPreg('/^\\w+([-+.]\\w+)*@\\w+([-.]\\w+)*\\.\\w+([-.]\\w+)*$/', $field, $type);
	}

	public function isMobile($field, $type = 0)
	{
		return $this->isPreg('/^(1[3-9])\\d{9}$/', $field, $type);
	}

	public function isCreditNo($field, $type = 0)
	{
		return $this->isPreg('/^(\\d{6})(\\d{4})(\\d{2})(\\d{2})(\\d{3})([0-9]|[X|x])$/', $field, $type);
	}

	public function isUrl($field, $type = 0)
	{
		return $this->isPreg('/^http(s?):\\/\\/(?:[A-za-z0-9-]+\\.)+[A-za-z]{2,4}(:\\d+)?(?:[\\/\\?#][\\/=\\?%\\-&~`@[\\]\':+!\\.#\\w]*)?$/', $field, $type);
	}

	public function isCurrency($field, $type = 0)
	{
		return $this->isPreg('/^\\d+(\\.\\d+)?$/', $field, $type);
	}

	public function isNumber($field, $type = 0)
	{
		return $this->isPreg('/^\\d+$/', $field, $type);
	}

	public function isZip($field, $type = 0)
	{
		return $this->isPreg('/^\\d{6}$/', $field, $type);
	}

	public function isInteger($field, $type = 0)
	{
		return $this->isPreg('/^[-\\+]?\\d+$/', $field, $type);
	}

	public function isDouble($field, $type = 0)
	{
		return $this->isPreg('/^[-\\+]?\\d+$/', $field, $type);
	}

	public function isEnglish($field, $type = 0)
	{
		return $this->isPreg('/^[A-Za-z]+$/', $field);
	}

	public function isLength($field, $len, $type = 0)
	{
		$length = mb_strlen($this->data[$field], 'utf-8');

		if (strpos($len, ',')) {
			list($min, $max) = explode(',', $len);
			if ($min <= $length && $length <= $max) {
				return true;
			}
		}
		else {
			if ($length != $len) {
				return false;
			}

			return true;
		}
	}

	public function isPreg($rule, $field, $type = 0)
	{
		$data = $this->getData($field, $type);

		if (preg_match($rule, $data) === 1) {
			return true;
		}
		else {
			return false;
		}
	}

	public function htmlEncode($field, $type = 0)
	{
		$data = $this->getData($field, $type);
		return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
	}

	public function htmlDecode($field, $type = 0)
	{
		$data = $this->getData($field, $type);
		return html_entity_decode($data, ENT_QUOTES, 'UTF-8');
	}

	public function filterHtml($field, $type = 0)
	{
		$data = $this->getData($field, $type);
		$html = $this->htmlDecode($data, 1);
		return strip_tags($html);
	}

	public function filterUri($field, $type = 0)
	{
		$data = $this->getData($field, $type);
		$uri = $this->htmlDecode($data, 1);
		$allowed_protocols = array('http' => true, 'https' => true);

		do {
			$before = $uri;
			$colonpos = strpos($uri, ':');

			if (0 < $colonpos) {
				$protocol = substr($uri, 0, $colonpos);

				if (preg_match('![/?#]!', $protocol)) {
					break;
				}

				if (!isset($allowed_protocols[strtolower($protocol)])) {
					$uri = substr($uri, $colonpos + 1);
				}
			}
		} while ($before != $uri);

		return $uri;
	}

	public function filterXss($field, $allowedTags = array(), $allowedStyleProperties = array(), $type = 0)
	{
		static $xss;

		if (!isset($xss)) {
			$xss = new Xss();
		}

		$data = $this->getData($field, $type);
		$html = $this->htmlDecode($data, 1);
		return $xss->filter($html, $allowedTags, $allowedStyleProperties);
	}

	public function tokenGet($key)
	{
		static $encrypter;

		if (!isset($encrypter)) {
			$encrypter = new Encrypter($key);
		}

		return $encrypter->encrypt($encrypter->getId());
	}

	public function tokenVerify($str, $key)
	{
		static $encrypter;

		if (!isset($encrypter)) {
			$encrypter = new Encrypter($key);
		}

		$code = $encrypter->decrypt($str);

		if (!$encrypter->isId($str)) {
			return false;
		}

		return $code;
	}
}


?>