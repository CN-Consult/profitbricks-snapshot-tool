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

use DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
use Exception;

/**
 * Class SnapshotListCommand
 *
 * This command lists all snapshots including their description and their size.
 */
class SnapshotListCommand extends CommandBase
{
    private DateTime $before;

    public function __construct()
    {
        parent::__construct();
        $this->before = new DateTime();
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName("snapshot:list")
            ->setDescription("Lists all snapshots from ProfitBricks!")
            ->addOption("before","b", InputOption::VALUE_REQUIRED, "lists all snapshot before this date time!", null);
    }

    /**
     * @throws Exception
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);
        if ($input->getOption("before")!==null)
        {
            if (strtotime($input->getOption("before")) === false)
                $output->writeln("Before must be a valid date time.");
            else
                $this->before = new DateTime($input->getOption("before"));
        }
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $snapShots = $this->profitBricksApi->snapshots();
        if ($snapShots!==false)
        {
            $io =  new SymfonyStyle($input, $output);
            $io->title("Snapshots");

            $rows = array();
            $sumSize = 0;
            $sumCount = 0;
            foreach ($snapShots as $snapShot)
            {
                $snapshotDate = $snapShot->createdDate;
                if ($snapshotDate<$this->before)
                {
                    $rows[] = array (
                        $snapShot->id,
                        $snapShot->name,
                        $snapShot->description,
                        $snapshotDate->format("d.m.Y H:i"),
                        $snapShot->size." GB",
                        $snapShot->state
                    );
                    $sumSize += (int)$snapShot->size;
                    $sumCount += 1;
                }
            }
            $sumSize = $this->formatSize($sumSize);
            $rows[] = new TableSeparator();
            $rows[] = array ("Counter:", $sumCount, "", "Total", $sumSize." TB", "");

            // this line should be used instead of all table commands. But I don't know how to STR_PAD_LEFT column 5!
            //$io->table(array("ID", "Name", "Description", "Date       Time", "Size", "State"), $rows);

            $table = new Table($output);
            $table->setHeaders(array("ID", "Name", "Description", "Date       Time", "Size", "State"));
            $table->addRows($rows);
            $tableStyle = new TableStyle();
            $colStyle = new TableStyle();
            $tableStyle->setVerticalBorderChar(" ");
            $tableStyle->setCrossingChar(" ");
            $table->setStyle($tableStyle);
            $colStyle->setPadType(STR_PAD_LEFT);
            $table->setColumnStyle(4, $colStyle);
            $table->render();
        }
    }
}
