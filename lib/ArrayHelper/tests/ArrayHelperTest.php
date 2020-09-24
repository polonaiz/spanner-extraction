<?php

namespace ArrayHelper;

use PHPUnit\Framework\TestCase;

class ArrayHelperTest extends TestCase
{
	public function test()
	{
		$helper = new ArrayHelper([
			['value' => 1],
			['value' => 2],
			['value' => 3]
		]);

		$mapped = $helper->map(function ($item) {
			return $item['value'];
		})->get();
//		echo \json_encode($mapped) . PHP_EOL;
		$this->assertEquals(1, $mapped[0]);
		$this->assertEquals(2, $mapped[1]);
		$this->assertEquals(3, $mapped[2]);

		$reduced = $helper->reduce(0, function ($carry, $item) {
			return $carry + $item;
		})->get();
		$this->assertEquals(6, $reduced);
//		echo \json_encode($reduced) . PHP_EOL;
	}
}
