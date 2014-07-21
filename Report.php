<?php

class Report
{
	const ReportDatesFileName = './reportDates.list';
	private $startDate;
	private $stopDate;

	static public function makeWithMonth($month)
	{
		if (strlen($month) == 3) {
			$year = date('Y');
			$month .= $year;
		}
		$fd = fopen(self::ReportDatesFileName,
					'r');
		if ($fd == null) {
			throw new Exception('File: ' .
								self::ReportDatesFileName .
								' cannot be opened for reading.');
		}

		$startDate = false;
		$stopDate = false;
		while (	!feof($fd)
				&&
				$stopDate === false
				&&
				$stopDate === false) {
			$line = trim(fgets($fd));
			if (strlen($line) == 0) {
				continue;
			}
			$parts = explode(	'=',
								$line);
			if ($startDate) {
				$stopDate = $parts[1];
				continue;
			}
			if ($month != $parts[0]) {
				continue;
			}
			$startDate = $parts[1];
		}
		fclose($fd);

		if ($startDate
			&&
			$stopDate) {
			$parts = explode('-', $stopDate);
			$stopDate = $parts[0] . '-' . $parts[1] . '-' . ($parts[2] - 1);
			return new self($startDate, $stopDate);
		}

		if ($startDate) {
			throw new Exception('Start date ' .
								$startDate .
								' found, but no stop date found.');
		}

		throw new Exception('No start and stop dates for: ' .
							$month);
	}

	public function __construct($startDate, $stopDate)
	{
		$this->startDate = $startDate;
		$this->stopDate = $stopDate;
	}

	/**
	 * Command: Report
	 */
	public function run()
	{
		//
		// Setup the categories
		//
		$categories = Category::readAll();
		$report = array();
		foreach ($categories as $category) {
			$report[$category->getName()] = array();
		}
		$report['Ignored'] = array();

		$transactions = array();
		Transaction::getTransactions(	$transactions,
										$this->startDate,
										$this->stopDate);

		foreach ($transactions as &$transaction) {
			if ($transaction->getStatus() == Transaction::StatusIgnore) {
				$report['Ignored'][] = $transaction;
			} else {
				$report[$transaction->getCategory()->getName()][] = $transaction;
			}
		}

		$total = 0;
		foreach ($report as $categoryName => $transactions) {
			if (count($transactions) == 0) {
				continue;
			}
			$categoryTotal  = 0;
			echo $categoryName . "\n";
			foreach  ($transactions as $transaction) {
				printf(	"\t%s %-30.30s %10.2f\n",
						$transaction->getDate(),
						$transaction->getDescription(),
						$transaction->getAmount());
				$categoryTotal += $transaction->getAmount();
			}
			printf("\tTotal: %.2f", $categoryTotal);
			printf("\n\n\n");
			if ($categoryName == 'Ignored') {
				continue;
			}
			$total += $categoryTotal;
		}
		printf("Total: %.2f\n", $total);
	}
}

?>
