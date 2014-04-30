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

class BuildIndexXlsxCommand extends ContainerAwareCommand
{
    protected function configure()
    {

        $this
            ->setName('psonio:buildindexxlsx')
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
        $finder = new Finder();
        $finder->files()->in($this->container->getParameter('kernel.root_dir').'/../data');
        $chunkSize = 50;

        foreach ($finder as $file) {
            $output->writeln("Loading file " . $file->getRealpath() . " ....." . PHP_EOL);
            /**  Create a new Reader of the type that has been identified  * */
            $objReader = PHPExcel_IOFactory::createReader(PHPExcel_IOFactory::identify($file->getRealpath()));

            $spreadsheetInfo = $objReader->listWorksheetInfo($file->getRealpath());

            /**  Create a new Instance of our Read Filter  * */
            $chunkFilter = new CourierDataFiller_ChunkReadFilter();

            /**  Tell the Reader that we want to use the Read Filter that we've Instantiated  * */
            $objReader->setReadFilter($chunkFilter);
            $objReader->setReadDataOnly(true);
            $objReader->setLoadSheetsOnly("data");

            //get header column name
            $chunkFilter->setRows(0, 1);
            $objPHPExcel = $objReader->load($file->getRealpath());

            $output->writeln("Reading file " . $file->getRealpath() . PHP_EOL);
            $totalRows = $spreadsheetInfo[0]['totalRows'];
            $output->writeln("Total rows in file " . $totalRows . " " . PHP_EOL);

            /**  Loop to read our worksheet in "chunk size" blocks  * */
            /**  $startRow is set to 1 initially because we always read the headings in row #1  * */
            for ($startRow = 1; $startRow <= $totalRows; $startRow += $chunkSize) {
                $output->writeln("Loading WorkSheet for rows " . $startRow . " to " . ($startRow + $chunkSize - 1) . PHP_EOL);
                /**  Tell the Read Filter, the limits on which rows we want to read this iteration  * */
                $chunkFilter->setRows($startRow, $chunkSize);

                /**  Load only the rows that match our filter from $inputFileName to a PHPExcel Object  * */
                $objPHPExcel = $objReader->load($file->getRealpath());
                $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, false);

                $startIndex = ($startRow == 1) ? $startRow : $startRow - 1;
                //dumping in database
                if (!empty($sheetData) && $startRow < $totalRows) {
                    $this->dumpInDb(array_slice($sheetData, $startIndex, $chunkSize));
                }
                $objPHPExcel->disconnectWorksheets();
                unset($objPHPExcel, $sheetData);
            }
            $output->writeln("File " . $file->getRealpath() . " has been uploaded successfully in database" . PHP_EOL);
        }
        $this->search->updateIndex();
        $output->writeln('The search index was built successfully');
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

Class ChunkReadFilter implements PHPExcel_Reader_IReadFilter {

    private $_startRow = 0;
    private $_endRow = 0;

    /**  Set the list of rows that we want to read  */
    public function setRows($startRow, $chunkSize) {
        $this->_startRow = $startRow;
        $this->_endRow = $startRow + $chunkSize;
    }

    public function readCell($column, $row, $worksheetName = '') {

        //  Only read the heading row, and the rows that are configured in $this->_startRow and $this->_endRow
        if (($row == 1) || ($row >= $this->_startRow && $row < $this->_endRow)) {

            return true;
        }
        return false;
    }

}