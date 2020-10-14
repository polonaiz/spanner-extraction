<?php

namespace SpannerExtractor;

use ArrayHelper\ArrayHelper;
use Exception;
use Google\Cloud\Spanner\Database;
use Google\Cloud\Spanner\SpannerClient;

class SpannerExtractor
{
	private $config;

	public function __construct($config = [])
	{
		$this->config =
			$config + [
				'suppressKeyFileNotice' => true,
				'instanceName' => 'test-instance',
				'instanceConfigurationName' => 'regional-asia-northeast3', // # gcloud spanner instance-configs list
				'databaseName' => 'test-database',
				'logger' => function (array $message)
				{
					echo \json_encode($message) . PHP_EOL;
				}
			];
	}

	/**
	 * @param array $param
	 * @throws Exception
	 */
	public function setUp(array $param = [])
	{
		$spanner = new SpannerClient($this->config);

		//
		$instanceName = $this->config['instanceName'];
		$instance = $spanner->instance($instanceName);
		if ($instance->exists())
		{
			$this->config['logger']([
				'type' => 'deleteInstance',
				'instanceName' => $instance
			]);
			$instance->delete();
		}

		//
		$instanceConfiguration = $spanner->instanceConfiguration(
			$this->config['instanceConfigurationName']
		);
		$instance = $spanner->createInstance(
			$instanceConfiguration,
			$instanceName
		)->pollUntilComplete();
		$this->config['logger']([
			'type' => 'createInstance',
			'info' => $instance->info()
		]);

		//
		$database = $instance->createDatabase(
			$this->config['databaseName']
		)->pollUntilComplete();
		$this->config['logger']([
			'type' => 'createDatabase',
			'info' => $database->info()
		]);

		//
		$sql = <<<SQL
			CREATE TABLE TEST_TABLE_1 (
				pk INT64,
				value INT64,
				md5 STRING (32)
			) PRIMARY KEY ( pk ASC)
		SQL;
		$info = $database->updateDdl(
			$sql
		)->pollUntilComplete();
		$this->config['logger']([
			'type' => 'createTable',
			'info' => $info
		]);

		//
		$sql = <<<SQL
			CREATE TABLE TEST_TABLE_2 (
				pk1 INT64,
				pk3 INT64,
				pk2 STRING (8),
				value INT64,
				md5 STRING (32)
			) PRIMARY KEY ( pk1 ASC, pk2 DESC, pk3 ASC)
		SQL;
		$info = $database->updateDdl(
			$sql
		)->pollUntilComplete();
		$this->config['logger']([
			'type' => 'createTable',
			'info' => $info
		]);
	}

	/**
	 * @param array $param
	 * @throws Exception
	 */
	public function ingest(array $param = [])
	{
		$spanner = new SpannerClient($this->config);

		$instanceName = $this->config['instanceName'];
		$instance = $spanner->instance($instanceName);
		if (!$instance->exists())
		{
			throw new Exception("instance not exists: {$instanceName}");
		}

		$databaseName = $this->config['databaseName'];
		$database = $instance->database($databaseName);
		if (!$database->exists())
		{
			throw new Exception("database not exists: {$databaseName}");
		}

		$insertBeginTime = \time();
		$data = [];
		for ($idx = $begin = $param['begin']; $idx < $end = $param['end']; $idx++)
		{
			$data[] = ['pk' => $idx, 'value' => $value = \rand(100000, 999999), 'md5' => \md5($value)];
		}
		$timestamp = $database->insertOrUpdateBatch('TEST_TABLE_1', $data);
		$this->config['logger']([
			'type' => 'ingestionSummary',
			'begin' => $begin,
			'end' => $end,
			'timestamp' => $timestamp,
			'insertTime' => \time() - $insertBeginTime
		]);
	}

	/**
	 * @param array $param
	 * @throws Exception
	 */
	public function extract(array $param = [])
	{
		$spanner = new SpannerClient($this->config);

		$database = $spanner->connect(
			$this->config['instanceName'],
			$databaseName = $this->config['databaseName']
		);

		function extractQueryResultRows(Database $database, string $query): \Generator
		{
			$result = $database->execute($query);
			foreach ($result->rows() as $row)
			{
				yield $row;
			}
		}

		function extractPrimaryColumns(Database $database, string $tableName): \Generator
		{
			$query = <<<SQL
				SELECT 
					* 
				FROM 
					INFORMATION_SCHEMA.INDEX_COLUMNS 
				WHERE 
					TABLE_SCHEMA = '' AND 
					`TABLE_NAME` = '{$tableName}' AND 
					INDEX_TYPE = 'PRIMARY_KEY'
				ORDER BY
					ORDINAL_POSITION ASC
			SQL;
			return extractQueryResultRows($database, $query);
		}

		$tableName =
			'TEST_TABLE_1';
		$primaryKeyColumns =
			\iterator_to_array(extractPrimaryColumns($database, $tableName));
		$orderBy =
			(new ArrayHelper($primaryKeyColumns))->map(function ($primaryKeyColumn)
			{
				return "{$primaryKeyColumn['COLUMN_NAME']} {$primaryKeyColumn['COLUMN_ORDERING']}";
			})->implode(', ')->get();
		$query =
			<<<SQL
			SELECT
				*
			FROM 
				{$tableName}
			ORDER BY
				{$orderBy}
		SQL;
		$this->config['logger'](['query' => $query]);
		foreach (extractQueryResultRows($database, $query) as $row)
		{
			$pk = $row['pk'];
			if ($pk % 1000 === 0)
			{
				$this->config['logger']([
					'type' => 'row',
					'row' => $row
				]);
			}
		}
	}

	/**
	 * @param array $param
	 * @throws Exception
	 */
	public function cleanup(array $param = [])
	{
		$spanner = new SpannerClient($this->config);

		$instanceName = $this->config['instanceName'];
		$instance = $spanner->instance($instanceName);
		if ($instance->exists())
		{
			$instance->delete();
			$this->config['logger']([
				'type' => 'instanceDeleted',
				'instanceName' => $instanceName
			]);
		} else
		{
			$this->config['logger']([
				'type' => 'instanceNotExists',
				'instanceName' => $instanceName
			]);
		}
	}

	/**
	 * @throws \Throwable
	 */
	public function informationSchemaTables(): \Generator
	{
		$spannerClient = new SpannerClient($this->config);

		$database = $spannerClient->connect(
			$this->config['instanceName'],
			$this->config['databaseName']
		);
		$query = <<<SQL
			SELECT
			  *
			FROM
			  INFORMATION_SCHEMA.TABLES
			WHERE
			  TABLE_CATALOG = '' AND TABLE_SCHEMA = ''
		SQL;
		foreach (self::extractQueryResultRows($database, $query) as $row)
		{
			yield $row;
		}
	}

	private static function extractQueryResultRows(Database $database, string $query): \Generator
	{
		$result = $database->execute($query);
		foreach ($result->rows() as $row)
		{
			yield $row;
		}
	}

	public function rows($table): \Generator
	{
		$spanner = new SpannerClient($this->config);

		$database = $spanner->connect(
			$this->config['instanceName'],
			$this->config['databaseName']
		);
		$query = <<<SQL
			SELECT
			  *
			FROM
			  {$table}
		SQL;
		foreach (self::extractQueryResultRows($database, $query) as $row)
		{
			yield $row;
		}
	}
}