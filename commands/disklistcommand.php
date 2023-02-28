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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;

/**
 * Class DiskListCommand
 *
 * Lists all virtual disks connected to a VM at ProfitBricks DataCenters.
 */
class DiskListCommand extends CommandBase
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName("disk:list")
            ->setDescription("Lists all disks from ProfitBricks!");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dataCenters = $this->profitBricksApi->dataCenters();

        $io =  new SymfonyStyle($input, $output);
        $io->title("List of virtual disks");
        $tableHeaders = array ("DataCenter", "VirtualHost", "VirtualDisk", "Size", "ID");
        $tableRows = array();
        $sumSize = 0;
        $sumCount = 0;
        foreach ($dataCenters as $dataCenter)
        {
            foreach ($this->profitBricksApi->virtualMachinesFor($dataCenter) as $virtualMachine)
            {
                foreach ($this->profitBricksApi->virtualDisks($virtualMachine, $dataCenter->id) as $virtualDisk)
                {
                    $tableRows[] = array ($dataCenter->name, $virtualMachine->name, $virtualDisk->name, $virtualDisk->size." GB", $virtualDisk->id);
                    $sumSize += (int)$virtualDisk->size;
                    $sumCount += 1;
                }
            }
        }
        $sumSize = $this->formatSize($sumSize);
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
