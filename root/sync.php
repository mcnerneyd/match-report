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

echo "<pre>";

chdir('cards');
require_once('stub.php');
chdir('..');

require_once('lib.php');
require_once('model/controller.php');


function memdump($mark) {
	if (isset($_REQUEST['memory'])) {
		echo "M:[".$mark."]=".memory_get_usage(true)."/".memory_get_usage(false).".<br>";
	}
}

if (isset($_REQUEST['push'])) {
	qpush($_REQUEST['push']);
	return;
}

memdump(1);

$message = qpull();

memdump(2);

if ($message != null) {

	if ($message == 'nothing') {
		qpush('nothing@'.date('Y-m-d', strtotime('tomorrow')).' 00:05');
		return;
	}

	if (strpos($message, 'http://cards.leinsterhockey.ie/index.php') == 0) {
		redirect($message);
		return;
	}

	$message = explode('-', $message);
	if ($message[0] == 'weekly_club') {
		if (count($message) == 1) {
			$controller = Controller::getInstance();
			foreach ($controller->getConfig('club') as $club=>$value) {
				qpush('weekly_club-'.$club);
			}
		}
		if (count($message) == 2) {
			redirect("http://lha.secureweb.ie/report.php?name=weekly_club&club=$message[1]");
			return;
		}
	}

	/*
	if ($message[0] == 'force') {
		if (count($message) == 1) {
			$controller = Controller::getInstance();
			foreach ($controller->getConfig('club') as $club=>$value) {
				qpush('force-'.$club);
			}
		}
		if (count($message) == 2) {
			$controller = Controller::getInstance();
			$controller->forceList($message[1]);
			return;
		}
	}

	if ($message[0] == 'scrape') {
		echo "http://lha.secureweb.ie/scrape.php?section=".urlencode(SITE_NAME);
		file_get_contents("http://lha.secureweb.ie/scrape.php?section=".urlencode(SITE_NAME));
		return;
	}*/

	echo 'Message:'.print_r($message, true);

	return;
}

function processMail($lines, $debug = 0) {
	memdump("p1");
    $result = array();
    $body = array();
    $parts = null;
    
    $stage = 0;
    $inBody = false;
    $beforeBody = true;
    $boundary = null;
    
    if ($debug) echo "Process Mail\n";
    
    $tmpLines = array();
    
	memdump("p5");
	$inBody = false;
	$lastKey = "";
    
    foreach ($lines as $line) {
		if ($debug) echo "|".rtrim($line).chr(182)."\n";

        if (!$inBody) {
			$tline = trim($line);
            if (strlen($tline) == 0) {
				if ($debug) { echo "----- Break -----\n"; print_r($result); }
                $inBody = true;
                $matches = array();
                if (isset($result['content-type']) and preg_match("/^multipart.*; ?boundary=(.*)/i", $result['content-type'], $matches)) {
                    $boundary = trim($matches[1]);
                    if (strpos($boundary, '"') === 0) {
						$boundary = substr($boundary, 1, strlen($boundary) - 2);
					}
                    if (strpos($boundary, '"') > 0) {
						$boundary = substr($boundary, 0, strpos($boundary, '"'));
					}
                    if ($debug) echo "Boundary: {$boundary}$\n";
                }
                continue;
            }
        
			if (ltrim($line) != $line and $lastKey != "") {
				$result[$lastKey] .= $tline;
				if ($debug) echo "$lastKey+=$tline\n";
				continue;
			}

            $nvp = explode(":", $tline, 2);
            if (count($nvp) != 2) continue;

			$lastKey = strtolower(trim($nvp[0]));
            
			if ($debug) echo "   ".$lastKey."=".$nvp[1]."\n";
            $result[$lastKey] = trim($nvp[1]);
        } else {
            if ($boundary != null) {
                if (trim($line) == "--$boundary") {
                    if (isset($parts)) {
						if ($debug) echo "Next Boundary\n";
                        $parts[] = processMail($body, $debug);
                        $body = array();
                    } else {
						if ($debug) echo "First Boundary\n";
                        $parts = array();
                        $body = array();
                    }
                    continue;
                }
                
                if (trim($line) == "--$boundary--") {
					if ($debug) echo "Last Boundary\n";
                    $parts[] = processMail($body, $debug);
                    break;
                }
            }
            $body[]=$line;
        }
    }

    if (isset($parts)) $result['children'] = $parts;
    else {
        if (array_key_exists('Content-Transfer-Encoding', $result)) { echo "AKE".$result['Content-Transfer-Encoding']; }

        if (array_key_exists('content-transfer-encoding', $result) && $result['content-transfer-encoding'] == 'quoted-printable') {
						echo "Quoted Printable\n";
            $body = quoted_printable_decode(join("", $body));
            unset($result['content-transfer-encoding']);
        } else if (array_key_exists('content-transfer-encoding', $result) && $result['content-transfer-encoding'] == 'base64') {
						echo "Base64\n";
            $body = base64_decode(join("", $body));
            unset($result['content-transfer-encoding']);
        } else {
			$body = join("\n", $body);
		}
    
        $result['content'] = $body;
		if ($debug) echo " --- Content: ".substr($body,0,50)."...\n";
    }
    
	memdump("p2");
    return $result;
}

