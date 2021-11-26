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


namespace OCA\S3Scanner\Controller;


use OC\User\NoUserException;
use OCA\S3Scanner\Scanner;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ScanController extends OCSController {

	/** @var Scanner */
	private $scanner;

	public function __construct($appName, IRequest $request, Scanner $scanner, LoggerInterface $logger) {
		parent::__construct($appName, $request);
		$this->scanner = $scanner;
		$this->logger = $logger;
	}

	public function scanAll(string $userId, bool $propagateChanges = true): DataResponse {
		return $this->scan($userId, '', $propagateChanges);
	}

	public function scan(string $userId, string $path = '', bool $propagateChanges = true): DataResponse {
		$this->logger->debug('Start scanning for ' . $userId . ' with path: ' . $path);
		$counter = 0;
		@ini_set('output_buffering', '0');
		@header('X-Accel-Buffering: no');
		@ob_start();
		try {
			foreach ($this->scanner->scan($userId, $path, 0, 0, $propagateChanges) as $scan) {
				$this->logger->debug('Scanned path ' . $scan . ' for ' . $userId);
				// Sending empty characters in order to avoid load balancer timeouts
				echo ' ';
				$counter++;
				@ob_flush();
				@flush();
			}
		} catch (\Throwable $e) {
			\OC::$server->getLogger()->logException($e, [
				'app' => 's3scanner',
				'message' => 'Failed to scan "' . $path . '" for ' . $userId
			]);
			return new DataResponse([
				'status' => 'error',
				'processed' => $counter
			]);
		}
		$this->logger->debug('Finished scanning for ' . $userId . ' with path: ' . $path);
		return new DataResponse([
			'status' => 'success',
			'processed' => $counter
		]);
	}
}
