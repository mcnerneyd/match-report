<?php

class Log extends \Fuel\Core\Log
{
	public static function initialize()
	{
		// load the file config
		\Config::load('file', true);

		// get the required folder permissions
		$permission = \Config::get('file.chmod.folders', 0777);

		// determine the name and location of the logfile
		$path = \Config::get('log_path', APPPATH . 'logs' . DS);

		// and make sure it exsts
		if (!is_dir($path) or !is_writable($path)) {
			\Config::set('log_threshold', \Fuel::L_NONE);
			throw new \FuelException('Unable to create the log file. The configured log path "' . $path . '" does not exist.');
		}

		// determine the name of the logfile
		$filename = \Config::get('log_file', null);
		if (empty($filename)) {
			$filename = date('Y') . DS . date('m') . DS . date('d') . '.php';
		}

		$fullpath = dirname($filename);

		// make sure the log directories exist
		try {
			// make sure the full path exists
			if (!is_dir($path . $fullpath)) {
				\File::create_dir($path, $fullpath, $permission);
			}

			// open the file
			$handle = fopen($path . $filename, 'a');
		} catch (\Exception $e) {
			\Config::set('log_threshold', \Fuel::L_NONE);
			throw new \FuelException('Unable to access the log file. Please check the permissions on ' . \Config::get('log_path') . '. (' . $e->getMessage() . ')');
		}

		static::$path = $path;
		static::$filename = $filename;

		if (!filesize($path . $filename)) {
			fwrite($handle, "<?php defined('COREPATH') or exit('No direct script access allowed'); ?>" . PHP_EOL . PHP_EOL);
			chmod($path . $filename, \Config::get('file.chmod.files', 0666));
		}
		fclose($handle);

		// create the streamhandler, and activate the handler
		$stream = new \Monolog\Handler\StreamHandler($path . $filename, \Monolog\Logger::DEBUG);
		$stdout = new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG);
		$formatter = new MyFormatter();
		$stream->setFormatter($formatter);
		$stdout->setFormatter($formatter);
		static::$monolog->pushHandler($stream);
		static::$monolog->pushHandler($stdout);
	}
}

class MyFormatter extends \Monolog\Formatter\NormalizerFormatter
{

	public function format(array $record)
	{

		if (strpos($record['message'], "Fuel\\Core\\") === 0)
			return "";

		// do not log anything with the word "securelog" in it
		if (strpos($record['message'], "securelog") === 0)
			return "";

		$username = Session::get('REQUEST_KEY', "");
		if ($username)
			$username = " ($username)";

		if ($record['level_name'] == 'DEBUG')
			return "      ∙ " . $record['message'] . $username . PHP_EOL;

		$output = $record['datetime']->format('Y-m-d\\TH:i:s') . " [" . substr($record['level_name'], 0, 1) . "] " . $record['message'] . $username;
		$output = str_replace(PHP_EOL, PHP_EOL . "      | ", $output);
		$output = $output . PHP_EOL;
		$exception = \Arr::get($record, "context.exception", null);

		if ($exception != null) {
			//$output = $output . join(array_map(fn($a): string => "{$a['file']}:{$a['line']}", $exception->trace), "\n");
			$output = $output . $exception->getTraceAsString() . PHP_EOL;
		}

		return $output;
	}
}

