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


namespace OCA\S3Scanner\Command;

use OCA\S3Scanner\Scanner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class User extends Command {

	/** @var Scanner */
	private $scanner;

	public function __construct(Scanner $scanner) {
		parent::__construct();
		$this->scanner = $scanner;
	}

	protected function configure() {
		$this->setName('s3scanner:scan')
			->setDescription('Scan a users directory')
			->addArgument(
				'user_id',
				InputArgument::REQUIRED,
				'will rescan all files of the given user'
			)
			->addArgument(
				'path',
				InputArgument::OPTIONAL,
				'path'
			);
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$user = $input->getArgument('user_id');
		$path = $input->getArgument('path') ?? '';
		$this->scanner->setOutput($output);
		$counter = 0;
		$startTime = microtime(true);
		foreach ($this->scanner->scan($user, $path) as $scan) {
			$output->writeln($scan);
			$counter++;
		}
		$duration = microtime(true) - $startTime;
		$output->writeln('Processed '. $counter . ' paths in ' .
			number_format($duration, 3, '.', '') . 's'
		);
		$output->writeln('Peak memory usage: ' . (memory_get_peak_usage()/1024/1024) . 'M');

		return 0;
	}
}
