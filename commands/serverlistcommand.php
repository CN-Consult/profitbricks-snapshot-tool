<?php
/**
 * @file
 * @version 0.1
 * @copyright 2017 CN-Consult GmbH
 * @author Jens Stahl <jens.stahl@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

namespace PBST\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use PBST\ProfitBricksApi\ProfitBricksApi;
use Exception;

/**
 * Class ServerListCommand
 *
 * List all servers, you have created in ProfitBricks space.
 */
class ServerListCommand extends Command
{
    private $config;

    protected function configure()
    {
        $this
            ->setName("server:list")
            ->setDescription("Lists all servers from ProfitBricks!");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (is_readable('config.ini'))
        {
            $this->config = parse_ini_file('config.ini', true);
            if (!isset($this->config['api']['user']) || !isset($this->config['api']['password'])) throw new Exception("No user or no password configured to connect ProfitBricks!");
        }
        else throw new Exception("Error during reading config.ini!");
        $profitBricksApi = new ProfitBricksApi();
        $profitBricksApi->setUserName($this->config["api"]["user"]);
        $profitBricksApi->setPassword($this->config["api"]["password"]);

        $dataCenters = $profitBricksApi->dataCenters();
        $io =  new SymfonyStyle($input, $output);
        $io->title("Virtual Machines");
        $tableHeaders = array ("DataCenter", "ID", "VirtualHost");
        $tableColumns = array ();
        foreach ($dataCenters as $dataCenter)
        {
            foreach ($profitBricksApi->virtualMachinesFor($dataCenter) as $virtualMachine)
            {
                $tableColumns[] = array ($dataCenter->name, $virtualMachine->id, $virtualMachine->name);
            }
        }
        $io->table($tableHeaders, $tableColumns);
    }
}