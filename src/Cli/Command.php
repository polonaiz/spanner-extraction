<?php


namespace SpannerExtractor\Cli;


interface Command
{
	/**
	 * @throws \Throwable
	 */
	public function run();
}