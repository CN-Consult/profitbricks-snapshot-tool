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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ServerListCommand
 *
 * List all servers, you have created in ProfitBricks space.
 */
class ServerListCommand extends CommandBase
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName("server:list")
            ->setDescription("Lists all servers from ProfitBricks!");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dataCenters = $this->profitBricksApi->dataCenters();
        $io =  new SymfonyStyle($input, $output);
        $io->title("Virtual Machines");
        $tableHeaders = array ("DataCenter", "ID", "VirtualHost");
        $tableColumns = array ();
        foreach ($dataCenters as $dataCenter)
        {
            foreach ($this->profitBricksApi->virtualMachinesFor($dataCenter) as $virtualMachine)
            {
                $tableColumns[] = array ($dataCenter->name, $virtualMachine->id, $virtualMachine->name);
            }
        }
        $io->table($tableHeaders, $tableColumns);
    }
}