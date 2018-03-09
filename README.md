# CsvResponse2
CSV response for [Nette Framework](https://github.com/nette/nette)

* gzip encoding
* dynamic datasource

Download package
```console
composer require tomaskarlik/csvresponse2
````

Sample datasource
```php
<?php

declare(strict_types = 1);

namespace App\Model\Service\Feed;

use TomasKarlik\CsvResponse2\IDataSource;


class CsvExporter implements IDataSource
{

	/**
	 * @var array
	 */
	private $data = [
		['honza', '2016-01-01'],
		['pepa', '2016-01-02'],
		['david', '2016-01-03']
	];

	/**
	 * @var int
	 */
	private $index = 0;


	public function next(): ?array
	{
		if ( ! isset($this->data[$this->index])) {
			return NULL;
		}

		return $this->data[$this->index++];
	}

}
```

Presenter
```php
public function actionExportCsv(int $id): void
{
	$response = new CsvResponse($this->csvExporter, sprintf('export-%d.csv', $id));
	$this->sendResponse($response);
}
```