function parseAddresses($str) {
	$emails = array();

	if(preg_match_all('/\s*"?([^><"]+)"?\s*((?:<[^><,]+>)?)\s*/', $str, $matches, PREG_SET_ORDER) > 0)
	{
		foreach($matches as $m)
		{
			if(! empty($m[2]))
			{
				$emails[trim($m[2], '<>')] = $m[1];
			}
			else
			{
				$emails[$m[1]] = '';
			}
		}
	}
	
	return array_keys($emails);
}

function getAllImages($msg) {
echo "Stripping\n";
	$result = array();

	if (isset($msg['content-type']) and strpos($msg['content-type'], 'image') === 0) {
		echo "Image:".$msg['content-type']."\n";
		$type = explode(";", $msg['content-type'], 2);
		$type = $type[0];
		$result[] = array('type'=>$type, 'data'=>$msg['content']);
	}
	
	if (isset($msg['content-disposition'])) {
		$matches = array();
		echo "CD:".$msg['content-disposition']."\nX".$msg['content-type']."X";
		if (trim($msg['content-disposition']) == 'attachment;') {
			if ($msg['content-type'] == 'text/comma-separated-values;') {
				$result[] = array('type'=>'csv', 'data'=>$msg['content'], 'filename'=>'unnamed.csv');
				echo "++ AttachmentX";
			} else if (preg_match('/text.csv; .*name=\"?(.*\.csv)\"?/', $msg['content-type'], $matches)) {
				$result[] = array('type'=>'csv', 'data'=>$msg['content'], 'filename'=>$matches[1]);
				echo "++ Attachment0: ".$matches[1];
			} else if (preg_match('/(.*\.csv)/', $msg['content-description'], $matches)) {
				$result[] = array('type'=>'csv', 'data'=>$msg['content'], 'filename'=>$matches[1]);
				echo "++ Attachment1: ".$matches[1];
			}

			if (preg_match('/.*name=\"?(.*\.xlsx?)\"?/', $msg['content-type'], $matches)) {
				$result[] = array('type'=>'xls', 'data'=>$msg['content'], 'filename'=>$matches[1]);
				echo "++ Attachment0: ".$matches[1];
			} else if (preg_match('/(.*\.xlsx?)/', $msg['content-description'], $matches)) {
				$result[] = array('type'=>'xls', 'data'=>$msg['content'], 'filename'=>$matches[1]);
				echo "++ Attachment1: ".$matches[1];
			}
		} else 
		if (preg_match('/attachment; ?filename=\"?(.*\.csv)\"?/', $msg['content-disposition'], $matches)) {
			echo "++ Attachment2: ".$matches[1];
			$result[] = array('type'=>'csv', 'data'=>$msg['content'], 'filename'=>$matches[1]);
		} else 
		if (preg_match('/attachment; ?filename=\"?(.*\.xlsx?)\"?/', $msg['content-disposition'], $matches)) {
			echo "++ Attachment2: ".$matches[1];
			$result[] = array('type'=>'xls', 'data'=>$msg['content'], 'filename'=>$matches[1]);
		}
	}

	if (isset($msg['children'])) {
		echo " Children\n";
		foreach ($msg['children'] as $child) {
			$result = array_merge($result, getAllImages($child));
		}
	}
	
	return $result;
}

