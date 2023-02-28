<?php
/**
 * @file
 * @version 0.2
 * @copyright 2023 CN-Consult GmbH
 * @author Jens Stahl <jens.stahl@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

namespace PBST\Commands;

use Symfony\Component\Console\Command\Command;
use PBST\ProfitBricksApi\ProfitBricksApi;
use Exception;

/**
 * Base-Class for all ProfitBricks-related commands.
 *
 * We inherit from our own base class instead direct use of symfony command because there are some lines of code
 * which has to be executed always. All commmands should use this class for a base class.
 *  - Implement method configure() with command-specific additions as needed
 *      - provide a valid name, check/parse of new input arguments, arbitrary setup code before running the command
 *  - Implement method execute() with what gets done if the command is triggered
 */
class CommandBase extends Command
{
	/** @var string Path to the config file */
	protected string $configFile = __DIR__.'/../config.ini';

	/** @var string[][] IniConfig-representation as 2-dimensional array [iniSection][keyName] -> value */
	protected array $config;

	/** @var ProfitBricksApi Reference to the ProfitBricks API which provides functions that are used in the commands */
	protected ProfitBricksApi $profitBricksApi;

	/**
	 * @inheritdoc
	 *
	 * @throws Exception Thrown if the config file is missing or the contained auth token is invalid.
	 *
	 * When implementing an own command, do NOT forget to call parent::configure() for having the basic things here done.
	 */
	protected function configure(): void
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
     * Calculates from GB to TB with german decimal sign style.
     *
     * @param int $_size
     * @return string Output format german decimal style with one digit behind comma.
     */
    protected function formatSize(int $_size): string
    {
        $size = ceil($_size / 100);
        $size = $size / 10;
        return str_replace('.',',',(string)$size);
    }
}
