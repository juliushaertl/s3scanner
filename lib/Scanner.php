<?php
/*
 * @copyright Copyright (c) 2021 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);


namespace OCA\S3Scanner;


use Generator;
use OC\Files\Filesystem;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OC\User\NoUserException;
use OCP\Files\Cache\ICache;
use OCP\Files\IRootFolder;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOrder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Output\OutputInterface;

class Scanner {

	/**
	 * @var IRootFolder
	 */
	private $rootFolder;
	/** @var IDBConnection */
	private $db;
	/**
	 * @var IUserManager
	 */
	private $userManager;
	/**
	 * @var OutputInterface
	 */
	private $output;

	public function __construct(IRootFolder $rootFolder, IDBConnection $db, IUserManager $userManager) {
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->db = $db;
	}

	public function setOutput(OutputInterface $output): void {
		$this->output = $output;
	}

	public function writeln(string $text): void {
		if ($this->output) {
			$this->output->writeln('<info>[Scanner] ' . $text . '</info>');
		}
	}

	public function iterateFolders(ICache $cache, IUser $user, string $path = '', int $limit = 0, int $offset = 0, $chunkingLimit = 1000): iterable {
		// allow iterating at the caller side
		if ($limit !== 0) {
			yield from $this->getCacheQuery($cache, $user, $path, $limit, $offset);
			return;
		}

		$chunkingOffset = 0;
		while($results = $this->getCacheQuery($cache, $user, $path, $chunkingLimit, $chunkingOffset)) {
			yield from $results;
			$chunkingOffset += $chunkingLimit;
		}
	}

	public function scan(string $userId, string $path = '', int $limit = 0, int $offset = 0, bool $propagateChanges = true): Generator {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new NoUserException();
		}

		$userFolder = $this->rootFolder->getUserFolder($userId);
		$storage = $userFolder->getStorage();
		$cache = $storage->getCache();

		$time = time();
		$etagTriggered = [];
		foreach ($this->iterateFolders($cache, $user, $path, $limit, $offset) as $result) {
			$internalPath = $result->getPath();
			$this->writeln($internalPath);

			// Files have the correct size set so we can just run the folder size correction once for each path starting from the deepest
			$cache->correctFolderSize($internalPath);
			$this->writeln('- corrected filesize');

			// e.g. /a/b/c/1 and /a/b/c/2 will both trigger the etag update on /a/b/c
			if (isset($etagTriggered[$internalPath]) || !$propagateChanges) {
				$this->writeln('- skipped propagation');
			} else {
				// We need to propagate an etag update for the found folder as well, therefore we add an extra fake filename to the path
				$this->writeln('- updater called');
				$storage->getUpdater()->update($internalPath . '/X', $time);
				$paths = explode('/', dirname($internalPath));
				$fullTriggeredPath = '';
				foreach ($paths as $triggeredPath) {
					$fullTriggeredPath .= $triggeredPath . '/';
					$etagTriggered[rtrim($fullTriggeredPath, '/')] = true;
				}
			}
			yield $internalPath;
		}
	}

	public function getCacheQuery(ICache $cache, IUser $user, string $path = '', int $limit = 0, int $offset = 0): array {
		$path = Filesystem::normalizePath('files/' . $path . '/');
		$basePath = $this->db->escapeLikeParameter(trim($path, '/')) . '/%';

		$mimeTypeComparison = new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', 'httpd/unix-directory');
		$pathExact = new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', trim($path, '/'));
		$pathLike = new SearchComparison(ISearchComparison::COMPARE_LIKE, 'path', $basePath);
		$pathOr = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [$pathExact, $pathLike]);
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [$mimeTypeComparison, $pathOr]);

		return $cache->searchQuery(new SearchQuery($operator, $limit, $offset, [
			new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'path'),
		], $user, true));
	}

}
