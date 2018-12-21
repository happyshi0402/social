<?php
declare(strict_types=1);


/**
 * Nextcloud - Social Support
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018, Maxence Lange <maxence@artificial-owl.com>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Social\Command;


use Exception;
use OC\Core\Command\Base;
use OCA\Social\Db\CoreRequestBuilder;
use OCA\Social\Service\ConfigService;
use OCA\Social\Service\MiscService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;


class Reset extends Base {


	private $coreRequestBuilder;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * CacheUpdate constructor.
	 *
	 * @param CoreRequestBuilder $coreRequestBuilder
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		CoreRequestBuilder $coreRequestBuilder, ConfigService $configService,
		MiscService $miscService
	) {
		parent::__construct();

		$this->coreRequestBuilder = $coreRequestBuilder;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('social:reset')
			 ->setDescription('Reset ALL data related to the Social App');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		$helper = $this->getHelper('question');
		$output->writeln(
			'<error>Beware, this operation will delete all content from the Social App.</error>'
		);
		$output->writeln('');
		$question = new ConfirmationQuestion(
			'<info>Do you confirm this operation?</info> (y/N) ', false, '/^(y|Y)/i'
		);
		if (!$helper->ask($input, $output, $question)) {
			return;
		}

		$question = new ConfirmationQuestion(
			'<info>Operation is destructive. Are you sure about this?</info> (y/N) ', false,
			'/^(y|Y)/i'
		);
		if (!$helper->ask($input, $output, $question)) {
			return;
		}

		$output->writeln('');
		$output->write('flushing data... ');
		try {
			$this->coreRequestBuilder->emptyAll();
			$output->writeln('<info>done</info>');
		} catch (Exception $e) {
			$output->writeln('<error>fail</error>');

			return;
		}

		$output->writeln('');

		$cloudAddress = $this->configService->getCloudAddress();
		$question = new Question(
			'<info>Now is a good time to change the base address of your cloud: </info> ('
			. $cloudAddress . ') ',
			$cloudAddress
		);

		$newCloudAddress = $helper->ask($input, $output, $question);

		if ($newCloudAddress === $cloudAddress) {
			return;
		}

		$this->configService->setCloudAddress($newCloudAddress);
		$output->writeln('');
		$output->writeln('New address: <info>' . $newCloudAddress . '</info>');
	}


}
