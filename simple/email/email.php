<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Emails
 */

##
# .begin
# = class SEmailAttachment
##
class SEmailAttachment
{
	##
	# [$mime_type] Mime-type
	##
	var $mime_type = '';

	##
	# [$content] Attachment content
	##
	var $content = '';

	##
	# [$file_name] Attachment name
	##
	var $file_name = '';

	##
	# [$content_name] Content name (used when embedding images into email)
	##
	var $content_name = '';

	##
	# [$content_id] Automatically generated field
	##
	var $content_id = '';
}
##
# .end
##

##
# .begin
# = class SEmail
##
class SEmail
{
	##
	# [$from] Email from
	##
	var $from = '';

	##
	# [$to] Email to
	##
	var $to = '';

	##
	# [$subject] Email subject
	##
	var $subject = '';

	##
	# [$body] Email body
	##
	var $body = '';

	##
	# [$headers] Email headers. Usually you don't need to set this field manually
	##
	var $headers = array();

	##
	# [$attachments] Email attachments
	##
	var $attachments = array();

	##
	# [$charset] Email charset. **utf-8** is used by default.
	##
	var $charset = 'uft-8';

	##
	# = void send()
	# Send email
	##
	function send()
	{
		$email = clone($this);
		$this->prepare_images($email);
		$this->send_raw($email);
	}

	function generate_boundary($str='')
	{
		do {
			$boundary = ('----------'.strtoupper(substr(md5(get_microtime()), 0, 15)));
		} while (strpos($str, $boundary) !== false);

		return $boundary;
	}

	function generate_content_id_prefix()
	{
		$cid = strtoupper(md5(get_microtime()));
		$cid = substr($cid, 0, 8).'.'.substr($cid, 8, 8).'.'.substr($cid, 16, 8).'.'.substr($cid, 24, 8);
		return $cid;
	}

	function prepare_images(&$email)
	{
		$chk = strtolower(conf('domain'));
		$root = strtolower(conf('http.root'));

		preg_match_all("@<\s*img[^>]*src=['\"]([^'\">]*)['\"][^>]*>@i", $email->body, $matches, PREG_SET_ORDER);
		$imgs = array();

		foreach ($matches as $m)
		{
			$arr = parse_url($m[1]);

			if (array_key_exists('path', $arr))
			{
				$host = array_key_exists('host', $arr) ? $arr['host'] : '';

				if ($host!='' && array_key_exists('port', $arr)) $host .= ':'.$port;

				if ($host=='' || strtolower($host)==$chk)
				{
					$path = $arr['path'];

					if (substr($path, 0, strlen($root)))
					{
						$path = substr($path, strlen($root));

						if (!array_key_exists($m[1], $imgs) && file_exists(BASE_PATH.$path)) {
							$imgs[$m[1]] = array('n'=>basename($path), 'c'=>file_get_contents(BASE_PATH.$path));
						}
					}
				}
			}
		}

		$cnt = 1;
		$cid_pref = $this->generate_content_id_prefix();

		$types = array(
				'gif'  => 'image/gif',
				'jpg'  => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'jpe'  => 'image/jpeg',
				'bmp'  => 'image/bmp',
				'png'  => 'image/png',
				'tif'  => 'image/tiff',
				'tiff' => 'image/tiff',
				'swf'  => 'application/x-shockwave-flash'
			);

		foreach ($imgs as $k=>$v)
		{
			$cid = $cid_pref.'_'.$cnt;
			$cnt++;

			$email->body = preg_replace("@(<\s*img[^>]*src=['\"])(".preg_quote($k,'@').")(['\"][^>]*>)@i", '${1}cid:'.$cid.'${3}', $email->body);
			$ext = substr($v['n'], strrpos($v['n'], '.') + 1);

			$att = new SEmailAttachment();
			$att->mime_type = array_key_exists(strtolower($ext), $types) ? $types[strtolower($ext)] : 'application/octet-stream';
			$att->content = $v['c'];
			$att->content_name = $v['n'];
			$att->content_id = $cid;

			$email->attachments[] = $att;
		}
	}

	function send_raw(&$email)
	{
		$hdr = 'From: '.$email->from."\n";
		foreach ($email->headers as $val) $hdr .= $val."\n";
		$hdr .= "MIME-Version: 1.0\n";

		if (!count($email->attachments)) {
			$hdr .= 'Content-Type: text/html; charset='.$email->charset."\n";
			$body = $email->body;
		}
		else
		{
			$str = $email->body;
			foreach ($email->attachments as $att) $str .= $att->content;
			$boundary = $this->generate_boundary($str);

			$hdr .= 'Content-Type: multipart/mixed; boundary="'.$boundary.'"'."\n";
			$attachs = '';

			foreach ($email->attachments as $att)
			{
				if (strpos($att->mime_type, '/') === false) error('Invalid MIME type of the attachment.');

				$attachs .= "--$boundary\n";
				$attachs .= 'Content-Type: '.$att->mime_type;
				if ($att->content_name != '') $attachs .= '; name="'.$att->content_name.'"';
				$attachs .= "\n";
				$attachs .= "Content-Transfer-Encoding: base64\n";
				if ($att->file_name != '') $attachs .= 'Content-Disposition: attachment; filename="'.$att->file_name.'"'."\n";
				if ($att->content_id != '') $attachs .= 'Content-ID: <'.$att->content_id.">\n";
				$attachs .= "\n";
				$attachs .= chunk_split(base64_encode($att->content)) . "\n";
			}

			$body = "--$boundary\n";
			$body .= "Content-type: text/html; charset=".$email->charset."\n\n";
			$body .= $email->body . "\n" . $attachs;
			$body .= "--$boundary--\n";
		}

		if (DEBUG)
		{
			$msg = '<b>SendMail to "'.$email->to.'" with subject "'.$email->subject.'"</b>';
			if (!conf("mail.send")) $msg .= ' <span style="color:red;">(Sending email is disabled)</span>';

			dwrite($msg, MSG_NORMAL);
			dwrite_msg('Headers', $hdr);
			dwrite_msg('Body', $body);
		}

		if (conf("mail.send")) mail($email->to, $email->subject, $body, $hdr);
	}
}
##
# .end
##

?>