<?php
namespace Psonio\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use EWZ\Bundle\SearchBundle\Lucene\LuceneSearch;
use EWZ\Bundle\SearchBundle\Lucene\Document;
use EWZ\Bundle\SearchBundle\Lucene\Field;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DomCrawler\Crawler;
use URLify;

class BuildIndexCommand extends ContainerAwareCommand
{
    protected $_stack = array();
    protected $_file = "";
    protected $_parser = null;

    protected $_current = "";
    protected $i = 0;

    protected function configure()
    {

        $this
            ->setName('psonio:buildindex')
            ->setDescription('Build search index')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting BuildIndex process');
        $this->container = $this->getContainer();
        // Delete all items from the index first
        $this->search = $this->container->get('ewz_search.lucene.manager')->getIndex('eprices');
        $query = '+(saved:yes)';
        $results = $this->search->find($query);
        foreach($results as $curResult) {
            $this->search->getIndex()->delete($curResult);
        }

        // Add all pages to the index
        $em = $this->container->get('doctrine')->getManager();
        $finder = new Finder();
        $finder->files()->in($this->container->getParameter('kernel.root_dir').'/../data');

        foreach ($finder as $file) {
            $this->_parser = xml_parser_create("UTF-8");
            xml_set_object($this->_parser, $this);
            xml_set_element_handler($this->_parser, "startTag", "endTag");

            $this->parse($file->getRealpath());

            //$this->container->get('kp.page.listener.prepersist')->addToIndex($curPage);
        }
        $this->search->updateIndex();
        $output->writeln('The search index was built successfully');
    }

    public function startTag($parser, $name, $attribs)
    {
        array_push($this->_stack, $this->_current);

        if ($name == "DETAIL" && count($attribs)) {
            $this->addToIndex($attribs);
        } else {
            var_dump($name);
        }

        $this->_current = $name;
    }

    public function endTag($parser, $name)
    {
        $this->_current = array_pop($this->_stack);
    }

    public function parse($file)
    {
        $fh = fopen($file, "r");
        if (!$fh) {
            die("Could not open file!\n");
        }

        while (!feof($fh)) {
            $data = fread($fh, 4096);
            xml_parse($this->_parser, $data, feof($fh));
        }
    }

    private function addToIndex($attribs) {
        $document = new Document();
        $document->addField(Field::keyword('key', $this->i++));
        $document->addField(Field::binary('area', $attribs['Νομός___Δήμος']));
        $document->addField(Field::binary('store', $attribs['Ίατάστημα']));
        $document->addField(Field::binary('product', $attribs['Όνομα_Προϊόντος']));
        $document->addField(Field::text('sarea', URLify::filter($attribs['Νομός___Δήμος'])));
        $document->addField(Field::text('sstore', URLify::filter($attribs['Ίατάστημα'])));
        $document->addField(Field::text('sproduct', URLify::filter($attribs['Όνομα_Προϊόντος'])));
        $document->addField(Field::binary('price', $attribs['Τιμή']));
        // A universal parameter to be able to fetch all pages
        $document->addField(Field::text('saved','yes'));
        $this->search->addDocument($document);
    }
}

function mb_trim($str) {
  return preg_replace("/(^\s+)|(\s+$)/us", "", $str);
}