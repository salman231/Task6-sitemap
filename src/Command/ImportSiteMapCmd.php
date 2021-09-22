<?php

namespace Task6\Sitemap\Command;

use Snowdog\DevTest\Core\Database;
use Task6\Sitemap\Model\ImportManager;
use Snowdog\DevTest\Model\PageManager;
use Snowdog\DevTest\Model\User;
use Snowdog\DevTest\Model\UserManager;
use Snowdog\DevTest\Model\WebsiteManager;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ImportSiteMapCmd
{
    /**
     * @var QuestionHelper
     */
    private $questionHelper;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    /**
     * @var PageManager
     */
    private $pageManager;

    /**
     * @var ImportManager
     */
    private $importManager;

    /**
     * @param QuestionHelper $questionHelper
     * @param WebsiteManager $websiteManager
     * @param PageManager $pageManager
     * @param UserManager $userManager
     * @param ImportManager $importManager
     */
    public function __construct(
        QuestionHelper $questionHelper,
        WebsiteManager $websiteManager,
        PageManager $pageManager,
        UserManager $userManager,
        ImportManager $importManager
    ) {
        $this->questionHelper = $questionHelper;
        $this->userManager = $userManager;
        $this->websiteManager = $websiteManager;
        $this->pageManager = $pageManager;
        $this->importManager = $importManager;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __invoke(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln('<info>Importing Requires Login Credentials</info>');
            $login = $this->questionHelper->ask($input, $output, new Question('Enter Login: '));
            $password = $this->questionHelper->ask($input, $output, new Question('Enter Password: '));

            /** @var User $user */
            $user = $this->userManager->getByLogin($login);
            if($user) {
                if($this->userManager->verifyPassword($user, $password)) {
                    $question = new Question('Are you sure to import websites for this <comment>'.$login.'</comment>: <comment>yes/no</comment> ', 'no');
                    $start = $this->questionHelper->ask($input, $output, $question);

                    if ($start == "yes") {
                        $askForFilePath = new Question('Give file path to import data from: <comment>e.g: vendor/task6/sitemap/web/test.xml</comment> ');
                        $path = $this->questionHelper->ask($input, $output, $askForFilePath);
                        $output->writeln('<info>Reading file at path: </info><comment>'.$path.'</comment>');
                        $xmlfile = file_get_contents($path);
                        if ($xmlfile == false) {
                            $output->writeln('<error>Unable to load file or file not exist.</error>');
                            return;
                        }
                        $data = simplexml_load_string($xmlfile);
                        $data = json_decode(json_encode($data), true);
                        $websites = end($data);

                        foreach ($websites as $website) {
                            if (isset($website["websiteUrl"]) && isset($website["websiteName"])) {
                                $added = $this->websiteManager->create($user, $website["websiteName"], $website["websiteUrl"]);
                                if ($added == 0) {
                                    $output->writeln('<error>Unable to add website. Website "'. $website["websiteName"] .'"already Exist.</error>');
                                    $output->writeln('<comment>Continuing...</comment>');
                                    continue;
                                }
                            }
                        }

                        foreach ($websites as $website) {
                            if (isset($website["websitePage"]) && isset($website["website"])) {
                                $web = $this->importManager->getWebsiteByUrl($website["website"]);
                                if (isset($web->website_id)) {
                                    $pages = $this->importManager->getPagesByUrl($website["websitePage"], $web->website_id);
                                    if (count($pages) < 1) {
                                        $pageAdded = $this->pageManager->create($web, $website["websitePage"]);
                                        if ($pageAdded == 0) {
                                            $output->writeln('<error>Unable to add page "' . $website["websitePage"] . '" to website. Try Again</error>');
                                            $output->writeln('<comment>Continuing...</comment>');
                                            continue;
                                        }
                                    }
                                }
                            }
                        }
                        $output->writeln('<info>Website and pages import complete. If data is not showing check file format</info>');

                    } else {
                        $output->writeln('<info>Exiting...</info>');
                    }
                } else {
                    $output->writeln('<error>Wrong Username/Password!</error>');
                }
            } else {
                $output->writeln('<error>Wrong Username/Password!</error>');
            }
        } catch (\Exception $e) {
            $output->writeln('<error>Something went wrong: ' . $e->getMessage() . '</error>');
        }
    }
}