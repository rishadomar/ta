<?php

class Category
{
	static $categories = false;
	//const CategoryFileName = '/home/rishadomar/data/ta/categories.list';	
	const CategoryFileName = '/home/rishadomar/src/ta/categories.list';
	private $id;
	private $name;
	private $transactionDescriptions;

	public function __construct($id, $name)
	{
		$this->id = $id;
		$this->name = $name;
		$this->transactionDescriptions = array();
	}
	
	static public function makeWithId($id)
	{
		if (self::$categories === false) {
			self::readAll();
		}
		if (!isset(self::$categories[$id])) {
			throw new Exception('No such category: ' .
								$id);
		}
		return self::$categories[$id];
	}

	static public function makeWithName($name)
	{
		if (self::$categories === false) {
			self::readAll();
		}
		foreach (self::$categories as &$category) {
			if ($category->name == $name) {
				return $category;
			}
		}
		throw new Exception('No such category: ' .
							$name);
	}

	public function addTransactionDescription($transactionDescription)
	{
		$this->transactionDescriptions[] = $transactionDescription;
	}

	public function getTransactionDescriptions()
	{
		return $this->transactionDescriptions;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getName()
	{
		return $this->name;
	}
	
	static public function findWithTransactionDescription($description)
	{
		self::readAll();
		foreach (self::$categories as &$category) {
			foreach ($category->getTransactionDescriptions() as $transactionDescription) {
				if (strstr( $description,
							$transactionDescription)) {
					return $category;
				}
			}
		}
		return self::$categories[0];
	}

	static public function readAll()
	{
		if (self::$categories !== false) {
			return self::$categories;
		}
		
		$fd = fopen(self::CategoryFileName,
					'r');
		if ($fd === null) {
			throw new Exception('File: ' .
								self::CategoryFileName . 
								' cannot be opened for reading.');
		}
		
		self::$categories = array();
		while (!feof($fd)) {
			$line = fgets($fd);
			if (strlen(trim($line)) == 0) {
				continue;
			}
			$parts = explode(	'=',
								$line);
			$idNameParts = explode(	',',
									$parts[0]);
			$category = new Category($idNameParts[0],
									 $idNameParts[1]);
			$parts = explode(	';',
								trim($parts[1]));
			foreach ($parts as $part) {
				$category->addTransactionDescription(substr($part, 1, strlen($part) - 2));
			}
			self::$categories[$category->id] = $category;
		}

		return self::$categories;
	}
}

?>
