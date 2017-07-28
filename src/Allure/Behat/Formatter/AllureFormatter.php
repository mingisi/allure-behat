<?php

namespace Allure\Behat\Formatter;

// use Behat\Behat\Event\OutlineExampleEvent;
// use Behat\Behat\Event\ScenarioEvent;
// use Behat\Behat\Event\StepEvent;
// use Behat\Behat\Event\SuiteEvent;
// use Behat\Behat\Formatter\FormatterInterface;


// use Behat\Behat\EventDispatcher\Event\OutlineExampleEvent;
// use Behat\Behat\EventDispatcher\Event\ScenarioEvent;
// use Behat\Behat\EventDispatcher\Event\StepEvent;
// use Behat\Behat\EventDispatcher\Event\SuiteEvent;
use Behat\Behat\Tester\Result\StepResult;

use Behat\Testwork\Output\Formatter;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Behat\Testwork\EventDispatcher\Event\AfterSuiteTested;
use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use Behat\Testwork\EventDispatcher\Event\BeforeSuiteTested;
use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterOutlineTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeOutlineTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Testwork\Tester\Result;
use Behat\Testwork\Output\Printer\OutputPrinter as PrinterInterface;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;

use DateTime;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Translation\Translator;
use Throwable;
use Yandex\Allure\Adapter\Allure;
use Yandex\Allure\Adapter\AllureException;
use Yandex\Allure\Adapter\Annotation\AnnotationManager;
use Yandex\Allure\Adapter\Annotation\AnnotationProvider;
use Yandex\Allure\Adapter\Annotation\Description;
use Yandex\Allure\Adapter\Annotation\Features;
use Yandex\Allure\Adapter\Annotation\Issues;
use Yandex\Allure\Adapter\Annotation\Parameter;
use Yandex\Allure\Adapter\Annotation\Severity;
use Yandex\Allure\Adapter\Annotation\Stories;
use Yandex\Allure\Adapter\Annotation\TestCaseId;
use Yandex\Allure\Adapter\Event\StepCanceledEvent;
use Yandex\Allure\Adapter\Event\StepFailedEvent;
use Yandex\Allure\Adapter\Event\StepFinishedEvent;
use Yandex\Allure\Adapter\Event\StepStartedEvent;
use Yandex\Allure\Adapter\Event\TestCaseBrokenEvent;
use Yandex\Allure\Adapter\Event\TestCaseCanceledEvent;
use Yandex\Allure\Adapter\Event\TestCaseFailedEvent;
use Yandex\Allure\Adapter\Event\TestCaseFinishedEvent;
use Yandex\Allure\Adapter\Event\TestCasePendingEvent;
use Yandex\Allure\Adapter\Event\TestCaseStartedEvent;
use Yandex\Allure\Adapter\Event\TestSuiteFinishedEvent;
use Yandex\Allure\Adapter\Event\TestSuiteStartedEvent;
use Yandex\Allure\Adapter\Model\DescriptionType;
use Yandex\Allure\Adapter\Model\Provider;


use Allure\Behat\Printer\FileOutputPrinter;

/**
 * Class BehatFormatter
 * @package tests\features\formatter
 */
class AllureFormatter implements Formatter
{
    private $translator;

    private $uuid;
    private $filename = 'allure-results';

    /**
     * behat.yml parameters
     *
     * @var Array
     */
    private $parameters = [];

    /**
     * location to save the generated report file
     *
     * @var String
     */
    private $outputPath;

    /**
     * reports base bath
     *
     * @var string
     */
    private $base_path;

    /**
     * name of the formatter
     *
     * @var String
     */
    private $name;

    /**
     * Printer used by this Formatter and Context
     * @var OutputPrinter
     */
    private $printer;

    /**
     * @var Exception|Throwable
     */
    private $exception;

