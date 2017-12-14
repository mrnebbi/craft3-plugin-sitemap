<?php
/**
 * sitemap plugin for Craft CMS 3.x
 *
 * Craft 3 plugin that provides an easy way to enable and manage a xml sitemap for search engines like Google
 *
 * @link      https://github.com/Dolphiq/craft3-plugin-sitemap
 * @copyright Copyright (c) 2017 Johan Zandstra
 */

namespace dolphiq\sitemap\controllers;

use dolphiq\sitemap\models\SitemapEntryModel;
use dolphiq\sitemap\records\SitemapEntry;
use dolphiq\sitemap\Sitemap;

use Craft;
use craft\db\Query;
use craft\web\Controller;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Johan Zandstra
 * @package   Sitemap
 * @since     1.0.0
 */
class SitemapController extends Controller
{
    private $_sourceRouteParams = [];
    protected $allowAnonymous = ['index'];
    // Public Methods
// =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/sitemap/default
     *
     * @return mixed
     */
    public function actionIndex()
    {
/*
        $response = Craft::$app->response;

        $xml = new \yii\web\XmlResponseFormatter;
        $xml->rootTag = 'urlset';
        $xml->itemTag = 'url';
        $xml->encoding = 'UTF-8';
        $xml->version = '1.0';
        $xml->useObjectTags = false;

        $response->format = 'sitemap_xml';
        $response->formatters['sitemap_xml'] = $xml;

// get all the urls!
        // $entries = SitemapEntry::find()->all();
        $urlItems = [];

        foreach($this->_createEntrySectionQuery()->all() as $item) {
            $urlItems[] = $item;
        }
        $response = $urlItems;

*/
        //set content type xml in response
        Craft::$app->response->format = \yii\web\Response::FORMAT_RAW;
        $headers = Craft::$app->response->headers;
        $headers->add('Content-Type', 'text/xml');

        $dom = new \DOMDocument('1.0','UTF-8');
        $dom->formatOutput = true;

        $urlset = $dom->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $dom->appendChild($urlset);


        foreach($this->_createEntrySectionQuery()->all() as $item) {

            $loc = rtrim($item['baseurl'], '/ ') . '/' . ($item['uri'] === '__home__' ? '' : $item['uri']);

            $url = $dom->createElement('url');
            $urlset->appendChild($url);
            $url->appendChild($dom->createElement('loc', $loc));
            $url->appendChild($dom->createElement('priority', $item['priority']));
            $url->appendChild($dom->createElement('changefreq', $item['changefreq']));

        }
        return $dom->saveXML();
    }

    private function _createEntrySectionQuery(): Query
    {
        return (new Query())
            ->select([
                'sections.id',
                'sections.structureId',
                'sections.name',
                'sections.handle',
                'sections.type',
                'sections.enableVersioning',
                'elements_sites.uri uri',
                'elements_sites.dateUpdated dateUpdated',
                'sites.baseurl',

                'sitemap_entries.id sitemapEntryId',
                'sitemap_entries.changefreq changefreq',
                'sitemap_entries.priority priority',
            ])
            ->innerJoin('{{%dolphiq_sitemap_entries}} sitemap_entries', '[[sections.id]] = [[sitemap_entries.linkId]] AND [[sitemap_entries.type]] = "section"')
            ->leftJoin('{{%structures}} structures', '[[structures.id]] = [[sections.structureId]]')
            ->innerJoin('{{%sections_sites}} sections_sites', '[[sections_sites.sectionId]] = [[sections.id]] AND [[sections_sites.hasUrls]] = 1')
            ->innerJoin('{{%entries}} entries', '[[sections.id]] = [[entries.sectionId]]')
            ->innerJoin('{{%elements}} elements', '[[entries.id]] = [[elements.id]] AND [[elements.enabled]] = 1')
            ->innerJoin('{{%elements_sites}} elements_sites', '[[elements_sites.elementId]] = [[elements.id]] AND [[elements_sites.enabled]] = 1')
            ->innerJoin('{{%sites}} sites', '[[elements_sites.siteId]] = [[sites.id]]')

            ->from(['{{%sections}} sections'])

            ->from(['{{%sections}} sections'])
            ->groupBy(['elements_sites.id'])
            ->orderBy(['type' => SORT_ASC],['name' => SORT_ASC]);
    }



}

?>