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


use OCP\IRequest;

class Helper {

	/** @var IRequest */
	private $request;

	public function __construct(IRequest $request) {
		$this->request = $request;
	}

	public function shouldPostpone(string $path): bool {
		if (strpos($path, 'uploads/') === 0 && basename($path) === '.target') {
			return true;
		}

		if ($this->request->getHeader('X-Chunking-Destination') !== "" && $path === 'uploads') {
			return true;
		}

		if ($this->request->getHeader('X-Postpone-Propagation') === "true") {
			return true;
		}

		return false;
	}
}