    public function __construct($name, $output, $delete_previous_results, $ignored_tags, $severity_tag_prefix, $issue_tag_prefix, $test_id_tag_prefix, $base_path)
    {

        $defaultLanguage = null;
        if (($locale = getenv('LANG')) && preg_match('/^([a-z]{2})/', $locale, $matches)) {
            $defaultLanguage = $matches[1];
        }

        $this->name = $name;
        $this->parameters['output'] = $output;        // $this->parameters['language'] = $language;
        $this->parameters['language'] = $defaultLanguage;
        $this->parameters['ignored_tags'] = $ignored_tags;
        $this->parameters['issue_tag_prefix'] = $issue_tag_prefix;
        $this->parameters['severity_tag_prefix'] = $severity_tag_prefix;
        $this->parameters['delete_previous_results'] = $delete_previous_results;

        $this->printer = new FileOutputPrinter([], $this->filename, $base_path);
    }

    /**
     * Set formatter translator.
     *
     * @param Translator $translator
     */
    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Checks if current formatter has parameter.
     *
     * @param string $name
     *
     * @return Boolean
     */
    public function hasParameter($name)
    {
        return $this->parameters->has($name);
    }

    /**
     * Returns formatter name.
     *
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns formatter description.
     *
     * {@inheritDoc}
     */
    public function getDescription()
    {
        return "Allura Reports";
    }

    /**
     * Returns formatter output printer.
     *
     * {@inheritDoc}
     */
    public function getOutputPrinter()
    {
        return $this->printer;
    }

    /**
     * Sets formatter parameter.
     *
     * {@inheritDoc}
     */
    public function setParameter($name, $value)
    {
        $this->parameters->set($name, $value);
    }

    /**
     * Returns parameter name.
     *
     * {@inheritDoc}
     */
    public function getParameter($name)
    {
        return $this->parameters[$name];
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
         return array(
            'tester.suite_tested.before'       => 'onBeforeSuiteTested',
            'tester.suite_tested.after'        => 'onAfterSuiteTested',
            'tester.scenario_tested.before'    => 'onBeforeScenarioTested',
            'tester.scenario_tested.after'     => 'onAfterScenarioTested',
            'tester.outline_tested.before'     => 'onBeforeOutlineTested',
            'tester.outline_tested.after'      => 'onAfterOutlineTested',
            'tester.step_tested.before'        => 'onBeforeStepTested',
            'tester.step_tested.after'         => 'onAfterStepTested',
        );
    }

    /**
     * @param BeforeSuiteTested $suiteEvent
     */
    public function onBeforeSuiteTested(BeforeSuiteTested $suiteEvent)
    {
        AnnotationProvider::addIgnoredAnnotations(array());

        $this->prepareOutputDirectory(
            $this->getParameter('output'),
            $this->getParameter('delete_previous_results')
        );
        $now = new DateTime();
        $event = new TestSuiteStartedEvent(sprintf('TestSuite-%s', $now->format('Y-m-d_His')));

        $this->uuid = $event->getUuid();

        Allure::lifecycle()->fire($event);
    }

    /**
     * @param AfterSuiteTested $suiteEvent
     */
    public function onAfterSuiteTested(AfterSuiteTested $suiteEvent)
    {
        Allure::lifecycle()->fire(new TestSuiteFinishedEvent($this->uuid));
    }

    /**
     * @param BeforeScenarioTested $scenarioEvent
     */
    public function onBeforeScenarioTested(BeforeScenarioTested $scenarioEvent)
    {
        $scenario = $scenarioEvent->getScenario();

        $annotations = array_merge(
            $this->parseFeatureAnnotations($scenarioEvent->getFeature()),
            $this->parseScenarioAnnotations($scenario)
        );

        $annotationManager = new AnnotationManager($annotations);

        $scenarioName = sprintf('%s:%d', $scenarioEvent->getFeature()->getFile(), $scenario->getLine());
        $event = new TestCaseStartedEvent($this->uuid, $scenarioName);
        $annotationManager->updateTestCaseEvent($event);

        Allure::lifecycle()->fire($event->withTitle($scenario->getTitle()));
    }

