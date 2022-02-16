<?php

declare(strict_types=1);

namespace OCA\ShareImport\Command;

use OC\Core\Command\Base;
use OCP\Constants;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IShare;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Import extends Base {
	/** @var IRootFolder */
	private $rootFolder;
	private $shareManager;
	public function __construct(
		IRootFolder $rootFolder,
		ShareManager $shareManager
	) {
		parent::__construct();
		$this->rootFolder = $rootFolder;
		$this->shareManager = $shareManager;
	}
	protected function configure(): void {
		$this
			->setName('share_import:import')
			->setDescription('Import links')
			->addArgument(
				'csv',
				InputArgument::REQUIRED,
				'The CSV file to import'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$pathCsv = $input->getArgument('csv');
		if (($handle = fopen($pathCsv, 'r')) === false) {
			return 1;
		}
		$header = array_flip(fgetcsv($handle, 1000, ","));
		$rowNumber = 1;
		while ($row = fgetcsv($handle, 1000, ",")) {
			try {
				$userFolder = $this->rootFolder->getUserFolder($row[$header['user']]);
				$path = $userFolder->get($row[$header['path']]);
				try {
					$share = $this->shareManager->getShareByToken($row[$header['token']]);
				} catch (ShareNotFound $e) {
					$share = $this->shareManager->newShare();
				}
				$share->setNode($path);
				$this->lock($share->getNode());
				$share->setSharedBy($row[$header['user']]);
				if ($row[$header['password']]) {
					$share->setPassword($row[$header['password']]);
				}
				$share->setShareType(IShare::TYPE_LINK);
				$share->setPermissions(Constants::PERMISSION_READ);
				if (!$share->getId()) {
					$share = $this->shareManager->createShare($share);
				}
				if ($row[$header['token']]) {
					$share->setToken($row[$header['token']]);
				}
				$share = $this->shareManager->updateShare($share);
				$output->writeln('Row: ' . $rowNumber . '. Imported. Token: ' . $share->getToken());
			} catch (LockedException $e) {
				$output->writeln('<error>Row: ' . $rowNumber . '. Locked exception. json: ' . json_encode($row). '</error>');
			} catch (NotFoundException $e) {
				$output->writeln('<error>Row: ' . $rowNumber . '. Not found. json: ' . json_encode($row). '</error>');
			}
			$rowNumber++;
		}
		return 0;
	}

	private function lock(\OCP\Files\Node $node) {
		$node->lock(ILockingProvider::LOCK_SHARED);
		$this->lockedNode = $node;
	}
}
