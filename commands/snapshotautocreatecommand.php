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
use PBST\ProfitBricksApi\DataCenter;
use PBST\ProfitBricksApi\Snapshot;
use DateTime;
use DateInterval;

/**
 * Class SnapshotAutoCreateCommand
 *
 * Creates automated snapshots from virtual servers which have been configured in config.ini.
 */
class SnapshotAutoCreateCommand extends CommandBase
{
    private array $virtualMachineState;

    public function __construct()
    {
        parent::__construct();
        $this->virtualMachineState = array();
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName("snapshot:autoCreate")
            ->setDescription("Creates a snapshot from all disks which are attached to a server when necessary.");
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var DataCenter[] $dataCenters */
        $dataCenters = $this->profitBricksApi->dataCenters();
        /** @var Snapshot[] $snapshots */
        $snapshots = $this->profitBricksApi->snapshots();

        $now = new DateTime();
        $io =  new SymfonyStyle($input, $output);
        $io->title("Snapshots automatic creation  ".$now->format("d.m.Y H:i"));

        $tableHeader = array ("DataCenter", "VirtualHost", "isConfigured", "last snapshot", "last by autoscript");
        $tableRows = array ();
        foreach ($dataCenters as $dataCenter)
        {
            foreach ($this->profitBricksApi->virtualMachinesFor($dataCenter) as $virtualMachine)
            {// only auto snapshot configured servers (virtual machines)
                if (isset($this->config[$virtualMachine->name]))
                {
                    $latestServerBackup = new DateTime("2080-01-01");
                    $latestServerBackupByScript = new DateTime("2080-01-01");
                    $virtualDisks = $this->profitBricksApi->virtualDisks($virtualMachine, $dataCenter->id);
                    foreach ($virtualDisks as $virtualDisk)
                    {
                        $latestDiskBackup = new DateTime("1980-01-01");
                        $latestDiskBackupByScript = new DateTime("1980-01-01");
                        foreach ($snapshots as $snapshot)
                        {
                            if (str_contains($snapshot->name, $virtualDisk->name) && str_contains($snapshot->name, $virtualMachine->name))
                            {
                                if ($snapshot->createdDate > $latestDiskBackup) $latestDiskBackup = $snapshot->createdDate;
                                if ($snapshot->autoScriptCreated && $snapshot->createdDate > $latestDiskBackupByScript) $latestDiskBackupByScript = $snapshot->createdDate;
                            }
                        }
                        if ($latestDiskBackup<$latestServerBackup) $latestServerBackup = $latestDiskBackup;
                        if ($latestDiskBackupByScript<$latestServerBackupByScript) $latestServerBackupByScript = $latestDiskBackupByScript;
                    }
                    $tableRows[] = array ($dataCenter->name, $virtualMachine->name, "YES  ".$this->config[$virtualMachine->name]["snapshotInterval"]." days", $latestServerBackup->format("d.m.Y"), $latestServerBackupByScript->format("d.m.Y"));
                    // check if backup script did a backup and if it is in time limit
                    $nextBackup = clone $latestServerBackupByScript;
                    $nextBackup->add(new DateInterval("P".$this->config[$virtualMachine->name]["snapshotInterval"]."D"));
                    $nextBackup->setTime(0,0,0);  //makes next backup only date depending
                    if (((int)$latestServerBackupByScript->format("Y") < 2000 && //never made a backup && startDay matches
                        ((strtolower($now->format("l"))==strtolower($this->config[$virtualMachine->name]["snapshotStartDay"]) ||
                        strtolower($now->format("D"))==strtolower($this->config[$virtualMachine->name]["snapshotStartDay"])))) ||
                        ((int)$latestServerBackupByScript->format("Y") > 2000 && $now >= $nextBackup)) //or backup interval matches
                    foreach ($virtualDisks as $virtualDisk)
                    {// make the snapshots
                        $snapshot = $this->profitBricksApi->makeSnapshot($dataCenter, $virtualMachine, $virtualDisk, "Auto-Script: ");
                        $this->virtualMachineState[$virtualMachine->id][$snapshot->id] = "initiated";
                        $tableRows[] = array ("", "Disk ".$virtualDisk->name, "", "", "done!");
                    }
                }

                else $tableRows[] = array ($dataCenter->name, $virtualMachine->name, "NO", "", "");
            }
        }
        $io->table($tableHeader, $tableRows);
        file_put_contents(__DIR__."/checker.sav", serialize($this->virtualMachineState));
    }
}
