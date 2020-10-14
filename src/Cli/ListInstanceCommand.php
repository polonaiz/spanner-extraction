<?php


namespace SpannerExtractor\Cli;


use Google\Cloud\Spanner\Instance;
use Google\Cloud\Spanner\SpannerClient;
use ScratchPad\Logger\Logger;

class ListInstanceCommand implements Command
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
		Logger::info($this->config);

		$spanner = new SpannerClient($this->config);
		/** @var Instance $instance */
		foreach ($spanner->instances() as $instance)
		{
			Logger::info(['instanceInfo' => $instance->info()]);
		}
	}
}