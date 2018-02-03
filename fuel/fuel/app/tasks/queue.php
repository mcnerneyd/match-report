<?php
namespace Fuel\Tasks;

class Queue
{
	public static function run() {

		\Log::info("Queue Execute");

		try {

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

				$command = \Model_Task::command($task);
				\Log::info("Execute command: ".$command);

				$curl = \Request::forge($command,'curl');
				$curl->execute();

				\Log::info("Command execution complete");
			} else {
				Queue::processMail();
			}
		} catch (Exception $e) {
			\Log::error("Failed to execute command:".$e->getMessage());
		}
	}	

	public static function processMail() {
		\Log::info("!Processed mail");
	}
}
