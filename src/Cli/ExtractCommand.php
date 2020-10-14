<?php


namespace SpannerExtractor\Cli;


use ScratchPad\Logger\Logger;
use SpannerExtractor\SpannerExtractor;

class ExtractCommand implements Command
{
	private $config;

	public function __construct($config = [])
	{
		$this->config = $config;
	}

	/**
	 * @throws \Throwable
	 */
	public function run()
	{
		Logger::info(['config' => $this->config]);

		$spannerExtractor = new SpannerExtractor([
			'instanceName' => $this->config['cliParams']['instance'],
			'databaseName' => $this->config['cliParams']['database'],
		]);
		foreach ($spannerExtractor->informationSchemaTables() as $table)
		{
			$tableName = $table['TABLE_NAME'];
			Logger::info(['tableName' => $table]);
			foreach ($spannerExtractor->rows($tableName) as $row)
			{
				Logger::info(['row' => $row]);
			}
		}
	}
}