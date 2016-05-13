<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace drafterbit\Bundle\CoreBundle\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand as BaseCommand;

/**
 * Command that places bundle web assets into a given directory.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Gábor Egyed <gabor.egyed@gmail.com>
 */
class AssetsInstallCommand extends BaseCommand
{
    const METHOD_COPY = 'copy';
    const METHOD_ABSOLUTE_SYMLINK = 'absolute symlink';
    const METHOD_RELATIVE_SYMLINK = 'relative symlink';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Installs bundles and themes web assets under a public web directory')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command installs bundle and themes assets into a given
directory (e.g. the <comment>web</comment> directory). This command override default symfony
<info>%command.name%</info> command.

  <info>php %command.full_name% web</info>

A "bundles" and "themes" directory will be created inside the target directory and the
"Resources/public" directory of each bundle will be copied into "bundles", "themes/<active_theme>/asset'"
will be copied into target path.

To create a symlink to each bundle instead of copying its assets, use the
<info>--symlink</info> option (will fall back to hard copies when symbolic links aren't possible:

  <info>php %command.full_name% web --symlink</info>

To make symlink relative, add the <info>--relative</info> option:

  <info>php %command.full_name% web --symlink --relative</info>

EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetArg = rtrim($input->getArgument('target'), '/');

        if (!is_dir($targetArg)) {
            throw new \InvalidArgumentException(sprintf('The target directory "%s" does not exist.', $input->getArgument('target')));
        }

        $this->filesystem = $this->getContainer()->get('filesystem');

        // Create the bundles directory otherwise symlink will fail.
        $bundlesDir = $targetArg.'/bundles/';
        $this->filesystem->mkdir($bundlesDir, 0777);

        $io = new SymfonyStyle($input, $output);
        $io->newLine();

        if ($input->getOption('relative')) {
            $expectedMethod = self::METHOD_RELATIVE_SYMLINK;
            $io->text('Trying to install assets as <info>relative symbolic links</info>.');
        } elseif ($input->getOption('symlink')) {
            $expectedMethod = self::METHOD_ABSOLUTE_SYMLINK;
            $io->text('Trying to install assets as <info>absolute symbolic links</info>.');
        } else {
            $expectedMethod = self::METHOD_COPY;
            $io->text('Installing assets as <info>hard copies</info>.');
        }

        $io->newLine();

        $rows = array();
        $copyUsed = false;
        $exitCode = 0;
        /** @var BundleInterface $bundle */
        foreach ($this->getContainer()->get('kernel')->getBundles() as $bundle) {
            if (!is_dir($originDir = $bundle->getPath().'/Resources/public')) {
                continue;
            }

            $targetDir = $bundlesDir.preg_replace('/bundle$/', '', strtolower($bundle->getName()));

            if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $message = sprintf("%s\n-> %s", $bundle->getName(), $targetDir);
            } else {
                $message = $bundle->getName();
            }

            try {
                $this->filesystem->remove($targetDir);

                if (self::METHOD_RELATIVE_SYMLINK === $expectedMethod) {
                    $method = $this->relativeSymlinkWithFallback($originDir, $targetDir);
                } elseif (self::METHOD_ABSOLUTE_SYMLINK === $expectedMethod) {
                    $method = $this->absoluteSymlinkWithFallback($originDir, $targetDir);
                } else {
                    $method = $this->hardCopy($originDir, $targetDir);
                }

                if (self::METHOD_COPY === $method) {
                    $copyUsed = true;
                }

                if ($method === $expectedMethod) {
                    $rows[] = array(sprintf('<fg=green;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94" /* HEAVY CHECK MARK (U+2714) */), $message, $method);
                } else {
                    $rows[] = array(sprintf('<fg=yellow;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'WARNING' : '!'), $message, $method);
                }
            } catch (\Exception $e) {
                $exitCode = 1;
                $rows[] = array(sprintf('<fg=red;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'ERROR' : "\xE2\x9C\x98" /* HEAVY BALLOT X (U+2718) */), $message, $e->getMessage());
            }
        }

        // @todo verify is it necessary to connect to database
        // during this operation
        $theme = $this->getContainer()->get('system')->get('theme.active', 'feather');
        $themesPath = $this->getContainer()->getParameter('themes_path');
        $themePath = $themesPath.'/'.$theme;
        $targetDir = $targetArg.'/themes/'.$theme.'/asset/';
        $originDir = $themePath.'/asset';

        try {
            $this->filesystem->remove($targetDir);
            $method = $this->absoluteSymlinkWithFallback($originDir, $targetDir);

            if (self::METHOD_COPY === $method) {
                $copyUsed = true;
            }

            if ($method === $expectedMethod) {
                $rows[] = array(sprintf('<fg=green;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94" /* HEAVY CHECK MARK (U+2714) */), $message, $method);
            } else {
                $rows[] = array(sprintf('<fg=yellow;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'WARNING' : '!'), $message, $method);
            }
        } catch (\Exception $e) {
            $exitCode = 1;
            $rows[] = array(sprintf('<fg=red;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'ERROR' : "\xE2\x9C\x98" /* HEAVY BALLOT X (U+2718) */), $message, $e->getMessage());
        }

        $io->table(array('', 'Bundle', 'Method / Error'), $rows);

        if (0 !== $exitCode) {
            $io->error('Some errors occurred while installing assets.');
        } else {
            if ($copyUsed) {
                $io->note('Some assets were installed via copy. If you make changes to these assets you have to run this command again.');
            }
            $io->success('All assets were successfully installed.');
        }

        return $exitCode;
    }

    /**
     * Try to create relative symlink.
     *
     * Falling back to absolute symlink and finally hard copy.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function relativeSymlinkWithFallback($originDir, $targetDir)
    {
        try {
            $this->symlink($originDir, $targetDir, true);
            $method = self::METHOD_RELATIVE_SYMLINK;
        } catch (IOException $e) {
            $method = $this->absoluteSymlinkWithFallback($originDir, $targetDir);
        }

        return $method;
    }

    /**
     * Try to create absolute symlink.
     *
     * Falling back to hard copy.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function absoluteSymlinkWithFallback($originDir, $targetDir)
    {
        try {
            $this->symlink($originDir, $targetDir);
            $method = self::METHOD_ABSOLUTE_SYMLINK;
        } catch (IOException $e) {
            // fall back to copy
            $method = $this->hardCopy($originDir, $targetDir);
        }

        return $method;
    }

    /**
     * Creates symbolic link.
     *
     * @param string $originDir
     * @param string $targetDir
     * @param bool   $relative
     *
     * @throws IOException If link can not be created.
     */
    private function symlink($originDir, $targetDir, $relative = false)
    {
        if ($relative) {
            $originDir = $this->filesystem->makePathRelative($originDir, realpath(dirname($targetDir)));
        }
        $this->filesystem->symlink($originDir, $targetDir);
        if (!file_exists($targetDir)) {
            throw new IOException(sprintf('Symbolic link "%s" was created but appears to be broken.', $targetDir), 0, null, $targetDir);
        }
    }

    /**
     * Copies origin to target.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function hardCopy($originDir, $targetDir)
    {
        $this->filesystem->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $this->filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));

        return self::METHOD_COPY;
    }
}