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

use ProfitBricksApi\Snapshot;
use ProfitBricksApi\VirtualMachine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use ProfitBricksApi\ProfitBricksApi;
use DateTime;
use Exception;

/**
 * Class SnapshotAutoDeleteCommand
 *
 * This class implements a simple automated deletion which is controlled by a time range defined in config.ini.
 */
class SnapshotAutoDeleteCommand extends Command
{
    private $config;
    /** @var  ProfitBricksApi $profitBricksApi */
    private $profitBricksApi;
    /** @var null|VirtualMachine[] $virtualMachines */
    private $virtualMachines = null;

    protected function configure()
    {
        $this->profitBricksApi = new ProfitBricksApi();
        $this
            ->setName("snapshot:autoDelete")
            ->setDescription("Deletes snapshots regarding to the configuration in config.ini!");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (is_readable('config.ini'))
        {
            $this->config = parse_ini_file('config.ini', true);
            if (!isset($this->config['api']['user']) || !isset($this->config['api']['password'])) throw new Exception("No user or no password configured to connect ProfitBricks!");
        }
        else throw new Exception("Error during reading config.ini!");
        $this->profitBricksApi->setUserName($this->config["api"]["user"]);
        $this->profitBricksApi->setPassword($this->config["api"]["password"]);

        $snapshots = $this->profitBricksApi->snapshots();
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
                    if (array_key_exists($virtualMachine->name, $this->config)) // check if config exists
                    {
                        $deletionDate = clone $snapshot->createdDate;
                        $deletionDate->add(new \DateInterval("P".$this->config[$virtualMachine->name]["deleteSnapshotsOlderThan"]."D"));
                        if ($snapshot->autoScriptCreated && $now>=$deletionDate)
                        {
                            if ($this->profitBricksApi->deleteSnapshot($snapshot->id))
                            {
                                $tableRows[] = array ($snapshot->id, $snapshot->name, $snapshot->description, $snapshot->createdDate->format("d.m.Y H:i"), $snapshot->size." GB", "deleted!");
                                $sumSize += (int)$snapshot->size;
                                $sumCount += 1;
                            }
                            else $tableRows[] = array ($snapshot->id, $snapshot->name, $snapshot->description, $snapshot->createdDate->format("d.m.Y H:i"), $snapshot->size." GB", "deletion failed!");
                        }
                        else $tableRows[] = array ($snapshot->id, $snapshot->name, $snapshot->description, $snapshot->createdDate->format("d.m.Y H:i"), $snapshot->size." GB", "deletion time: ".$deletionDate->format("d.m.Y H:i"));
                    }
                    else  $tableRows[] = array ($snapshot->id, $snapshot->name, $snapshot->description, $snapshot->createdDate->format("d.m.Y H:i"), $snapshot->size." GB", "no configuration for server ".$virtualMachine->name."!");
                }
                else  $tableRows[] = array ($snapshot->id, $snapshot->name, $snapshot->description, $snapshot->createdDate->format("d.m.Y H:i"), $snapshot->size." GB", "no valid server name!");
            }
            $sumSize = ceil($sumSize / 100);
            $sumSize = $sumSize / 10;
            $sumSize = str_replace('.',',',(string)$sumSize);
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

    private function getVirtualMachineFor(Snapshot $snapshot)
    {
        foreach ($this->virtualMachines as $virtualMachine)
        {
            if (strpos($snapshot->name, $virtualMachine->name)!==false)// && strpos($snapshot->description, $virtualMachine->name)!==false)
                return $virtualMachine;
        }
        return false;
    }

    /**
     * Get all VMs from ProfitBricks.
     */
    private function readVirtualMachinesOnce()
    {
        $virtualMachines = array();
        foreach ($this->profitBricksApi->dataCenters() as $dataCenter)
        {
            $virtualMachines = array_merge($virtualMachines, $this->profitBricksApi->virtualMachines($dataCenter));
        }
        $this->virtualMachines = $virtualMachines;
    }
}