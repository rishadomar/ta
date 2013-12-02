<?php

class Transaction
{
	const StatusNormal = 0;
	const StatusIgnore = 1;
	const StatusSplit = 2;

	private $id;
	private $date;
	private $description;
	private $amount;
	private $categoryId;
	private $category;
	private $status;
	private $comment;

	/**
	 * Constructor
	 */
	public function __construct($id,
								$date,
								$description,
								$amount,
								$categoryId,
								$status,
								$comment)
	{
		$this->id 			= $id;
		$this->date 		= $date;
		$this->description 	= $description;
		$this->amount 		= $amount;
		$this->categoryId 	= $categoryId;
		$this->category 	= false;
		$this->status 		= $status;
		$this->comment 		= $comment;
	}

	/**
	 * Constructor via details
	 */
	static public function makeWithDetails(	$date,
											$description,
											$amount)
	{
		$q = 	"SELECT * FROM trans WHERE trdate = '" .
				$date .
				"' AND description like '" .
				$description .
				"%' AND amount=" .
				$amount;
		/**
		global $db;
		$query = $db->prepare($q);
		$result = $query->execute();
		if (!$result) {
			throw new Exception('Failed fetching the data from database. Query: ' .
								$q);
		}
		**/
		$result = runQuery($q);
		$rows = $result->fetchAll(PDO::FETCH_ASSOC);
		if (!$rows) {
			return null;
		}
		if (count($rows) > 1) {
			throw new Exception('More than 1 record matched the criteria.');
		}
		$row = $rows[0];
		return new Transaction(	$row['ID'],
								$row['trdate'],
								$row['description'],
								$row['amount'],
								$row['category'],
								$row['status'],
								$row['comment']);
	}

	/**
	 * Make a new entry
	 */
	static public function makeNew(	$date,
									$description,
									$amount)
	{
		$transaction = Transaction::makeWithDetails($date,
													$description,
													$amount);
		if ($transaction != null) {
			return $transaction;
		}

		$category = Category::findWithTransactionDescription($description);

		$q = 	"INSERT INTO trans VALUES (" .
				"null," .
				"'" . $date . "'," .
				"'" . $description . "'," .
				$amount . "," .
				$category->getId() . "," .
				self::StatusNormal . "," .
				"null" .
				")";
		$result = runQuery($q);
		/**
		global $db;
		$result = $db->query($q);
		if (!$result) {
			throw new Exception('Database error encountered while inserting. Query: ' .
								$q);
		}
		**/
		return self::makeWithDetails(	$date,
										$description,
										$amount);





		return new self($id,		// ?
						$date,
						$description,
						$amount,
						$category->getId(),
						self::StatusNormal,
						null);
	}

	public function getDate()
	{
		return $this->date;
	}

	public function getDescription()
	{
		return  $this->description;
	}

	public function getAmount()
	{
		return $this->amount;
	}

