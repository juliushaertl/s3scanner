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

namespace OCA\S3Scanner\AppInfo;

use OC\Files\Filesystem;
use OC\Files\ObjectStore\HomeObjectStoreStorage;
use OC\Files\Storage\Storage;
use OCA\S3Scanner\Filesystem\StorageWrapper;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Util;

class Application extends App implements IBootstrap {

	public function __construct() {
		parent::__construct('s3scanner', []);
	}

	public function register(IRegistrationContext $context): void {
		Util::connectHook('OC_Filesystem', 'preSetup', $this, 'addStorageWrapper');
	}

	public function boot(IBootContext $context): void {

	}

	public function addStorageWrapper() {
		Filesystem::addStorageWrapper(
		's3scanner', function (string $mountPoint, Storage $storage) {
			if ($storage->instanceOfStorage(HomeObjectStoreStorage::class)) {
				return new StorageWrapper([
					'storage' => $storage
				]);
			}

			return $storage;
		}
	);
}

}
