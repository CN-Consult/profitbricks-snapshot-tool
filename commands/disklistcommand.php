<?php
/**
 * @file
 * @version 0.1
 * @copyright 2017 CN-Consult GmbH
 * @author Jens Stahl <jens.stahl@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

namespace PBST\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
use ProfitBricksApi\ProfitBricksApi;
use Exception;

/**
 * Class DiskListCommand
 *
 * Lists all virtual disks connected to a VM at ProfitBricks DataCenters.
 */
class DiskListCommand extends Command
{
    private $config;

    protected function configure()
    {
        $this
            ->setName("disk:list")
            ->setDescription("Lists all disks from ProfitBricks!");
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
        $io->title("List of virtual disks");
        $tableHeaders = array ("DataCenter", "VirtualHost", "VirtualDisk", "Size", "ID");
        $tableRows = array();
        $sumSize = 0;
        $sumCount = 0;
        foreach ($dataCenters as $dataCenter)
        {
            foreach ($profitBricksApi->virtualMachines($dataCenter) as $virtualMachine)
            {
                foreach ($profitBricksApi->virtualDisks($virtualMachine, $dataCenter->id) as $virtualDisk)
                {
                    $tableRows[] = array ($dataCenter->name, $virtualMachine->name, $virtualDisk->name, $virtualDisk->size." GB", $virtualDisk->id);
                    $sumSize += (int)$virtualDisk->size;
                    $sumCount += 1;
                }
            }
        }
        $sumSize = ceil($sumSize / 100);
        $sumSize = $sumSize / 10;
        $sumSize = str_replace('.',',',(string)$sumSize);
        $tableRows[] = new TableSeparator();
        $tableRows[] = array ("Counter:", $sumCount, "Total:", $sumSize." TB", "");

        $table = new Table($output);
        $table->setHeaders($tableHeaders);
        $table->addRows($tableRows);
        $tableStyle = new TableStyle();
        $colStyle = new TableStyle();
        $tableStyle->setVerticalBorderChar(" ");
        $tableStyle->setCrossingChar(" ");
        $table->setStyle($tableStyle);
        $colStyle->setPadType(STR_PAD_LEFT);
        $table->setColumnStyle(3, $colStyle);
        $table->render();
    }
}