	public function getCategory()
	{
		if ($this->category === false) {
			$this->category = Category::makeWithId($this->categoryId);
		}
		return $this->category;
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function getComment()
	{
		return $this->comment;
	}

	public function toString()
	{
		return $this->date . ' ' . $this->description . ' ' . $this->amount. ' ' . $this->status . ' ' . $this->comment;
	}

	public function setCategory(Category $newCategory)
	{
		$q = 	"UPDATE trans SET category=" .
				$newCategory->getId() .
				" WHERE id=" .
				$this->id;
		$result = runQuery($q);
		/**
		global $db;
		$result = $db->prepare($q);
		$result = $db->execute();
		if (!$result) {
			throw new Exception('Unable to update the category. Query: ' .
								$q);
		}
		**/
		$this->categoryId = $newCategory->getId();
		$this->category = $newCategory;
	}

	public function setComment($comment)
	{
		$q = 	"UPDATE trans SET comment='" .
				$comment .
				"' WHERE id=" .
				$this->id;
		$result = runQuery($q);
		/**
		global $db;
		$query = $db->prepare($q);
		$result = $query->execute();
		if (!$result) {
			$errorInfo = $query->errorInfo();
			throw new Exception('Unable to set the comment. Query: ' .
								$q .
								' Reason: ' .
								$errorInfo[2]);
		}
		**/
		$this->comment = $comment;
	}

	public function setStatus($status)
	{
		if ($status != self::StatusNormal
			||
			$status != self::StatusIgnore
			||
			$status != self::StatusSplit) {
			throw new Exception('Invalid status to set: ' . $status);
		}
		$q = 	"UPDATE trans SET status=" .
				$status .
				" WHERE id=" .
				$this->id;
		$result = runQuery($q);
		/**`
		global $db;
		$result = $db->query($q);
		if (!$result) {
			throw new Exception('Unable to set the status. Query: ' .
								$q);
		}
		**/
		$this->status = $status;
	}

	static public function readAll($fileName,
								   &$transactionDetails)
	{
		$fd = fopen($fileName,
					'r');
		if ($fd === null) {
			throw new Exception('File: ' .
								$fileName .
								' cannot be opened for reading.');
		}

		// skip the first few lines
		for ($i = 0; $i < 7; ++$i) {
			fgets($fd);
		}
		while (!feof($fd)) {
			$line = fgets($fd);
			if (strlen(trim($line)) == 0) {
				continue;
			}
			$parts = explode(	',',
								$line);
			$date = $parts[0];
			$transactionDetail['date'] 			= substr($date, 0, 4) . '-' . substr($date, 5, 2) . '-' . substr($date, 8, 2);
			$transactionDetail['amount'] 		= trim($parts[1]);
			$transactionDetail['description']	= trim(str_replace("'", "", $parts[3]));
			$transactionDetails[] 				= $transactionDetail;
			unset($transactionDetail);
		}
	}

	/**
	 * Command: Import
	 */
	static public function import($fileName)
	{
		$transactionDetails = array();
		Transaction::readAll(	$fileName,
								$transactionDetails);
		$ignored = 0;
		$added = 0;
		foreach ($transactionDetails as &$transactionDetail) {
			$transaction = Transaction::makeWithDetails($transactionDetail['date'],
														$transactionDetail['description'],
														$transactionDetail['amount']);
			if ($transaction != null) {
				++$ignored;
				continue;
			}
			Transaction::makeNew(	$transactionDetail['date'],
									$transactionDetail['description'],
									$transactionDetail['amount']);
			++$added;
		}
		return array(	'ignored' => $ignored,
						'added' => $added);
	}

	static public function getTransactions(	&$transactions,
											$startDate,
											$stopDate)
	{
		$q = 	"SELECT * FROM trans WHERE trdate >= '" .
				$startDate .
				"' AND trdate <= '" .
				$stopDate .
				"' ORDER BY trdate ASC";
		$result = runQuery($q);
		/**
		global $db;
		$result = $db->query($q);
		if (!$result) {
			throw new Exception('Failed fetching the data from database. Query: ' .
								$q);
		}
		**/
		echo $q . "\n";
		$rows = $result->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as &$row) {
			$transactions[] = new Transaction(	$row['ID'],
												$row['trdate'],
												$row['description'],
												$row['amount'],
												$row['category'],
												$row['status'],
												$row['comment']);
		}
	}

	static public function reCategorise()
	{
		$q = "SELECT * FROM trans WHERE category = 0";
		global $db;
		$result = $db->query($q);
		if (!$result) {
			throw new Exception('Failed fetching the data from database. Query: ' .
								$q);
		}
		$rows = $result->fetchAll();
		foreach ($rows as &$row) {
			$transaction = new Transaction(	$row[0],
											$row[1],
											$row[2],
											$row[3],
											$row[4],
											$row[5],
											$row[6]);
			echo "Considering: " . $transaction->description . "\n";
			$category = Category::findWithTransactionDescription($transaction->description);
			if ($category->getId() == 0) {
				unset($transaction);
				continue;
			}
			echo 	"Setting category to: " .
					$category->getName() .
					" for transaction " .
					$transaction->toString() .
					"\n";
			$transaction->setCategory($category);
			unset($transaction);
		}
	}
}

?>
