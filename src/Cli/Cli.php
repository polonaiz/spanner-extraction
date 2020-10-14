<?php

namespace SpannerExtractor\Cli;

use Phalcon\Cop\Parser;
use ScratchPad\Exception\Exception;
use ScratchPad\Logger\CompositeLogger;
use ScratchPad\Logger\ConsoleLogger;
use ScratchPad\Logger\Logger;
use ScratchPad\Throwable;

class Cli
{
	/**
	 * @throws \Throwable
	 */
	public static function main()
	{
		try {
			self::env();

			global $argv;
			$cliParser = new Parser();
			$cliParams = $cliParser->parse($argv);
			$command = self::createCommand($cliParams);
			$command->run();
			return;
		}
		catch (\Throwable $t)
		{
			echo \json_encode([
				'type' => 'exception',
				'message' => $t->getMessage(),
				'trace' => Throwable::getTraceSafe($t),
			], JSON_PRETTY_PRINT) . PHP_EOL;
		}
	}

	/**
	 * @throws \Throwable
	 */
	public static function env()
	{
		ini_set('memory_limit', '1024M');
		ini_set('serialize_precision', -1);
		Exception::convertNonFatalErrorToException();
		\date_default_timezone_set('Asia/Seoul');

		// configure logger
		Logger::setLogger(new CompositeLogger(
			[
				'defaults' =>
					[
						'timestamp' => CompositeLogger::getTimeStamper(),
						'host' => gethostname(),
						'program' => 'spanner-extractor-cli',
						'pid' => getmypid()
					],
				'loggerFilterPairs' =>
					[
						[
							'logger' => new ConsoleLogger(),
							'filter' => CompositeLogger::getSelectorAll()
						],
					]
			]));
	}

	/**
	 * @param $cliParams
	 * @return Command
	 * @throws \Throwable
	 */
	public static function createCommand($cliParams)
	{
		$commandMap = [
			'extract' => 'SpannerExtractor\Cli\ExtractCommand',
			'list-instance' => 'SpannerExtractor\Cli\ListInstanceCommand',
		];

		/** @var Command $command */
		return new $commandMap[$cliParams[0]]([
			'cliParams' => $cliParams
		]);
	}
}