    /**
     * @param BeforeOutlineTested $outlineExampleEvent
     */
    public function onBeforeOutlineTested(BeforeOutlineTested $outlineExampleEvent)
    {
        $scenarioOutline = $outlineExampleEvent->getOutline();

        $scenarioName = sprintf(
            '%s:%d [%d]',
            $scenarioOutline->getFile(),
            $scenarioOutline->getLine(),
            $outlineExampleEvent->getIteration()
        );
        $event = new TestCaseStartedEvent($this->uuid, $scenarioName);

        $annotations = array_merge(
            $this->parseFeatureAnnotations($scenarioOutline->getFeature()),
            $this->parseScenarioAnnotations($scenarioOutline),
            $this->parseExampleAnnotations($scenarioOutline, $outlineExampleEvent->getIteration())
        );
        $annotationManager = new AnnotationManager($annotations);
        $annotationManager->updateTestCaseEvent($event);

        Allure::lifecycle()->fire($event->withTitle($scenarioOutline->getTitle()));
    }

    /**
     * @param AfterScenarioTested $scenarioEvent
     */
    public function onAfterScenarioTested(AfterScenarioTested $scenarioEvent)
    {
        $this->processScenarioResult($scenarioEvent->getTestResult());
    }

    /**
     * @param AfterOutlineTested $outlineExampleEvent
     */
    public function onAfterOutlineTested(AfterOutlineTested $outlineExampleEvent)
    {
        $this->processScenarioResult($outlineExampleEvent->getTestResult());
    }

    /**
     * @param BeforeStepTested $stepEvent
     */
    public function onBeforeStepTested(BeforeStepTested $stepEvent)
    {
        $step = $stepEvent->getStep();
        $event = new StepStartedEvent($step->getText());
        $event->withTitle(sprintf('%s %s', $step->getType(), $step->getText()));

        Allure::lifecycle()->fire($event);
    }

    /**
     * @param AfterStepTested $stepEvent
     */
    public function onAfterStepTested(AfterStepTested $stepEvent)
    {
 
        switch ($stepEvent->getTestResult()->getResultCode()) {
            case StepResult::FAILED:
                $this->exception = $stepEvent->getException();
                $this->addFailedStep();
                break;
            case StepResult::UNDEFINED:
                $this->exception = $stepEvent->getException();
                $this->addFailedStep();
                break;
            case StepResult::PENDING:
            case StepResult::SKIPPED:
                $this->addCanceledStep();
                break;
            case StepResult::PASSED:
            default:
                $this->exception = null;
        }

        $this->addFinishedStep();
    }

