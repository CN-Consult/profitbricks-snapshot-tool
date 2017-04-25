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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use ProfitBricksApi\ProfitBricksApi;
use Exception;

/**
 * Class SnapshotCheckerCommand
 *
 * This command verifies the snapshots have been taken successful and sends an email to a configured address.
 */
class SnapshotCheckerCommand extends Command
{
    private $config;
    private $virtualMachines = null;

    protected function configure()
    {
        $this
            ->setName("snapshot:checker")
            ->setDescription("Checks, if all snapshots of a server have been done and send success notification.");
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
        /** @var Snapshot[] $snapshots */
        $snapshots = $profitBricksApi->snapshots();

        // read from snapshot status file, which is created (and overwritten) by snapshot:autoCreate
        $fileContent = file_get_contents(getcwd()."/checker.sav");
        if ($fileContent===false) throw new Exception("Could not read file 'checker.sav'!");
        $virtualMachineState = unserialize($fileContent);
        if (count($virtualMachineState)>0) {
            $io = new SymfonyStyle($input, $output);
            $io->title("Snapshot checker  ".date("d.m.Y H:i:s"));
            $tableRows = array();
            $this->readVirtualMachines($profitBricksApi);
            foreach ($virtualMachineState as $virtualMachineId => $snapshotStates) {
                $valueChanged = false;
                $available = true;
                $snapshotDates = "";
                foreach ($snapshotStates as $snapshotId => $snapshotState) {
                    if (array_key_exists($snapshotId, $snapshots) && $snapshotState != $snapshots[$snapshotId]->state) {
                        $virtualMachineState[$virtualMachineId][$snapshotId] = $snapshots[$snapshotId]->state;
                        $valueChanged = true;
                    }
                    if ($virtualMachineState[$virtualMachineId][$snapshotId] != "AVAILABLE") $available = false;
                    else if (array_key_exists($snapshotId, $snapshots)) $snapshotDates .= $snapshots[$snapshotId]->createdDate->format("Y-m-d H:i") . "; ";
                }
                if ($valueChanged && $available) {
                    $action = "mail sent!";
                    $this->sendMailForSnapshotVMs($virtualMachineId, $this->virtualMachines[$virtualMachineId]->name, $snapshotDates);
                } else $action = "nothing to do!";
                $tableRows[] = array($this->virtualMachines[$virtualMachineId]->name, $action);
            }

            $io->table(array("Server", "Action"), $tableRows);

            file_put_contents(getcwd() . "/checker.sav", serialize($virtualMachineState));
        }
    }

    /**
     * @param $_virtualMachineId
     * @param $_virtualMachineName
     * @param $_backupDates
     * @throws Exception
     */
    private function sendMailForSnapshotVMs($_virtualMachineId, $_virtualMachineName, $_backupDates)
    {
        $receiver =  $this->config["mail"]["to"];
        $subject = $_virtualMachineName . " snapshot success";
        $message = "ProfitBricks Snapshot has been made today!\r\n";
        $message .= "Server: " . $_virtualMachineName . "\r\n" .
            "ID: " . $_virtualMachineId . "\r\n" .
            "LastBackups: " . $_backupDates . "\r\n";
        $headers = "From: ". $this->config["mail"]["from"];
        if (!mail($receiver, $subject, $message, $headers)) throw new Exception("Error during sending email to $receiver!");
    }

    /**
     * Provides all information of VMs (servers) in class property.
     *
     * @param ProfitBricksApi $_profitBricksApi
     */
    private function readVirtualMachines(ProfitBricksApi &$_profitBricksApi)
    {
        $virtualMachines = array();
        foreach ($_profitBricksApi->dataCenters() as $dataCenter)
        {
            $virtualMachines = array_merge($virtualMachines, $_profitBricksApi->virtualMachines($dataCenter));
        }
        $this->virtualMachines = $virtualMachines;
    }
}