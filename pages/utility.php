<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


// menuId  8: Utilities g_initPath()
//  tabId  1: Tools     g_initHeader()
class UtilityPage extends GenericPage
{
    protected $tpl           = 'list-page-generic';
    protected $path          = [1, 8];
    protected $tabId         = 1;
    protected $mode          = CACHETYPE_NONE;
    protected $validPages    = array(
        'latest-additions',       'latest-articles',       'latest-comments',       'latest-screenshots',  'random',
        'unrated-comments', 11 => 'latest-videos',   12 => 'most-comments',   13 => 'missing-screenshots'
    );
    private $page            = '';
    private $rss             = false;

    public function __construct($pageCall, $pageParam)
    {
        $this->getCategoryFromUrl($pageParam);

        parent::__construct($pageCall, $pageParam);

        $this->page = $pageCall;
        $this->rss  = isset($_GET['rss']);
        $this->name = Lang::$main['utilities'][array_search($pageCall, $this->validPages)];

        if ($this->page == 'most-comments')
        {
            if ($this->category && in_array($this->category[0], [7, 30]))
                $this->name .= Lang::$main['colon'] . sprintf(Lang::$main['mostComments'][1], $this->category[0]);
            else
                $this->name .= Lang::$main['colon'] . Lang::$main['mostComments'][0];
        }
    }

    public function display($override = '')
    {
        if ($this->rss)                                     // this should not be cached
        {
            header('Content-Type: application/rss+xml; charset=ISO-8859-1');
            die($this->generateRSS());
        }
        else
            return parent::display($override);
    }

    protected function generateContent()
    {
        /****************/
        /* Main Content */
        /****************/

        if (in_array(array_search($this->page, $this->validPages), [0, 1, 2, 3, 11, 12]))
            $this->h1Links = '<small><a href="?'.$this->page.($this->category ? '='.$this->category[0] : null).'&rss" class="icon-rss">'.Lang::$main['subscribe'].'</a></small>';

        switch ($this->page)
        {
            case 'random':
                $type   = array_rand(array_filter(Util::$typeStrings));
                $typeId = (new Util::$typeClasses[$type](null))->getRandomId();

                header('Location: ?'.Util::$typeStrings[$type].'='.$typeId);
                die();
            case 'latest-comments':
                $this->lvData[] = array(
                    'file'   => 'commentpreview',
                    'data'   => [],
                    'params' => []
                );
                break;
            case 'latest-screenshots':
                $this->lvData[] = array(
                    'file'   => 'screenshot',
                    'data'   => [],
                    'params' => []
                );
                break;
            case 'latest-videos':
                $this->lvData[] = array(
                    'file'   => 'video',
                    'data'   => [],
                    'params' => []
                );
                break;
            case 'latest-articles':
                $this->lvData = [];
                break;
            case 'latest-additions':
                $extraText = '';
                break;
            case 'unrated-comments':
                $this->lvData[] = array(
                    'file'   => 'commentpreview',
                    'data'   => [],
                    'params' => []
                );
                break;
            case 'missing-screenshots':
                $cnd = [[['cuFlags', CUSTOM_HAS_SCREENSHOT, '&'], 0]];

                if (!User::isInGroup(U_GROUP_EMPLOYEE))
                    $cnd[] = [['cuFlags', CUSTOM_EXCLUDE_FOR_LISTVIEW, '&'], 0];

                foreach (Util::$typeClasses as $classStr)
                {
                    if (!$classStr)
                        continue;

                    $typeObj = new $classStr($cnd);
                    if (!$typeObj->error)
                    {
                        $this->extendGlobalData($typeObj->getJSGlobals(GLOBALINFO_SELF | GLOBALINFO_RELATED | GLOBALINFO_REWARDS));
                        $this->lvData[] = array(
                            'file'   => $typeObj::$brickFile,
                            'data'   => $typeObj->getListviewData(),
                            'params' => ['tabs' => '$myTabs']
                        );
                    }
                }
                break;
            case 'most-comments':
                if ($this->category && !in_array($this->category[0], [1, 7, 30]))
                    header('Location: ?most-comments=1'.($this->rss ? '&rss' : null));

                $this->lvData[] = array(
                    'file'   => 'commentpreview',
                    'data'   => [],
                    'params' => []
                );
                break;
        }
    }

    protected function generateRSS()
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
            "<rss version=\"2.0\">\n\t<channel>\n".
            "\t\t<title>".CFG_NAME_SHORT.' - '.$this->name."</title>\n".
            "\t\t<link>".HOST_URL.'?'.$this->page . ($this->category ? '='.$this->category[0] : null)."</link>\n".
            "\t\t<description>".CFG_NAME."</description>\n".
            "\t\t<language>".implode('-', str_split(User::$localeString, 2))."</language>\n".
            "\t\t<ttl>".CFG_TTL_RSS."</ttl>\n".
            "\t\t<lastBuildDate>".date(DATE_RSS)."</lastBuildDate>\n".
            "\t</channel>\n";

        # generate <item>'s here

        $xml .= '</rss>';

        return $xml;
    }

    protected function generateTitle()
    {
        if ($this->page == 'most-comments')
        {
            if ($this->category && in_array($this->category[0], [7, 30]))
                array_unshift($this->title, sprintf(Lang::$main['mostComments'][1], $this->category[0]));
            else
                array_unshift($this->title, Lang::$main['mostComments'][0]);
        }

        array_unshift($this->title, Lang::$main['utilities'][array_search($this->page, $this->validPages)]);
    }

    protected function generatePath()
    {
        $this->path[] = array_search($this->page, $this->validPages);

        if ($this->page == 'most-comments')
        {
            if ($this->category && in_array($this->category[0], [7, 30]))
                $this->path[] = $this->category[0];
            else
                $this->path[] = 1;
        }
    }
}

?>
