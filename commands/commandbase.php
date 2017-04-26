<?php
/**
 * @file
 * @version 0.1
 * @copyright 2017 CN-Consult GmbH
 * @author Empty Dev <empty.dev@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

namespace PBST\Command;     // adapted to other classes but should not contain the PBST-part or add it everywhere

use Symfony\Component\Console\Command\Command;
use ProfitBricksApi\ProfitBricksApi;
use Exception;

/**
 * Base-Class for all ProfitBricks-related commands.
 *
 * Good place to locate a documentation (for API-Users) how the commands work and what needs to be do when adding a new.
 *  - Implement method configure() with command-specific additions as needed
 *      - provide a valid name, check/parse of new input arguments, arbitrary setup code before running the command
 *  - Implement method execute() with what gets done if the command is triggered
 */
class CommandBase extends Command
{
	/** @var string Path to the config file */
	protected $configFile = __DIR__.'/../config.ini';

	/** @var string[][] IniConfig-representation as 2-dimensional array [iniSection][keyName] -> value */
	protected $config;

	/** @var ProfitBricksApi Reference to the ProfitBricks API which provides functions that are used in the commands */
	protected $profitBricksApi;

	/**
	 * @inheritdoc
	 *
	 * @throws Exception Thrown if the config file is missing or the contained auth token is invalid.
	 *
	 * When implementing an own command, do NOT forget to call parent::configure() for having the basic things here done.
	 */
	protected function configure()
	{
		$this
			->setName("pbst:cmdBase")
			->setDescription("Provide some information about the command!");

		if (is_readable($this->configFile))
		{
			$this->config = parse_ini_file($this->configFile, true);
			if (!isset($this->config['api']['user']) || !isset($this->config['api']['password']))
			{
				throw new Exception("No user or no password configured to connect ProfitBricks!");
			}
		}
		else throw new Exception("Error during reading config at '".$this->configFile."''");

		$this->profitBricksApi = new ProfitBricksApi();
		$this->profitBricksApi->setUserName($this->config["api"]["user"]);
		$this->profitBricksApi->setPassword($this->config["api"]["password"]);
	}

	/**
	 * Transforms the provided sum size into a string format which is usable in result tables.
	 *
	 * @param int $_sumSize The summed up size which gets transformed to fit well in result tables.
	 * @return string The transformed size string usable in result tables.
	 */
	protected function niceSize($_sumSize)
	{
		$sumSize = ceil($_sumSize / 100);
		$sumSize = $sumSize / 10;

		return str_replace('.',',',(string)$sumSize);
	}
}

// _____________________________________________________________________________________________________________________
// Minimal demo code for a derived command
// _____________________________________________________________________________________________________________________
// imports are set automatically by IDE
//
class TestCommand extends CommandBase
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName("pbst:test")
			->setDescription("Child implementation using base functionality!");
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		foreach($this->profitBricksApi->dataCenters() as $dataCenter)
		{
			echo "DataCenter: ".$dataCenter->name."\n";
		}
	}
}