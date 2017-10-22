<?php declare(strict_types=1);

namespace Rector\Console\Command;

use Rector\Application\FileProcessor;
use Rector\Exception\NoRectorsLoadedException;
use Rector\FileSystem\PhpFilesFinder;
use Rector\Naming\CommandNaming;
use Rector\Printer\ChangedFilesCollector;
use Rector\Rector\RectorCollector;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @todo decouple report methods to output ... ProcessCommandReporter
 */
final class ProcessCommand extends Command
{
    /**
     * @var string
     */
    private const ARGUMENT_SOURCE_NAME = 'source';

    /**
     * @var int
     */
    private const MAX_FILES_TO_PRINT = 30;

    /**
     * @var FileProcessor
     */
    private $fileProcessor;

    /**
     * @var RectorCollector
     */
    private $rectorCollector;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var PhpFilesFinder
     */
    private $phpFilesFinder;

    /**
     * @var ChangedFilesCollector
     */
    private $changedFilesCollector;

    public function __construct(
        FileProcessor $fileProcessor,
        RectorCollector $rectorCollector,
        SymfonyStyle $symfonyStyle,
        PhpFilesFinder $phpFilesFinder,
        ChangedFilesCollector $changedFilesCollector
    ) {
        $this->fileProcessor = $fileProcessor;
        $this->rectorCollector = $rectorCollector;
        $this->symfonyStyle = $symfonyStyle;
        $this->phpFilesFinder = $phpFilesFinder;
        $this->changedFilesCollector = $changedFilesCollector;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription('Reconstruct set of your code.');
        $this->addArgument(
            self::ARGUMENT_SOURCE_NAME,
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'The path(s) to be checked.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = $input->getArgument(self::ARGUMENT_SOURCE_NAME);

        $this->ensureSomeRectorsAreRegistered();

        $files = $this->phpFilesFinder->findInDirectories($source);

        $this->reportLoadedRectors();

        $this->symfonyStyle->title('Processing files');

        $i = 0;
        foreach ($files as $file) {
            $this->reportLoadedFile($i, $file, count($files));
            $this->fileProcessor->processFile($file);

            ++$i;
        }

        $this->reportChangedFiles();

        $this->symfonyStyle->success('Rector is done!');

        return 0;
    }

    private function ensureSomeRectorsAreRegistered(): void
    {
        if ($this->rectorCollector->getRectorCount() > 0) {
            return;
        }

        throw new NoRectorsLoadedException(
            'No rector were found. Registers them in rector.yml config to "rector:" '
            . 'section or load them via "--config <file>.yml" CLI option.'
        );
    }

    private function reportLoadedRectors(): void
    {
        $this->symfonyStyle->title(sprintf(
            '%d Loaded Rector%s',
            $this->rectorCollector->getRectorCount(),
            $this->rectorCollector->getRectorCount() === 1 ? '' : 's'
        ));

        foreach ($this->rectorCollector->getRectors() as $rector) {
            $this->symfonyStyle->writeln(sprintf(
                ' - %s',
                get_class($rector)
            ));
        }

        $this->symfonyStyle->newLine();
    }

    private function reportChangedFiles(): void
    {
        $this->symfonyStyle->title(sprintf(
            '%d Changed File%s',
            $this->changedFilesCollector->getChangedFilesCount(),
            $this->changedFilesCollector->getChangedFilesCount() === 1 ? '' : 's'
        ));

        foreach ($this->changedFilesCollector->getChangedFiles() as $fileInfo) {
            $this->symfonyStyle->writeln(sprintf(
                ' - %s',
                $fileInfo
            ));
        }
    }

    private function reportLoadedFile(int $i, SplFileInfo $fileInfo, int $fileCount): void
    {
        if ($i < self::MAX_FILES_TO_PRINT) {
            $this->symfonyStyle->writeln(sprintf(
                ' - %s',
                $fileInfo
            ));
        }

        if ($i === self::MAX_FILES_TO_PRINT) {
            $this->symfonyStyle->newLine();
            $this->symfonyStyle->writeln(sprintf(
                '...and %d more.',
                $fileCount - self::MAX_FILES_TO_PRINT
            ));
            $this->symfonyStyle->newLine();
        }
    }
}
