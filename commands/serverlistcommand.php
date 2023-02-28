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

use Exception;
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
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName("server:list")
            ->setDescription("Lists all servers from ProfitBricks!");
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
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
