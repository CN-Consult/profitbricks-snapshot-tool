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
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use PBST\ProfitBricksApi\ProfitBricksApi;
use PBST\ProfitBricksApi\VirtualMachine;
use PBST\ProfitBricksApi\Snapshot;
use DateTime;
use Exception;

/**
 * Class SnapshotAutoDeleteCommand
 *
 * This class implements a simple automated deletion which is controlled by a time range defined in config.ini.
 */
class SnapshotAutoDeleteCommand extends CommandBase
{
    /** @var null|VirtualMachine[] $virtualMachines */
    private $virtualMachines = null;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName("snapshot:autoDelete")
            ->setDescription("Deletes snapshots regarding to the configuration in config.ini!");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $snapshots = $this->profitBricksApi->snapshots();
        $output->writeln("test");
        if ($snapshots != false && count($snapshots)>0)
        {
            $io =  new SymfonyStyle($input, $output);
            $io->title("Snapshots automatic deletion  ".date("d.m.Y H:i:s"));
            $tableRows = array ();
            $header = array ("ID", "Name", "Description", "Date       Time", "Size", "State");
            $now = new DateTime();
            $sumSize = 0;
            $sumCount = 0;
            $this->readVirtualMachinesOnce();
            foreach ($snapshots as $snapshot)
            {
                if ($virtualMachine = $this->getVirtualMachineFor($snapshot)) // don't use ==, because it is an valid assignment
                {
                    if (array_key_exists($virtualMachine->name, $this->config))
                    {
                        $deletionDate = clone $snapshot->createdDate;
                        $deletionDate->add(new \DateInterval("P".$this->config[$virtualMachine->name]["deleteSnapshotsOlderThan"]."D"));
                        if ($snapshot->autoScriptCreated && $now>=$deletionDate)
                        {
                            if ($this->profitBricksApi->deleteSnapshot($snapshot->id))
                            {
                                $snapshotSituation = "deleted!";
                                $sumSize += (int)$snapshot->size;
                                $sumCount += 1;
                            }
                            else $snapshotSituation = "deletion failed!";
                        }
                        else $snapshotSituation = "deletion time: ".$deletionDate->format("d.m.Y H:i");
                    }
                    else  $snapshotSituation = "no configuration for server ".$virtualMachine->name."!";
                }
                else $snapshotSituation = "no valid server name!";
                $tableRows[] = array ($snapshot->id, $snapshot->name, $snapshot->description, $snapshot->createdDate->format("d.m.Y H:i"), $snapshot->size." GB", $snapshotSituation);
            }
            $sumSize = $this->formatSize($sumSize);
            $tableRows[] = new TableSeparator();
            $tableRows[] = array("Counter:", $sumCount, "", "Total:", $sumSize." TB", "deleted");
            //$io->table($header, $tableRows);
            $table = new Table($output);
            $table->setHeaders($header);
            $table->addRows($tableRows);
            $tableStyle = new TableStyle();
            $colStyle = new TableStyle();
            $tableStyle->setVerticalBorderChar(" ");
            $tableStyle->setCrossingChar(" ");
            $table->setStyle($tableStyle);
            $colStyle->setPadType(STR_PAD_LEFT);
            $table->setColumnStyle(4, $colStyle);
            $table->render();
        }
        else throw new Exception("There are no snapshots at ProfitBricks!");
    }

    /**
     * @param Snapshot $snapshot The snapshot, for which a VM should be found.
     * @return bool|VirtualMachine False or the VM
     */
    private function getVirtualMachineFor(Snapshot $snapshot)
    {
        foreach ($this->virtualMachines as $virtualMachine)
        {
            if (strpos($snapshot->name, $virtualMachine->name)!==false)
                return $virtualMachine;
        }
        return false;
    }

    /**
     * Get all VMs from ProfitBricks into the class member.
     */
    private function readVirtualMachinesOnce()
    {
        $virtualMachines = array();
        foreach ($this->profitBricksApi->dataCenters() as $dataCenter)
        {
            $virtualMachines = array_merge($virtualMachines, $this->profitBricksApi->virtualMachinesFor($dataCenter));
        }
        $this->virtualMachines = $virtualMachines;
    }
}