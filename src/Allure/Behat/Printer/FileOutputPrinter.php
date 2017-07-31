<?php
namespace Allure\Behat\Printer;

use Behat\Testwork\Output\Exception\BadOutputPathException;
use Behat\Testwork\Output\Printer\OutputPrinter as PrinterInterface;

class FileOutputPrinter implements PrinterInterface
{

  /**
   * @param  $outputPath where to save the generated report file
   */
    private $outputPath;

  /**
   * @param  $base_path Behat base path
   */
    private $base_path;


    public function __construct()
    {
    }

  /**
   * {@inheritDoc}
   */
    public function setOutputPath($path)
    {
      $this->outputPath = $path;
    }

  /**
   * {@inheritDoc}
   */
    public function getOutputPath()
    {
        return $this->outputPath;
    }

  /**
   * {@inheritDoc}
   */
    public function setOutputStyles(array $styles)
    {
    }

  /**
   * {@inheritDoc}
   */
    public function getOutputStyles()
    {
    }

   /**
   * {@inheritDoc}
   */
    public function setOutputDecorated($decorated)
    {
    }

  /**
   * {@inheritDoc}
   */
    public function isOutputDecorated()
    {
        return true;
    }

  /**
   * {@inheritDoc}
   */
    public function setOutputVerbosity($level)
    {
    }

  /**
   * {@inheritDoc}
   */
    public function getOutputVerbosity()
    {
    }

  /**
   * {@inheritDoc}
   */
    public function write($messages = array())
    {
    }


   /**
   * {@inheritDoc}
   */
    public function writeln($messages = array())
    {
    }

   /**
   * {@inheritDoc}
   */
    public function flush()
    {
    }
}