    /**
     * @param string $outputDirectory
     * @param boolean $deletePreviousResults
     */
    private function prepareOutputDirectory($outputDirectory, $deletePreviousResults)
    {
        if (!file_exists($outputDirectory)) {
            mkdir($outputDirectory, 0755, true);
        }

        if ($deletePreviousResults) {
            $files = glob($outputDirectory . DIRECTORY_SEPARATOR . '{,.}*', GLOB_BRACE);
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        if (is_null(Provider::getOutputDirectory())) {
            Provider::setOutputDirectory($outputDirectory);
        }
    }

    /**
     * @param integer $result
     */
    protected function processScenarioResult($result)
    {
        switch ($result->getResultCode()) {
            case StepResult::FAILED:
                $this->addTestCaseFailed();
                break;
            case StepResult::UNDEFINED:
                $this->addTestCaseBroken();
                break;
            case StepResult::PENDING:
                $this->addTestCasePending();
                break;
            case StepResult::SKIPPED:
                $this->addTestCaseCancelled();
                break;
            case StepResult::PASSED:
            default:
                $this->exception = null;
        }

        $this->addTestCaseFinished();
    }

    /**
     * @param FeatureNode $featureNode
     *
     * @return array
     */
    private function parseFeatureAnnotations(FeatureNode $featureNode)
    {
        $feature = new Features();
        $feature->featureNames = array($featureNode->getTitle());

        $description = new Description();
        $description->type = DescriptionType::TEXT;
        $description->value = $featureNode->getDescription();

        return [
            $feature,
            $description,
        ];
    }

    /**
     * @param ScenarioNode $scenario
     *
     * @return array
     * @throws Exception
     */
    private function parseScenarioAnnotations(ScenarioNode $scenario)
    {
        $annotations = [];
        $story = new Stories();
        $story->stories = [];

        $issues = new Issues();
        $issues->issueKeys = [];

        $testId = new TestCaseId();
        $testId->testCaseIds = [];

        $ignoredTags = [];
        $ignoredTagsParameter = $this->getParameter('ignored_tags');
        if (is_string($ignoredTagsParameter)) {
            $ignoredTags = array_map('trim', explode(',', $ignoredTagsParameter));
        } elseif (is_array($ignoredTagsParameter)) {
            $ignoredTags = $ignoredTagsParameter;
        }
        foreach ($scenario->getTags() as $tag) {
            if (in_array($tag, $ignoredTags)) {
                continue;
            }
            if ($severityPrefix = $this->getParameter('severity_tag_prefix')) {
                if (stripos($tag, $severityPrefix) === 0) {
                    try {
                        $parsedSeverity = substr($tag, strlen($severityPrefix));

                        $severity = new Severity();
                        $severity->level = $parsedSeverity;

                        $annotations[] = $severity;

                        continue;
                    } catch (AllureException $e) {
                        // do nothing and parse it as if it were regular tag
                    }
                }
            }

            if ($issuePrefix = $this->getParameter('issue_tag_prefix')) {
                if (stripos($tag, $issuePrefix) === 0) {
                    $issues->issueKeys[] = substr($tag, strlen($issuePrefix));

                    continue;
                }
            }

            if ($testIdPrefix = $this->getParameter('test_id_tag_prefix')) {
                if (stripos($tag, $testIdPrefix) === 0) {
                    $testId->testCaseIds[] = substr($tag, strlen($testIdPrefix));

                    continue;
                }
            }

            $story->stories[] = $tag;
        }

        if ($story->getStories()) {
            array_push($annotations, $story);
        }

        if ($issues->getIssueKeys()) {
            array_push($annotations, $issues);
        }

        if ($testId->getTestCaseIds()) {
            array_push($annotations, $testId);
        }

        return $annotations;
    }

    /**
     * @param OutlineNode $scenarioOutline
     * @param integer $iteration
     *
     * @return array
     */
    private function parseExampleAnnotations(OutlineNode $scenarioOutline, $iteration)
    {
        $parameters = [];
        $examplesRow = $scenarioOutline->getExamples()->getHash();
        foreach ($examplesRow[$iteration] as $name => $value) {
            $parameter = new Parameter();
            $parameter->name = $name;
            $parameter->value = $value;
            $parameters[] = $parameter;
        }

        return $parameters;
    }

    private function addCanceledStep()
    {
        $event = new StepCanceledEvent();

        Allure::lifecycle()->fire($event);
    }

    private function addFinishedStep()
    {
        $event = new StepFinishedEvent();

        Allure::lifecycle()->fire($event);
    }

    private function addFailedStep()
    {
        $event = new StepFailedEvent();

        Allure::lifecycle()->fire($event);
    }

    private function addTestCaseFinished()
    {
        $this->exception;

        $event = new TestCaseFinishedEvent();
        Allure::lifecycle()->fire($event);
    }

    private function addTestCaseCancelled()
    {
        $event = new TestCaseCanceledEvent();

        Allure::lifecycle()->fire($event);
    }

    private function addTestCasePending()
    {
        $event = new TestCasePendingEvent();

        Allure::lifecycle()->fire($event);
    }

    private function addTestCaseBroken()
    {
        $event = new TestCaseBrokenEvent();
        $event->withException($this->exception)->withMessage($this->exception->getMessage());

        Allure::lifecycle()->fire($event);
    }

    private function addTestCaseFailed()
    {
        $event = new TestCaseFailedEvent();
        $event->withException($this->exception)->withMessage($this->exception->getMessage());

        Allure::lifecycle()->fire($event);
    }
}