function parseMail($msg, $debug) {
	memdump("pm0");
	$tmpMail = processMail($msg, $debug);
	if ($debug) {
		echo "Mail result: \n".print_r($tmpMail, true)."\nEOR";
	}

	if (!(isset($tmpMail['subject']) and isset($tmpMail['from']) and isset($tmpMail['date']))) return false;

	$result['subject'] = $tmpMail['subject'];
	$result['from'] = parseAddresses($tmpMail['from']);
	if (count($result['from']) == 0) return false;
	$result['from'] = $result['from'][0];
	$result['attached'] = getAllImages($tmpMail);
	$result['date'] = date('Y-m-d', strtotime($tmpMail['date'])); 
	print_r($result);

	return $result;
}


function dequeueMail() {
	$debug = (isset($_REQUEST['debug']) && $_REQUEST['debug']>5);
	if ($debug) echo "DEBUG Dequeue<br>";
	$matchcards = array();

	memdump("mmR");
	$controller = Controller::getInstance();
	memdump("mmS");

	$emails = glob('tmp/mail_*');
	if (!$emails) return;
	array_multisort( array_map( 'filemtime', $emails ), SORT_NUMERIC, SORT_ASC, $emails);
	
	try {
			foreach ($emails as $email) {
				echo "</pre><hr>Processing $email\n<pre>";
				try {

					touch($email);

					$lines = file($email);

	memdump("f2");
					$result = parseMail($lines, $debug);

					if (!$result) {
						echo "Mail has no details\n";
						unlink($email);
						continue;
					}

					echo "Mail: ".$result['subject']." from ".$result['from']."<br>\n";

	memdump("mm0");

					//if ($matchcard->contact == 'lha.mcs@gmail.com') $matchcard->contact = 'lha.mens.regis@gmail.com';

					if (substr(strtolower($result['subject']),0,12) == 'registration') {

						$config = null;
						
						foreach ($controller->getConfig('club') as $configItem) {
							if (strtolower($configItem['Contact']) == strtolower($result['from'])) {
								$config = $configItem;
								break;
							}
						}

						if ($config != null || true) {
							$club = $config['Name'];

							echo "* Registration request from $club<br>\n";

							$csvAttachment = null;
							foreach ($result['attached'] as $attachment) {
								if ($attachment['type'] == 'csv' || $attachment['type'] == 'xls') {
									$csvAttachment = $attachment;
									break;
								}
							}

							if ($debug) continue;

							$success = True;
							if ($csvAttachment != null) {
								if (AUTO_REGISTER !== true) {
									sendMessage($club, "Auto-Registration Disabled\n\nRegistration system has auto-registration disabled");
									continue;
								}

								echo "- Processing attached file ${csvAttachment['filename']}<br>\n";
								/*
								$importResult = $controller->importList($result['date'], $csvAttachment['data'], $club);
								$importResult['filename'] = $csvAttachment['filename'];

								if (isset($importResult['errors'])) {
									sendMessage($club, template('registration_failure', $importResult));
								} else {
									sendMessage($club, template('registration_success', $importResult), null,
										array("$club.csv"=>$controller->exportList(array($club))));
								}
								*/

								register($csvAttachment['data'], $result['from'], $csvAttachment['filename'], $result['date']);
							} else {
								echo "- Processing registration query<br>\n";
								/*sendMessage($club, template('registration_request', $config), null,
									array("$club.csv"=>$controller->exportList(array($club))));*/
								query_register($result['from']);
							}
						} else {
							echo "No config for '${result['from']}'<br>\n";
							sendMessage(null, "Unauthorized Access\n\nThe email address ${result['from']} is not authorized for registration requests", array($result['from']));
						}
					} else {

						$images = array();

						foreach ($result['attached'] as $attachment) {
							if (strpos($attachment['type'], 'image') === 0) {
								$res = imagecreatefromstring($attachment['data']);
								if (imagesx($res) < 480 and imagesy($res) < 480) {
									$result['errors'][] = 'Image is too small - minimum 640x480';
									continue;
								}
								if ($attachment['type'] != 'image/jpg' and $attachment['type'] != 'image/jpeg' and $attachment['type'] != 'image/png') {
									$result['errors'][] = 'Unsupported image format: jpeg/png only';
									continue;
								}
								$images[] = $attachment;
							}
						}

						if (count($images) != 0) {

							echo "* Message has image attached<br>\n";

							if ($debug) continue;

							$matchcard = $controller->create($result['subject'], $result['date'], $result['from'], $images);

							$matchcards[] = $matchcard;

							echo "- club=".$matchcard['contactclub']."<br>\n";

							sendMessage($matchcard['contactclub'], 
								template('matchcard_receipt', $matchcard), array($matchcard['email']));
							//echo template('matchcard_receipt', $matchcard);
						}
					}

					if (isset($result['errors']) and count($result['errors']) > 0) {
						sendMessage(null,
							template('matchcard_failed', $result), array($result['email']));
					}
				} catch (Exception $e) {
					echo "Failed: ".$e->getMessage();
					continue;
				}

						echo "Mail has been processed\n";
			unlink($email);	
	memdump("mm1");
			}
	} catch (Exception $e) {
		echo "General failure: ".$e->getMessage().'\n';
	}

	echo "<hr>";

	echo (count($matchcards) > 0 ? count($matchcards)." new message(s)<br>": "No new message.<br>");
}

