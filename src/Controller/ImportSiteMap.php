<?php

namespace Task6\Sitemap\Controller;

use Task6\Sitemap\Model\Import;
use Task6\Sitemap\Model\ImportManager;
use Snowdog\DevTest\Model\PageManager;
use Snowdog\DevTest\Model\User;
use Snowdog\DevTest\Model\UserManager;
use Snowdog\DevTest\Model\WebsiteManager;

class ImportSiteMap
{
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
     * @param WebsiteManager $websiteManager
     * @param PageManager $pageManager
     * @param UserManager $userManager
     * @param ImportManager $importManager
     */
    public function __construct(
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

    public function execute()
    {
        try {
            if (isset($_SESSION['login'])) {
                $user = $this->userManager->getByLogin($_SESSION['login']);

                if (!isset($_FILES["uploadedFile"]["tmp_name"])) {
                    $_SESSION['flash'] = 'Please select file to import!';
                }

                $xmlfile = file_get_contents($_FILES["uploadedFile"]["tmp_name"]);
                $data = simplexml_load_string($xmlfile);
                $websites = json_decode(json_encode($data), true);
                $websites = end($websites);

                foreach ($websites as $website) {
                    if (isset($website["websiteUrl"]) && isset($website["websiteName"])) {
                        $added = $this->websiteManager->create($user, $website["websiteName"], $website["websiteUrl"]);
                    }
                }

                foreach ($websites as $website) {
                    if (isset($website["websitePage"]) && isset($website["website"])) {
                        $web = $this->importManager->getWebsiteByUrl($website["website"]);
                        if (isset($web->website_id)) {
                            $pages = $this->importManager->getPagesByUrl($website["websitePage"], $web->website_id);
                            if (count($pages) < 1) {
                                $pageAdded = $this->pageManager->create($web, $website["websitePage"]);
                            }
                        }
                    }
                }
                $_SESSION['flash'] = 'Website and pages import complete. If data is not showing check file format.';
            } else {
                $_SESSION['flash'] = 'Session is expired!';
            }


        } catch (\Exception $e) {
            $_SESSION['flash'] = 'Something went wrong: ' . $e->getMessage();
        }
        header('Location: /');
    }
}