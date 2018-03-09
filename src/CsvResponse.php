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

	const SEPARATOR_COMMA = ',';
	const SEPARATOR_SEMICOLON = ';';
	const SEPARATOR_TAB = '	';

	/**
	 * @var IDataSource
	 */
	private $dataSource;

	/**
	 * @var string
	 */
	private $glue = self::SEPARATOR_SEMICOLON;

	/**
	 * @var string
	 */
	private $outputFilename;


	public function __construct(IDataSource $dataSource, string $filename = 'output.csv')
	{
		$this->dataSource = $dataSource;
		$this->outputFilename = $filename;
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

		$acceptEncoding = $httpRequest->getHeader('Accept-Encoding');
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

		$count = 1;
		while (($row = $this->dataSource->next()) !== NULL) {
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
