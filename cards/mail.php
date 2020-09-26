<?php
/* ---------------------------------------------------------------------
	Team Registration Administration System - Hockey (TRASH)
	Copyright (C) 2014  David McNerney

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see http://www.gnu.org/licenses/.
--------------------------------------------------------------------- */

// Fix for PHPMailer bug
function doNothingX() { }
if (!spl_autoload_functions()) {
	spl_autoload_register('doNothingX');
}
// END

require_once("../lib/PHPMailer/class.phpmailer.php");
require_once("../lib/Parsedown/Parsedown.php");

function mailInit() {
	$mail = new PHPMailer();

	$mail->IsSMTP();                                      // Set mailer to use SMTP
	$mail->Host = 'smtp.gmail.com';  					  // Specify main and backup server
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Port = 587;
	$mail->Username = AUTO_EMAIL;                 		  // SMTP username
	$mail->Password = AUTO_PASSWORD;                      // SMTP password
	$mail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted

	$mail->SetFrom('noreply-service@lha.secureweb.ie','LHA Registration Secretary (Automated)');
	$mail->AddReplyTo('lha.mens.regis+results@gmail.com');
	$mail->AddBCC('lha.mens.regis@gmail.com');               
	
	return $mail;
}

function sendClubMessage($club, $subject, $message, $attachments = null)
{
	$email = Club::getEmail($club);
	$pin = Club::getPINNumber($club);

	if ($email) {

		$username = $email;
		$x = createsecurekey('secretarylogin'.$username);
		$a = url("x=$x&u=$username", 'loginUC', 'admin');

		$header = "$club Matchcard PIN: $pin<br><a href='$a'>Registration</a>";

		//$msg = "<a href='".url("x=".createsecurekey('secretaryloginAvoca')."&u=Avoca","loginUC")."'>Login</a>\n
		//PIN Number: $pin";

		sendMail($email, $club, "$subject\n\n$header<hr>$message", null, $attachments);
	}
}

function sendMail($to, $toName, $message, $cc = null, $attachments = null)
{
	$tmp = explode("\n", $message, 3);
	$subject = $tmp[0];

	echo "<pre>".print_r($tmp[2], true)."</pre>";
	$message = Parsedown::instance()->parse($tmp[2]);
	
	if (!isset($to)) $to='';

	$message = str_replace("\n\n","\n<br>\n",$message);

	if (gethostname() == 'fir.securehost.ie' && (!isset($_REQUEST['test']))) {
		info("Sending Email: $to->$subject");
		debug("Body\n$message");

		$mail = mailInit();
	
		$mail->Subject = $subject;
		$mail->MsgHTML($message);
	
		// Only send the mail for real if the host is production
		if ($to != '') $mail->AddAddress($to, $toName);               

		if ($cc != null) {
			foreach ($cc as $recipient=>$name) {
				if ($recipient == $to) continue;
				$mail->AddCC($recipient, $name);
			}
		}

		if ($attachments != null) {
			foreach ($attachments as $filename=>$attachment) {
				$mail->AddStringAttachment($attachment, $filename);
			}
		}
		
		if(!$mail->send()) {
		  warning('Message could not be sent. '.$mail->ErrorInfo);
		} else {
			info("Message sent: '$to'");
		}
	} else {
		echo "MESSAGE NOT SENT:<br>To: $to/$toName<br>Subject: $subject<br><br>".$message."<br>***************<br>\n";
		if ($attachments != null) {
			foreach ($attachments as $name=>$attachment) {
				echo "Attached: {$name}<br>\n";
			}
		}
	}
}

function templatex($templateName, $vars) {
	$src = "";
	$delete = false;
	foreach (explode("\n", file_get_contents(dirname(__FILE__)."/img/templates/{$templateName}")) as $line) {
		if ($line == '}') {
			$delete = false;
			continue;
		}

		if (substr($line,0,2) == '{?') {
			if (!isset($vars[substr($line,2)])) {
				$delete = true;
			}

			continue;
		}

		if ($delete) continue;

		$src .= $line."\n";
	}

	if ($vars) {
		foreach ($vars as $name=>$value) {
			if (gettype($value) == 'array') {
				$a = stripos($src, "{".$name.":");
				if ($a !== FALSE) {
					$b = strpos($src, "}", $a);
					$keys = explode(",", substr($src, $a + strlen($name) + 2, $b - $a - 2 - strlen($name)));
					$result = "<table><tr>";
					foreach ($keys as $key) {
						$result .= "<th width='300px' align='left' style='border-bottom:1px solid black'>".trim($key)."</th>";
					}
					$result .= "</tr>";
					foreach ($value as $item) {
						$result .= "<tr>";
						foreach ($keys as $key) {
							$result .= "<td>".$item[strtolower(trim($key))]."</td>";
						}
						$result .= "</tr>";
					}
					$result .= "</table>";
					$src = substr_replace($src, $result, $a, $b - $a + 1);
					continue;
				}
				$tmp = "";
				foreach ($value as $item=>$ref) {
					if (is_numeric($item)) {
						$tmp .= "* ".print_r($ref, true)."\n";	
					} else if ($ref != "") {
						$tmp .= "* [".print_r($item, true)."]($ref)\n";	
					} else {
						$tmp .= "* ".print_r($item, true)."\n";	
					}
				}
				$value = $tmp;
			}

			$src = str_replace("{{$name}}", print_r($value, true), $src);
		}
	}

	//echo "<pre>".$src."</pre>";

	return preg_replace("/{.*?}/", "", $src);
}


function qpush2($str) {
	touch("../queue");
	$contents = file_get_contents("../queue");
	if (!$contents) $contents = array();
	else $contents = explode("\n", $contents);
	$contents[] = $str;
	file_put_contents("../queue", join("\n", $contents));
	echo "<pre>Pushed: $str</pre>";
}
?>
