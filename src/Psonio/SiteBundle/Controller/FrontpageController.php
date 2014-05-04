<?php

namespace Psonio\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Psonio\SiteBundle\Entity\Search;
use Psonio\SiteBundle\Form\Type\SearchFormType;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;

use Zend\Search\Lucene\Search\Query\Boolean;
use Zend\Search\Lucene\Index\Term;
use Zend\Search\Lucene\Search\Query\Wildcard;
use Zend\Search\Lucene\Search\Query\Term as QueryTerm;
use Zend\Search\Lucene\Search\QueryParser;

use URLify;

class FrontpageController extends Controller {
    /**
     * @Route("/", name="home")
     */
    public function frontpageAction() {
        $test = array();
        $searchForm = $this->createForm(new SearchFormType());
        return $this->render('PsonioSiteBundle::frontpage.html.twig', array(
            'searchForm' => $searchForm->createView(),
        ));
    }

    /**
     * @Route("/how_it_works", name="how_it_works")
     */
    public function howItWorks() {
        return $this->render('PsonioSiteBundle::how_it_works.html.twig', array());
    }

    /**
     * @Route("/search", name="search")
     */
    public function searchAction() {
        // Search by term
        $searchObj = new Search();
        $searchForm = $this->createForm(new SearchFormType(), $searchObj);
        $searchForm->bind($this->getRequest());
        if(!$searchForm->isValid()) {
            $flash = $this->get('braincrafted_bootstrap.flash');
            $flash->error($searchForm->getErrorsAsString());
            return new RedirectResponse($this->container->get('router')->generate('home'));
        }
        $query = new Boolean();
        QueryParser::setDefaultEncoding('utf-8');
        $product = QueryParser::parse(URLify::filter($searchObj->getProduct()));
        $query->addSubquery($product, true);
        if($searchObj->getArea() != null) {
            $areaBoolQuery = new Boolean();
            $wildcardArea = new Wildcard(new Term('*'.URLify::filter($searchObj->getArea()).'*'));
            $wildcardArea->setMinPrefixLength(0);
            $area = QueryParser::parse(URLify::filter($searchObj->getArea()));
            $areaBoolQuery->addSubquery($area);
            $areaBoolQuery->addSubquery($wildcardArea);
            $query->addSubquery($areaBoolQuery, true);
        }
        $search = $this->get('ewz_search.lucene.manager')->getIndex('eprices');
        $searchresults = $search->find($query, 'score', SORT_NUMERIC, SORT_DESC, 'price', SORT_NUMERIC, SORT_ASC);
        $adapter = new ArrayAdapter($searchresults);
        $searchresults = new Pagerfanta($adapter);
        // Paging options
        $searchresults->setNormalizeOutOfRangePages(true);
        $searchresults->setMaxPerPage(10); // 10 by default
        $searchresults->setCurrentPage($this->container->get('request')->get('page', 1));
        return $this->render('PsonioSiteBundle::results.html.twig', array(
            'search' => $searchObj,
            'results' => $searchresults,
            'paginator' => $searchresults,
        ));
    }

    /**
     * @Route("/ajax_search/{field}/{value}", name="ajax_search")
     */
    public function ajaxSearch($field, $value) {
        QueryParser::setDefaultEncoding('utf-8');
        $query = new Wildcard(new Term('*'.URLify::filter(urldecode($value)).'*', 's'.$field));
        $query->setMinPrefixLength(0);
        $search = $this->get('ewz_search.lucene.manager')->getIndex('eprices');
        $searchresults = $search->find($query, 'score', SORT_NUMERIC, SORT_DESC, 'price', SORT_NUMERIC, SORT_ASC);
        $adapter = new ArrayAdapter($searchresults);
        $searchresults = new Pagerfanta($adapter);
        // Paging options
        $searchresults->setNormalizeOutOfRangePages(true);
        $searchresults->setMaxPerPage(200); // 10 by default
        $searchresults->setCurrentPage($this->container->get('request')->get('page', 1));
        $return = array();
        foreach($searchresults as $curResult) {
            $curReturn['product'] = $curResult->product;
            $curReturn['store'] = $curResult->store;
            $curReturn['area'] = $curResult->area;
            $curReturn['price'] = $curResult->price;
            $return[] = $curReturn;
        }
        return new Response($this->container->get('jms_serializer')->serialize($return, 'json'));
    }
}
