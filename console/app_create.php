<?php
/**
 * i18n:generate command for Skeleton Console
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine;
use Symfony\Component\Console\Question\Question;

class Core_App_Create extends \Skeleton\Console\Command {

	protected $twig_extractor = null;

	/**
	 * Configure the Create command
	 *
	 * @access protected
	 */
	protected function configure() {
		$this->setName('core:app:create');
		$this->addArgument('name', InputArgument::REQUIRED, 'The name of the app');
		$this->setDescription('Create a skeleton app');
	}

	/**
	 * Execute the Command
	 *
	 * @access protected
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$name = $input->getArgument('name');

		$application = null;
		try {
			$application = \Skeleton\Core\Application::get_by_name($name);
		} catch (\Exception $e) { }
		if ($application !== null) {
			$output->writeln('<error>Application with name ' . $name . ' already exists' . '</error>');			
			return 1;
		}

		$dialog = $this->getHelper('question');
		$question = new Question('Give the hostname(s) for the application, when providing multiple hostnames seperate them with a space: ');
		$hostnames = $dialog->ask($input, $output, $question);
		$hostnames = explode(' ', $hostnames);		
		
		$settings = [
			'hostnames' => $hostnames,
		];

		try {
			$application = \Skeleton\Core\Application::create($name, $settings);
			$output->writeln('New application created: ' . $name );		
			return 0;		
		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 1;
		}
	}

}
