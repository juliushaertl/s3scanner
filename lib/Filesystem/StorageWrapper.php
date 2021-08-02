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


namespace OCA\S3Scanner\Filesystem;

use OC\Files\Storage\Wrapper\Wrapper;

class StorageWrapper extends Wrapper {

	public function getUpdater($storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return new Updater($storage);
	}

	public function getPropagator($storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		if (!isset($storage->propagator)) {
			$storage->propagator = new PostponedPropagator($storage, \OC::$server->getDatabaseConnection());
		}
		return $storage->propagator;
	}


	public function getCache($path = '', $storage = null) {
		 if (!$storage) {
			 $storage = $this;
		 }
		 $cache = $this->storage->getCache($path, $storage);
		 return new CacheWrapper($cache);
	}
}