/* Check for new mail in the inbox.  If it exists write to temp */
function fetchMail() {
	$debug = isset($_REQUEST['debug']);

	if ($debug) echo "DEBUG Fetch<br>";

	$matchcards = array();

	memdump("mmR");
	$controller = Controller::getInstance();
	memdump("mmS");
	
	// Check mail
	echo date("Y-m-d h:i:s")." -- Connecting to gmail...<br>\n";
	flush();
	$inbox = imap_open("{imap.gmail.com:993/imap/ssl}INBOX", AUTO_EMAIL, AUTO_PASSWORD);
	$emails = imap_search($inbox, "UNSEEN");
	echo "Connected.<br><br>\n";
	flush();

	memdump("mmT");

	try {
		if ($emails) {
			foreach ($emails as $email) {
				echo "<hr>\n<pre>";
				try {
	memdump("mm9");
					$msgHeader = imap_fetchheader($inbox, $email);
					$msg = $msgHeader.imap_fetchtext($inbox, $email, FT_PEEK);
	memdump("f2");
					$a = 0;
					$stage = 0;
					$filename = tempnam("tmp", "mail_");
					$file = fopen($filename, "w");
					//echo substr($msg, 0, 300);
					while ($a !== false) {
						$b = strpos($msg, "\n", $a);

						if ($b === false) {
							$line = substr($msg, $a);
							$a = false;
						} else {
							$line = substr($msg, $a, $b + 1 - $a);
							$a = $b + 1;
						}
						//echo "**".$line."!!!!".$a."--".$b."\n";

						if ($stage == 0) {
							if (trim($line) != "") {
								$stage = 1;
								fwrite($file, trim($line));
								echo "Processing Headers\n";
							}
							continue;
						}

						if ($stage == 1) {
							if (trim($line) == "") {
								$stage = 2;
								fwrite($file, "\n\n");
								echo "Processing Body\n";
								continue;
							}

							if (stripos($line, "subject:") === 0) echo $line;
							if (stripos($line, "from:") === 0) echo $line;

							if (ltrim($line) == $line) {
								fwrite($file, "\n");
							} else {
								fwrite($file, " ");		// Join lines with whitespace the start
							}

							$line = trim($line);
						}

						fwrite($file, $line);
					}
					fclose($file);
					echo "Mail dequeued to $filename";
				} catch (Exception $e) {
					echo "Failed: ".$e->getMessage();
				}

				imap_setflag_full($inbox, $email, "\\Seen");
				break;
	memdump("mm1");
			}
		}
	} catch (Exception $e) {
		echo "General failure: ".$e->getMessage().'\n';
	}

	echo "<hr>";
}

fetchMail();
dequeueMail();
file_get_contents("http://lha.secureweb.ie/scrape.php?q=1&section=".SITE_NAME);
echo "Memory Usage:".memory_get_usage(true)."/".memory_get_usage(false).".<br>";
$queue = glob('tmp/mail_*');
$queue = $queue ? count($queue) : 0;
echo "Queue Size:".$queue;
echo "</pre>";
?>
