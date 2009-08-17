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
	public $mime_type = '';

	##
	# [$content] Attachment content
	##
	public $content = '';

	##
	# [$file_name] Attachment name
	##
	public $file_name = '';

	##
	# [$content_name] Content name (used when embedding images into email)
	##
	public $content_name = '';

	##
	# [$content_id] Automatically generated field
	##
	public $content_id = '';
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
	# [$from_email] Email from
	##
	public $from_email = '';

	##
	# [$from_name] Email from name
	##
	public $from_name = '';

	##
	# [$to] Email to
	##
	public $to = '';

	##
	# [$subject] Email subject
	##
	public $subject = '';

	##
	# [$body] Email body
	##
	public $body = '';

	##
	# [$headers] Email headers. Usually you don't need to set this field manually
	##
	public $headers = array();	// '

	##
	# [$attachments] Email attachments
	##
	public $attachments = array();

	##
	# [$charset] Email charset. **utf-8** is used by default.
	##
	public $charset = 'UTF-8';

	##
	# [$embed_images]
	##
	public $embed_images = false;

	##
	# = public string send()
	# Send email. Returns empty string for success, or error string if error occurred
	##
	public function send()
	{
		$email = clone($this);

		$email->to = $this->santize_string($email->to);
		$email->from_email = $this->santize_string($email->from_email);
		$email->from_name = $this->santize_string($email->from_name);
		$email->subject = $this->santize_string($email->subject);

		if ($email->embed_images) $email->prepare_images();

		return $email->send_raw();
	}

	protected function santize_string($str)
	{
		return str_replace("\n", '', str_replace("\r", '', trim($str)));
	}

	protected function generate_boundary($str='')
	{
		do {
			$boundary = ('----------'.strtoupper(substr(md5(get_microtime()), 0, 15)));
		} while (strpos($str, $boundary) !== false);

		return $boundary;
	}

	protected function generate_content_id_prefix()
	{
		$cid = strtoupper(md5(get_microtime()));
		$cid = substr($cid, 0, 8).'.'.substr($cid, 8, 8).'.'.substr($cid, 16, 8).'.'.substr($cid, 24, 8);
		return $cid;
	}

	protected function prepare_images()
	{
		$chk = strtolower(conf('domain'));
		$root = strtolower(conf('http.root'));

		preg_match_all("@<\s*img[^>]*src=['\"]([^'\">]*)['\"][^>]*>@i", $this->body, $matches, PREG_SET_ORDER);
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

			$this->body = preg_replace("@(<\s*img[^>]*src=['\"])(".preg_quote($k,'@').")(['\"][^>]*>)@i", '${1}cid:'.$cid.'${3}', $this->body);
			$ext = substr($v['n'], strrpos($v['n'], '.') + 1);

			$att = new SEmailAttachment();
			$att->mime_type = array_key_exists(strtolower($ext), $types) ? $types[strtolower($ext)] : 'application/octet-stream';
			$att->content = $v['c'];
			$att->content_name = $v['n'];
			$att->content_id = $cid;

			$this->attachments[] = $att;
		}
	}

	protected function get_smtp_response($sock)
	{
		$res = '';

		while ($str = @fgets($sock, 512))
		{
			$res .= $str;

			// begin code from SMTP class by Chris Ryan
			// if the 4th character is a space then we are done reading
			// so just break the loop
			if (substr($str, 3, 1) == ' ') break;
			// end code from SMTP class by Chris Ryan
		}

		if (DEBUG) dwrite_msg('SMTP response', $res);
		return $res;
	}

	protected function get_smtp_response_code($sock)
	{
		$resp = $this->get_smtp_response($sock);
		return substr($resp, 0, 3);
	}

	protected function smtp_puts($sock, $data)
	{
		if (DEBUG) dwrite_msg('SMTP request', $data);
		fputs($sock, $data);
	}

	protected function send_smtp_data($sock, $data, $is_headers)
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

	protected function send_mail_smtp($from_email, $from, $to, $subject, $hdr, $body)
	{
		$host = (conf_get('mail.smtp.ssl') ? 'ssl://' : '') . conf_get('mail.smtp.host');
		$sock = fsockopen($host, conf_get('mail.smtp.port'), $errno, $errstr, conf_get('mail.smtp.timeout'));
		if (!$sock) return "SMTP: $errstr ($errno)";

		$this->get_smtp_response($sock);	// get dummy response
		$hostname = _SERVER('SERVER_NAME', 'localhost');

		$this->smtp_puts($sock, 'EHLO ' . $hostname . "\r\n");

		if ($this->get_smtp_response_code($sock) != 250)
		{
			$this->smtp_puts($sock, 'HELO ' . $hostname . "\r\n");

			if ($this->get_smtp_response_code($sock) != 250)
			{
				fclose($sock);
				return 'SMTP: error while sending HELO request';
			}
		}

		if (strlen(conf_get('mail.smtp.user')))
		{
			$this->smtp_puts($sock, "AUTH LOGIN\r\n");

			if ($this->get_smtp_response_code($sock) != 334)
			{
				fclose($sock);
				return 'SMTP: error while senging AUTH LOGIN request';
			}

			$this->smtp_puts($sock, base64_encode(conf_get('mail.smtp.user')) . "\r\n");

			if ($this->get_smtp_response_code($sock) != 334)
			{
				fclose($sock);
				return 'SMTP: error while senging AUTH LOGIN request (username not accepted)';
			}

			$this->smtp_puts($sock, base64_encode(conf_get('mail.smtp.pass')) . "\r\n");

			if ($this->get_smtp_response_code($sock) != 235)
			{
				fclose($sock);
				return 'SMTP: error while senging AUTH LOGIN request (invalid password)';
			}
		}

		$this->smtp_puts($sock, 'MAIL FROM: <' . $from_email . ">\r\n");

		if ($this->get_smtp_response_code($sock) != 250)
		{
			fclose($sock);
			return 'SMTP: error while senging MAIL FROM request';
		}

		$this->smtp_puts($sock,'RCPT TO: <' . $to . ">\r\n");
		$code = $this->get_smtp_response_code($sock);

		if ($code!=250 && $code!=251)
		{
			fclose($sock);
			return 'SMTP: error while senging RCPT TO request';
		}

		$this->smtp_puts($sock, "DATA\r\n");

		if ($this->get_smtp_response_code($sock) != 354)
		{
			fclose($sock);
			return 'SMTP: error while senging DATA request';
		}

		$hdr .= 'Subject: ' . $subject . "\r\n";
		$hdr .= "\r\n";

		$this->send_smtp_data($sock, $hdr, true);
		$this->send_smtp_data($sock, $body, false);
		$this->smtp_puts($sock, "\r\n.\r\n");

		if ($this->get_smtp_response_code($sock) != 250)
		{
			fclose($sock);
			return 'SMTP: error while senging DATA request (data not accepted)';
		}

		$this->smtp_puts($sock, "QUIT\r\n");

		if ($this->get_smtp_response_code($sock) != 221)
		{
			fclose($sock);
			return 'SMTP: error while senging QUIT request';
		}

		fclose($sock);
		return '';
	}

	protected function send_mail_mail($from, $to, $subject, $hdr, $body)
	{
		if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN'))
		{
			$old_sendmail_form = ini_get('sendmail_from');
			ini_set('sendmail_from', $from);
		}
		else
		{
			$hdr = str_replace("\r\n", "\n", $hdr);
		}

		$body = str_replace("\n.", "\n..", $body);
		$res = @mail($to, $subject, $body, $hdr);

		if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN'))
		{
			ini_set('sendmail_from', $old_sendmail_form);
		}

		return ($res ? '' : 'MAIL: sending failed');
	}

	protected function send_mail_sendmail($from, $to, $subject, $hdr, $body)
	{
		$cmd = escapeshellcmd(conf('mail.sendmail.path')) . ' -t -i -f ' . escapeshellarg($from);
		// $cmd = escapeshellcmd(conf('mail.sendmail.path')) . ' -t -i';

		$h = popen($cmd, 'w');
		if (!$h) return "SENDMAIL: can't open pipe ($cmd)";

		fputs($h, $hdr);
		fputs($h, $body);

		$stat = pclose($h);

		if (function_exists('pcntl_wifexited'))
		{
			if (!pcntl_wifexited($stat)) return 'SENDMAIL: abnormal sendmail process terminate';
			$res = pcntl_wexitstatus($stat);
		}
		else
		{
			if (version_compare(phpversion(), '4.2.3') == -1) $res = ($stat >> 8) & 0xFF;
			else $res = $stat;
		}

		if ($res) return "SENDMAIL: error occurred (cmd: $cmd) (code: $res)";

		return '';
	}

	protected function make_from()
	{
		return (strlen($this->from_name) ? ($this->from_name . ' <' . $this->from_email . '>') : $this->from_email);
	}

	protected function validate_email($email_str)
	{
		return preg_match("/^[-+\\.0-9=a-z_]+@([-0-9a-z]+\\.)+([0-9a-z]){2,4}$/i", $email_str);
	}

	protected function send_raw()
	{
		if (!$this->validate_email($this->from_email)) return 'Invalid "From" email';
		if (!$this->validate_email($this->to)) return 'Invalid "To" email';

		$from = $this->make_from();

		$hdr  = 'From: ' . $from . "\r\n";
		$hdr .= 'Return-Path: ' . $this->from_email . "\r\n";
		$hdr .= 'Errors-To: ' . $this->from_email . "\r\n";
		$hdr .= "MIME-Version: 1.0\r\n";

		foreach ($this->headers as $key=>$val) $hdr .= $this->santize_string($key) . ': ' . $this->santize_string($val) . "\r\n";

		if (!count($this->attachments))
		{
			$hdr .= 'Content-Type: text/html; charset=' . $this->charset . "\r\n";
			$body = $this->body;
		}
		else
		{
			$str = $this->body;
			foreach ($this->attachments as $att) $str .= $att->content;
			$boundary = $this->generate_boundary($str);

			$hdr .= 'Content-Type: multipart/mixed; boundary="' . $boundary.'"' . "\r\n";
			$attachs = '';

			foreach ($this->attachments as $att)
			{
				if (strpos($att->mime_type, '/') === false) throw new Exception('Invalid MIME type of the attachment.');

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
			$body .= "Content-type: text/html; charset=" . $this->charset . "\r\n\r\n";
			$body .= $this->body . "\r\n" . $attachs;
			$body .= "--$boundary--\r\n";
		}

		if (DEBUG)
		{
			$msg = "**SendMail to \"{$this->to}\" with subject \"{$this->subject}\"**";
			if (!conf('mail.send')) $msg .= ' !!(Sending email is disabled)!!';

			dwrite($msg);
			dwrite_msg('Headers', $hdr);
			dwrite_msg('Body', $body);
		}

		if (conf('mail.send'))
		{
			switch (conf('mail.type'))
			{
				case 'mail':
					return $this->send_mail_mail($from, $this->to, $this->subject, $hdr, $body);

				case 'smtp':
					return $this->send_mail_smtp($this->from_email, $from, $this->to, $this->subject, $hdr, $body);

				case 'sendmail':
					return $this->send_mail_sendmail($from, $this->to, $this->subject, $hdr, $body);

				default:
					throw new Exception('Unknown mailer type (' . conf('mail.type') . ')');
			}
		}

		return '';
	}
}
##
# .end
##
