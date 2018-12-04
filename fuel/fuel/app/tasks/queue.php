<?php
namespace Fuel\Tasks;

class Queue
{
	public static function run() {

		\Log::debug("Queue Execute");

		try {
			$command = static::getNextTask();

			if (!$command) {
				$task = \Model_Task::query()->order_by('datetime','asc')
					->where('status', '=', 'Queued')
					->where('datetime', '<', date("Y-m-d H:i:s"))
					->rows_limit(1)
					->get_one();

				if ($task) {
					$date = strtotime($task['datetime']);

					switch ($task['recur']) {
						case 'Quarter':
							$date = strtotime("+15 minutes", $date);
							break;
						case 'Hour':
							$date = strtotime("+1 hour", $date);
							break;
						case 'Day':
							$date = strtotime("+1 day", $date);
							break;
						case 'Week':
							$date = strtotime("+1 week", $date);
							break;
						case 'Month':
							$date = strtotime("+1 month", $date);
							break;
						case 'Year':
							$date = strtotime("+1 year", $date);
							break;
						default:
							$date = null;
							break;
					}

					if ($date != null) {
						$task['datetime'] = date("Y-m-d H:i:s", $date);
					} else {
						$task['datetime'] = null;
					}

					$task->save();
					$command = $task['command'];
				}
			}

			if ($command) {
				$command = \Model_Task::command($command);
				\Log::info("Execute command: ".$command);

				$curl = \Request::forge($command,'curl');
				$curl->execute();

				\Log::info("Command execution complete");
			}
		} catch (Throwable $e) {
			\Log::error("Failed to execute command:".$e->getMessage());
		}
	}	

	private static function getNextTask() {
		$fp = fopen("lha.secureweb.ie/queue", "r+");

		$resultTask = null;

		if (flock($fp, LOCK_EX)) {
			$line = array();
			while(true) {
				$line = fgets($fp);
				if ($line === FALSE) break;
				if (!$line) continue;
				if ($resultTask == null) {
					$task = json_decode($line);
					if (!isset($task->date) || strtotime($task->date) < time()) {
						$resultTask = $task;
						continue;
					}
				}

				$lines[] = $line;
			}

			if ($resultTask) {
				rewind($fp);
				ftruncate($fp, 0);
				foreach ($lines as $line) {
					fputs($fp, $line);
				}

				if (isset($resultTask->recur)) {
					$date = "now";
					if (isset($resultTask->date)) $date = $resultTask->date;
					$copyOfResultTask = $resultTask;
					$copyOfResultTask->date = static::recur($date, $resultTask->recur);
					if ($copyOfResultTask->date != null) {
						fputs($fp, json_encode($copyOfResultTask)."\n");
					}
				}
			}
		}

		fclose($fp);

		$resultTask = (array)$resultTask;

		return $resultTask['command-endpoint'];
	}

	public static function recur($date, $interval) {
		$date = strtotime($date);

		switch ($interval) {
			case 'Quarter':
				$date = strtotime("+15 minutes", $date);
				break;
			case 'Hour':
				$date = strtotime("+1 hour", $date);
				break;
			case 'Day':
				$date = strtotime("+1 day", $date);
				break;
			case 'Week':
				$date = strtotime("+1 week", $date);
				break;
			case 'Month':
				$date = strtotime("+1 month", $date);
				break;
			case 'Year':
				$date = strtotime("+1 year", $date);
				break;
			default:
				\Log::warning("Unknown task interval: $interval");
				return null;
		}

		return date("Y-m-d H:i:s", $date);
	}

	public static function processMail() {
		\Log::info("!Processed mail");
	}
}
