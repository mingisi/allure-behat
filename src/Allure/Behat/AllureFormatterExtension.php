<?php

namespace Allure\Behat;

use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AllureFormatterExtension implements ExtensionInterface
{

    const CONFIG_KEY = 'allurehtml';

   /**
    * You can modify the container here before it is dumped to PHP code.
    *
    * @param ContainerBuilder $container
    *
    * @api
    */
    public function process(ContainerBuilder $container)
    {
    }

   /**
    * Returns the extension config key.
    *
    * @return string
    */
    public function getConfigKey()
    {
        return self::CONFIG_KEY;
    }


   /**
    * Initializes other extensions.
    *
    * This method is called immediately after all extensions are activated but
    * before any extension `configure()` method is called. This allows extensions
    * to hook into the configuration of other extensions providing such an
    * extension point.
    *
    * @param ExtensionManager $extensionManager
    */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
    * Setups configuration for the extension.
    *
    * @param ArrayNodeDefinition $builder
    */
    public function configure(ArrayNodeDefinition $builder)
    {

        $builder->children()->scalarNode("name")->defaultValue("allure");
        $builder->children()->scalarNode("output")->defaultValue("Twig");
        $builder->children()->booleanNode("delete_previous_results")->defaultValue(false);
        $builder->children()->scalarNode("ignored_tags")->defaultValue("javascript");
        $builder->children()->scalarNode("severity_tag_prefix")->defaultValue("severity_");
        $builder->children()->scalarNode("issue_tag_prefix")->defaultValue("bug_");
        $builder->children()->scalarNode("test_id_tag_prefix")->defaultValue("test_case_");
        //  $builder->useAttributeAsKey('name')->prototype('variable');
    }

   /**
    * Loads extension services into temporary container.
    *
    * @param ContainerBuilder $container
    * @param array $config
    */
    public function load(ContainerBuilder $container, array $config)
    {
        $definition = new Definition("Allure\\Behat\\Formatter\\AllureFormatter");
        $definition->addArgument($config['name']);
        $definition->addArgument($config['output']);
        $definition->addArgument($config['delete_previous_results']);
        $definition->addArgument($config['ignored_tags']);
        $definition->addArgument($config['severity_tag_prefix']);
        $definition->addArgument($config['issue_tag_prefix']);
        $definition->addArgument($config['test_id_tag_prefix']);
        $definition->addArgument('%paths.base%');
        $container->setDefinition("html.formatter", $definition)
        ->addTag("output.formatter");

        echo ">> i'm done setting up";
    }
}
