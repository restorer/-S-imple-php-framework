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
		$email->from = $this->santize_string($email->from);
		$email->to = $this->santize_string($email->to);
		$email->subject = $this->santize_string($email->subject);

		$this->prepare_images($email);
		$this->send_raw($email);
	}

	function santize_string($str)
	{
		return str_replace("\n", '', str_replace("\r", '', trim($str)));
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

	function get_smtp_response($sock)
	{
		$res = '';

		while ($str = @fgets($sock, 512))
		{
			$res .= $str;

			// From SMTP class by Chris Ryan
			// if the 4th character is a space then we are done reading
			// so just break the loop
			if (substr($str, 3, 1) == ' ') break;
		}

		if (DEBUG) dwrite_msg('SMTP response', $res);
		return $res;
	}

	function get_smtp_response_code($sock)
	{
		$resp = $this->get_smtp_response($sock);
		return substr($resp, 0, 3);
	}

	function smtp_puts($sock, $data)
	{
		if (DEBUG) dwrite_msg('SMTP request', $data);
		fputs($sock, $data);
	}

	function send_smtp_data($sock, $data, $is_headers)
	{
		$arr = explode("\n", str_replace("\r", "\n", str_replace("\r\n","\n",$data)));

		foreach ($arr as $line)
		{
			$send = array();

			while (strlen($line) > 998)
			{
				$pos = strrpos(substr($line, 0, 998), ' ');
		        if (!$pos) $pos = 997;

		        $send[] = substr($line, 0, $pos);
		        $line = substr($line, $pos + 1);

				if ($is_headers) $line = "\t" . $line;
			}

			$send[] = $line;

			foreach ($send as $str)
			{
				if (strlen($str) && substr($str, 0, 1)=='.') $str = '.' . $str;
		        $this->smtp_puts($sock, $str . "\r\n");
			}
		}
	}

	function send_mail_smtp($from, $to, $subject, $hdr, $body)
	{
		$host = (conf_get('mail.smtp.ssl') ? 'ssl://' : '') . conf_get('mail.smtp.host');
		$sock = fsockopen($host, conf_get('mail.smtp.port'), $errno, $errstr, conf_get('mail.smtp.timeout'));
		if (!$sock) error("SMTP: $errstr ($errno)");

		$this->get_smtp_response($sock);	// get dummy response
		$hostname = _SERVER('SERVER_NAME', 'localhost');

		$this->smtp_puts($sock, 'EHLO ' . $hostname . "\r\n");

		if ($this->get_smtp_response_code($sock) != 250)
		{
			$this->smtp_puts($sock, 'HELO ' . $hostname . "\r\n");
			if ($this->get_smtp_response_code($sock) != 250) error('SMTP: error while sending HELO request');
		}

		if (strlen(conf_get('mail.smtp.user')))
		{
	    	$this->smtp_puts($sock, "AUTH LOGIN\r\n");
			if ($this->get_smtp_response_code($sock) != 334) error('SMTP: error while senging AUTH LOGIN request');

		    $this->smtp_puts($sock, base64_encode(conf_get('mail.smtp.user')) . "\r\n");
			if ($this->get_smtp_response_code($sock) != 334) error('SMTP: error while senging AUTH LOGIN request (username not accepted)');

		    $this->smtp_puts($sock, base64_encode(conf_get('mail.smtp.pass')) . "\r\n");
			if ($this->get_smtp_response_code($sock) != 235) error('SMTP: error while senging AUTH LOGIN request (invalid password)');
    	}

	    $this->smtp_puts($sock, 'MAIL FROM: <' . $from . ">\r\n");
		if ($this->get_smtp_response_code($sock) != 250) error('SMTP: error while senging MAIL FROM request');

	    $this->smtp_puts($sock,'RCPT TO: <' . $to . ">\r\n");
	    $code = $this->get_smtp_response_code($sock);
		if ($code!=250 && $code!=251) error('SMTP: error while senging RCPT TO request');

	    $this->smtp_puts($sock, "DATA\r\n");
		if ($this->get_smtp_response_code($sock) != 354) error('SMTP: error while senging DATA request');

		$hdr .= 'Subject: ' . $subject . "\r\n";
		$hdr .= "\r\n";

		$this->send_smtp_data($sock, $hdr, true);
		$this->send_smtp_data($sock, $body, false);
		$this->smtp_puts($sock, "\r\n.\r\n");

		if ($this->get_smtp_response_code($sock) != 250) error('SMTP: error while senging DATA request (data not accepted)');

	    $this->smtp_puts($sock, "QUIT\r\n");
		if ($this->get_smtp_response_code($sock) != 221) error('SMTP: error while senging QUIT request');

		fclose($sock);
	}

	function send_raw(&$email)
	{
		$hdr  = 'From: ' . $email->from . "\r\n";
		$hdr .= 'Reply-To: ' . $email->from . "\r\n";
		$hdr .= "MIME-Version: 1.0\r\n";

		foreach ($email->headers as $key=>$val) $hdr .= $this->santize_string($key) . ': ' . $this->santize_string($val) . "\r\n";

		if (!count($email->attachments))
		{
			$hdr .= 'Content-Type: text/html; charset=' . $email->charset . "\r\n";
			$body = $email->body;
		}
		else
		{
			$str = $email->body;
			foreach ($email->attachments as $att) $str .= $att->content;
			$boundary = $this->generate_boundary($str);

			$hdr .= 'Content-Type: multipart/mixed; boundary="' . $boundary.'"' . "\r\n";
			$attachs = '';

			foreach ($email->attachments as $att)
			{
				if (strpos($att->mime_type, '/') === false) error('Invalid MIME type of the attachment.');

				$attachs .= "--$boundary\r\n";
				$attachs .= 'Content-Type: ' . $att->mime_type;
				if ($att->content_name != '') $attachs .= '; name="' . $att->content_name . '"';
				$attachs .= "\r\n";
				$attachs .= "Content-Transfer-Encoding: base64\r\n";
				if ($att->file_name != '') $attachs .= 'Content-Disposition: attachment; filename="' . $att->file_name . '"' . "\r\n";
				if ($att->content_id != '') $attachs .= 'Content-ID: <' . $att->content_id . ">\r\n";
				$attachs .= "\r\n";
				$attachs .= chunk_split(base64_encode($att->content)) . "\r\n";
			}

			$body = "--$boundary\r\n";
			$body .= "Content-type: text/html; charset=" . $email->charset . "\r\n\r\n";
			$body .= $email->body . "\r\n" . $attachs;
			$body .= "--$boundary--\r\n";
		}

		if (DEBUG)
		{
			$msg = '<b>SendMail to "' . $email->to  .'" with subject "' . $email->subject . '"</b>';
			if (!conf('mail.send')) $msg .= ' <span style="color:red;">(Sending email is disabled)</span>';

			dwrite($msg);
			dwrite_msg('Headers', $hdr);
			dwrite_msg('Body', $body);
		}

		if (conf('mail.send'))
		{
			if (conf('mail.smtp.enable'))
			{
				$this->send_mail_smtp($email->from, $email->to, $email->subject, $hdr, $body);
			}
			else
			{
				ini_set('sendmail_from', $email->from);
				mail($email->to, $email->subject, $body, $hdr);
			}
		}
	}
}
##
# .end
##
