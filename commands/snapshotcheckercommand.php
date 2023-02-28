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
use Exception;

/**
 * Class SnapshotCheckerCommand
 *
 * This command verifies the snapshots have been taken successful and sends an email to a configured address.
 */
class SnapshotCheckerCommand extends CommandBase
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName("snapshot:checker")
            ->setDescription("Checks, if all snapshots of a server have been done and send success notification.");
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $snapshots = $this->profitBricksApi->snapshots();

        // read from snapshot status file, which is created (and overwritten) by snapshot:autoCreate
        $fileContent = file_get_contents(__DIR__."/checker.sav");
        if ($fileContent===false) throw new Exception("Could not read file 'checker.sav'!");
        $virtualMachineState = unserialize($fileContent);
        if (count($virtualMachineState)>0) {
            $io = new SymfonyStyle($input, $output);
            $io->title("Snapshot checker  ".date("d.m.Y H:i:s"));
            $tableRows = array();
            $virtualMachines = $this->profitBricksApi->allVirtualMachines();
            foreach ($virtualMachineState as $virtualMachineId => $snapshotStates) {
                $valueChanged = false;
                $available = true;
                $snapshotDateTimes = "";
                foreach ($snapshotStates as $snapshotId => $snapshotState) {
                    if (array_key_exists($snapshotId, $snapshots) && $snapshotState != $snapshots[$snapshotId]->state)
                    {
                        $virtualMachineState[$virtualMachineId][$snapshotId] = $snapshots[$snapshotId]->state;
                        $valueChanged = true;
                    }
                    if ($virtualMachineState[$virtualMachineId][$snapshotId] != "AVAILABLE") $available = false;
                    else if (array_key_exists($snapshotId, $snapshots))
                        $snapshotDateTimes .= $snapshots[$snapshotId]->createdDate->format("Y-m-d H:i") . "; ";
                }
                if ($valueChanged && $available)
                {
                    $action = "mail sent!";
                    $this->sendMailForSnapshotVMs($virtualMachineId, $virtualMachines[$virtualMachineId]->name, $snapshotDateTimes);
                }
                else $action = "nothing to do!";
                $tableRows[] = array($virtualMachines[$virtualMachineId]->name, $action);
            }
            $io->table(array("Server", "Action"), $tableRows);
            file_put_contents(__DIR__ . "/checker.sav", serialize($virtualMachineState));
        }
    }

    /**
     * @param string $_virtualMachineId ID of the VM for which a mail has to be sent
     * @param string $_virtualMachineName Name of the VM for which a mail has to be sent
     * @param string $_backupDateTimes DateTimess of the last successfully backups/snapshots
     * @throws Exception
     */
    private function sendMailForSnapshotVMs(string $_virtualMachineId,
                                            string $_virtualMachineName,
                                            string $_backupDateTimes): void
    {
        $receiver =  $this->config["mail"]["to"];
        $subject = $_virtualMachineName . " snapshot success";
        $message = "ProfitBricks Snapshot has been made today!\r\n";
        $message .= "Server: " . $_virtualMachineName . "\r\n" .
            "ID: " . $_virtualMachineId . "\r\n" .
            "LastBackups: " . $_backupDateTimes . "\r\n";
        $headers = "From: ". $this->config["mail"]["from"];
        if (!mail($receiver, $subject, $message, $headers))
            throw new Exception("Error during sending email to $receiver!");
    }
}
