<?php

class Transaction
{
	private $id;
	private $date;
	private $description;
	private $amount;
	private $categoryId;
	private $category;

	/**
	 * Constructor
	 */
	public function __construct($id,
								$date,
								$description,
								$amount,
								$categoryId)
	{
		$this->id = $id;
		$this->date = $date;
		$this->description = $description;
		$this->amount = $amount;
		$this->categoryId = $categoryId;
		$this->category = false;
	}

	/**
	 * Constructor via details
	 */
	static public function makeWithDetails(	$date,
											$description,
											$amount)
	{
		global $db;
		$q = 	"SELECT * FROM trans WHERE trdate = '" .
				$date .
				"' AND description like '" .
				$description .
				"%' AND amount='" .
				$amount .
				"'";
		$result = $db->query($q);
		if (!$result) {
			throw new Exception('Failed fetching the data from database. Query: ' .
								$q);
		}
		$rows = $result->fetchAll();
		$c = count($rows);
		if ($c == 0) {
			return null;
		}
		if ($c > 1) {
			throw new Exception('Expected only 1 record with supplied details. Instead got: ' .
								$c .
								' entries. ');
		}
		$row = $rows[0];
		return new Transaction(	$row[0],
								$row[1],
								$row[2],
								$row[3],
								$row[4]);
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
		
		global $db;
		$q = 	"INSERT INTO trans VALUES (" .
				"null," .
				"'" . $date . "'," .
				"'" . $description . "'," .
				"'" . $amount . "'," .
				$category->getId() .
				")";
		$result = $db->query($q);
		if (!$result) {
			throw new Exception('Database error encountered while inserting. Query: ' .
								$q);
		}
		
		return new self($id,		// ?
						$date,
						$description,
						$amount,
						$category->getId());
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
	
	public function toString()
	{
		return $this->date . ' ' . $this->description . ' ' . $this->amount;
	}
		
	public function setCategory(Category $newCategory)
	{
		global $db;
		$q = 	"UPDATE trans SET category=" .
				$newCategory->getId() .
				" WHERE id=" .
				$this->id;
		$result = $db->query($q);
		if (!$result) {
			throw new Exception('Unable to update the category. Query: ' .
								$q);
		}
		$this->categoryId = $newCategory->getId();
		$this->category = $newCategory;
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
			$transactionDetail['date'] 		= trim(str_replace("/", "-", $parts[0]));
			$transactionDetail['description']	= trim(str_replace("'", "", $parts[3]));
			$transactionDetail['amount'] 		= trim($parts[1]);
			$transactionDetails[] = $transactionDetail;
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
		foreach ($transactionDetails as &$transactionDetail) {
			$transaction = Transaction::makeWithDetails($date,
														$description,
														$amount);
			if ($transaction != null) {
				continue;
			}
			Transaction::makeNew(	$transactionDetail['date'],
									$transactionDetail['description'],
									$transactionDetail['amount']);
		}
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
		global $db;
		$result = $db->query($q);
		if (!$result) {
			throw new Exception('Failed fetching the data from database. Query: ' .
								$q);
		}
		echo $q . "\n";
		$rows = $result->fetchAll();
		foreach ($rows as &$row) {
			$transactions[] = new Transaction(	$row[0],
												$row[1],
												$row[2],
												$row[3],
												$row[4]);
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
											$row[4]);
			$category = Category::findWithTransactionDescription($transaction->description);
			if ($category->getId() == 0) {
				unset($transaction);
				continue;
			}
			echo 	"Resetting category to: " .
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
