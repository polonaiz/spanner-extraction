<?php

namespace ArrayHelper;

class ArrayHelper
{
	/**
	 * @var mixed
	 */
	private $data;

	public function __construct($data)
	{
		$this->data = $data;
	}

	/**
	 * @param callable($item) $callable
	 * @return ArrayHelper
	 */
	public function map(callable $callable)
	{
		$this->data = \array_map($callable, $this->data);
		return $this;
	}

	/**
	 * @param mixed $initial
	 * @param callable($carry, $item) $callable
	 * @return ArrayHelper
	 */
	public function reduce($initial, callable $callable)
	{
		$this->data = \array_reduce($this->data, $callable, $initial);
		return $this;
	}

	public function implode($glue)
	{
		$this->data = \implode($glue, $this->data);
		return $this;
	}

	public function get()
	{
		return $this->data;
	}
}