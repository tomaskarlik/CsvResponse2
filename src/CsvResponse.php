<?php

declare(strict_types = 1);

namespace TomasKarlik\CsvResponse2;

use Exception;
use InvalidArgumentException;
use Nette\Application\IResponse as NetteAppIReseponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;


class CsvResponse implements NetteAppIReseponse
{

	public const SEPARATOR_COMMA = ',';
	public const SEPARATOR_SEMICOLON = ';';
	public const SEPARATOR_TAB = '	';

	/**
	 * @var callable[]
	 */
	private $columnsCallbacks = [];

	/**
	 * @var IDataSource
	 */
	private $dataSource;

	/**
	 * @var string
	 */
	private $glue = self::SEPARATOR_SEMICOLON;

	/**
	 * @var array
	 */
	private $header = [];

	/**
	 * @var string
	 */
	private $outputFilename;


	public function __construct(
		IDataSource $dataSource,
		string $outputFilename = 'output.csv'
	) {
		$this->dataSource = $dataSource;
		$this->outputFilename = $outputFilename;
	}


	/**
	 * @param mixed $column
	 * @param callable $callback
	 */
	public function addColumnCallback($column, callable $callback): void
	{
		if (isset($this->columnsCallbacks[$column])) {
			throw new InvalidArgumentException(sprintf('%s: column "%s" callback exists!', __CLASS__, $column));
		}

		$this->columnsCallbacks[$column] = $callback;
	}


	public function setGlue(string $glue): CsvResponse
	{
		if (empty($glue) || preg_match('/[\n\r"]/s', $glue)) {
			throw new InvalidArgumentException(sprintf('%s: glue cannot be an empty or reserved character!', __CLASS__));
		}

		$this->glue = $glue;
		return $this;
	}


	public function getGlue(): string
	{
		return $this->glue;
	}


	public function setHeader(array $header): CsvResponse
	{
		$this->header = $header;
		return $this;
	}


	public function setOutputFilename(string $outputFilename): CsvResponse
	{
		$this->outputFilename = $outputFilename;
		return $this;
	}


	public function getOutputFilename(): string
	{
		return $this->outputFilename;
	}


	public function send(IRequest $httpRequest, IResponse $httpResponse): void
	{
		$httpResponse->setContentType('text/csv', 'utf-8');
		$httpResponse->setHeader('Content-Disposition', 'attachment; filename="' . $this->outputFilename . '"');

		$acceptEncoding = $httpRequest->getHeader('Accept-Encoding', '');
		$supportsGzip = stripos($acceptEncoding, 'gzip' ) !== FALSE;
		if ($supportsGzip) {
			$httpResponse->setHeader('Content-Encoding', 'gzip');
			ob_start('ob_gzhandler');
		}

		$buffer = fopen('php://output', 'w');
		if ($buffer === FALSE) {
			throw new Exception(sprintf('%s: error create buffer!', __CLASS__));
		}
		fputs($buffer, $this->getUtf8BomBytes());

		if (count($this->header)) {
			fputcsv($buffer, $this->header, $this->glue);
		}

		$count = 1;
		while (($row = $this->dataSource->next()) !== NULL) {
			foreach ($this->columnsCallbacks as $column => $callback) {
				if ( ! isset($row[$column])) {
					continue;
				}
				$row[$column] = $callback($row[$column]);
			}

			fputcsv($buffer, $row, $this->glue);
			if ($count % 1000 === 0) {
				if ($supportsGzip) {
					ob_flush();
				}
				flush();
			}
			$count++;
		}
		fclose($buffer);
	}


	private function getUtf8BomBytes(): string
	{
		return chr(239) . chr(187) . chr(191);
	}

}
