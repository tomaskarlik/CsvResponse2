<?php

declare(strict_types = 1);

namespace TomasKarlik\CsvResponse2;


interface IDataSource {

	/**
	 * Return next row
	 * 
	 * @return array|NULL
	 */
	function next(): ?array;

}
