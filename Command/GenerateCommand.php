<?php

/*
 * This file is part of the IGLongFormBundle package.
 *
 * (c) Ivan Gabriele <http://www.ivangabriele.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */ 

namespace IvanGabriele\LongFormBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use IvanGabriele\LongFormBundle\Helper\PhpGenerator;
use IvanGabriele\LongFormBundle\Helper\Pluralizer;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * GenerateCommand generates a form type class from a simple yaml form model.
 *
 * @author Ivan Gabriele <ivan.gabriele@gmail.com>
 */
class GenerateCommand extends ContainerAwareCommand
{
    /**
     * @var string Bundle short name
     * @var string Bundle namespace
     * @var string Bundle absolute path (without the last slash)
     * @var string Form model name
     * @var array  Form model data (gotten from yaml model file)
     */
    protected
        $bundleName,
        $bundleNameSpace,
        $bundlePath,
        $formModel,
        $formModelName,
        $input,
        $output
    ;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:form')
            ->setDescription('Generates a form type class based on a yaml file')
            ->addArgument(
                'formModel',
                InputArgument::REQUIRED,
                'The yaml form model path. Example: <info>MyBundle:MyForm</info>'
            )
            ->addOption(
                'no-entity',
                null,
                InputOption::VALUE_NONE,
                'Do NOT generate an entity'
            )
            ->addOption(
                'schema-update',
                null,
                InputOption::VALUE_NONE,
                'Update Doctrine Shema (<info>doctrine:schema:update --force</info>)'
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        // We check for command input errors
        $this->checkInput();

        // We generate form type file
        $this->generateFormType();

        // We generate form template file
        $this->generateTemplate();

        // If the "no-entity" option is not set
        if (!$input->getOption('no-entity'))
        {
            // We generate entity file
            $this->generateEntity();
        }

        // If shema update option is set to TRUE
        if ($input->getOption('schema-update'))
        {
            $output->writeln('');

            // We generate the entities for this entity (via Doctrine)
            $command = $this->getApplication()->find('doctrine:schema:update');
            $commandInput = new ArrayInput(array(
                'command' => 'doctrine:schema:update',
                '--force' => true,
            ));
            $command->run($commandInput, $output);
        }
    }

    /**
     * Check command input.
     */
    protected function checkInput()
    {
        $formModelPath = explode(':', $this->input->getArgument('formModel'));

        // If the form model path format is correct (Foo:Bar)
        if (count($formModelPath) !== 2)
        {
            $this->output->writeln(array(
                '<error>Wrong format for your form model path !</error>',
                'Example: <info>php app/console generate:form MyBundle:MyForm</info>',
            ));
        }

        // We get the bundle name and the form model name
        $this->bundleName = $formModelPath[0];
        $this->formModelName = $formModelPath[1];

        // We try to get the bundle object
        try
        {
            // @throws \InvalidArgumentException when the bundle is not enabled
            $bundle = $this->getApplication()->getKernel()->getBundle($this->bundleName);
        }
        catch (\InvalidArgumentException $exception)
        {
            $this->output->writeln('<error>' . $exception->getMessage() . '</error>');
        }

        // We get the bundle name and the form model name
        $this->bundlePath = $bundle->getPath();
        $this->bundleNameSpace = $bundle->getNamespace();

        // If the form model name does not only contain letters
        if (!ctype_alpha($this->formModelName))
        {
            $this->output->writeln(array(
                '<error>Your form model name must only contain letters.</error>',
                '<info>Example: if your yaml filename is MyForm.yml (within Form/Model/ directory), the form model path will be ' . $this->bundleName . ':MyForm.</info>',
            ));
        }

        // If the first letter is not a capital one
        if (ucfirst($this->formModelName) !== $this->formModelName)
        {
            $this->output->writeln(array(
                '<error>Your form model name must respect Pacal Case format (each word starts with a capital letter).</error>',
                '<info>Maybe ' . $this->bundleName . ':' . ucfirst($this->formModelName) . ' ?</info>',
            ));
        }

        // We set the form model yaml file absolute path
        $formModelPath = $this->bundlePath . '/Form/Model/' . $this->formModelName . '.yml';

        // If form model yaml file exists
        if (!file_exists($formModelPath))
        {
            $this->output->writeln(array(
                "<error>We didn't find your yaml file !</error>",
                '<question>Is ' . $this->formModelName . '.yml within the Form/Model/ directory of your bundle ' . $this->bundleName . ' ?</question>',
            ));
        }

        // We try to get the yaml source
        try
        {
            // @throws ParseException If the YAML is not valid
            $this->formModel = Yaml::parse(file_get_contents($formModelPath));
        }
        catch (ParseException $exception)
        {
            $this->output->writeln('<error>' . $exception->getMessage() . '</error>');
        }
    }

    /**
     * Generate the form type file.
     */
    protected function generateFormType()
    {
        $this->output->writeln(array(
            '',
            'Generating form type class for form model "' . $this->bundleName . ':' . $this->formModelName . '"',
        ));

        $formTypeFileSource = $this->generateFormTypePhpSource();
        $formTypeFilePath = $this->bundlePath . '/Form/Type/' . $this->formModelName . 'Type.php';

        // If "Form/Type" directory doesn't exist, we create it
        if (!file_exists($this->bundlePath . '/Form/Type'))
        {
            $this->output->writeln('  > creating directory ' . $this->bundlePath . '/Form/Type');
            mkdir($this->bundlePath . '/Form/Type');
        }

        // If the form type already exists, we save a copy of it
        if (file_exists($formTypeFilePath))
        {
            $this->output->writeln('  > backing up ' . $this->formModelName . 'Type.php to ' . $this->formModelName . 'Type.php~');
            copy($formTypeFilePath, $formTypeFilePath . '~');
        }

        // We write the (new) form type for this form model
        $this->output->writeln('  > generating ' . $formTypeFilePath);
        file_put_contents($formTypeFilePath, $formTypeFileSource);
    }

    /**
     * Generate the PHP source code for the form type file.
     *
     * @return string The PHP source code
     */
    protected function generateFormTypePhpSource()
    {
        $formModelDefaults = array();

        // If defaults array is set and contains some data
        if (isset($this->formModel['defaults']) && is_array($this->formModel['defaults']) && count($this->formModel['defaults']) !== 0)
        {
            $formModelDefaults = $this->formModel['defaults'];
            unset($this->formModel['defaults']);
        }

        $source = '<?php' . "\n\n";

        $source .= 'namespace ' . $this->bundleNameSpace . '\\Form\\Type;' . "\n\n";

        $source .= 'use Symfony\\Component\\Form\\AbstractType;' . "\n";
        $source .= 'use Symfony\\Component\\Form\\FormBuilderInterface;' . "\n";
        $source .= 'use Symfony\\Component\\OptionsResolver\\OptionsResolver;' . "\n\n";

        $source .= 'class ' . $this->formModelName . 'Type extends AbstractType' . "\n";
        $source .= '{' . "\n";

        // We concatenate the form builder method source code
        $source .= $this->generateFormTypeBuilderPhpSource() . "\n";

        // If "defaults" are defined and contains data in the form model
        if (count($formModelDefaults) !== 0)
        {
            // We concatenate the configureOptions() method source code
            $source .= $this->generateFormTypeOptionsPhpSource($formModelDefaults) . "\n";
        }

        $source .= '    /**' . "\n";
        $source .= '      * @return string' . "\n";
        $source .= '     */' . "\n";
        $source .= '    public function getName()' . "\n";
        $source .= '    {' . "\n";
        $source .= "        return 'form_" . $this->getContainer()->underscore($this->formModelName) . "';" . "\n";
        $source .= '    }' . "\n";
        $source .= '}' . "\n";

        return $source;
    }

    /**
     * Generate the PHP source code for the buildForm() method of the form type class.
     *
     * @return string The PHP source code
     */
    protected function generateFormTypeBuilderPhpSource()
    {
        $source = '';

        $source .= '    /**' . "\n";
        $source .= "     * @param FormBuilderInterface \$builder" . "\n";
        $source .= "     * @param array \$options" . "\n";
        $source .= '     */' . "\n";
        $source .= "    public function buildForm(FormBuilderInterface \$builder, array \$options)" . "\n";
        $source .= '    {' . "\n";
        $source .= "        \$builder" . "\n";

        foreach ($this->formModel as $fieldName => $fieldOptions)
        {
            // By default, field type is "text"
            $fieldType = (isset($fieldOptions['type'])) ? $fieldOptions['type'] : 'text';
            unset($fieldOptions['type']);

            $source .= "            ->add('$fieldName', '$fieldType'";

            if (count($fieldOptions) !== 0)
            {
                $source .= ', array(' . "\n";
                $source .= PhpGenerator::arrayToPhp($fieldOptions);
                $source .= '            ))' . "\n";

                continue;
            }

            $source .= ')' . "\n";
        }

        $source .= '        ;' . "\n";

        $source .= '    }' . "\n";

        return $source;
    }

    /**
     * Generate the PHP source code for the form configureOptions() method of the form type class.
     *
     * @return string The PHP source code
     */
    protected function generateFormTypeOptionsPhpSource($formModelDefaults)
    {
        $source = '';

        $source .= '    /**' . "\n";
        $source .= "     * @param OptionsResolver \$resolver" . "\n";
        $source .= '     */' . "\n";
        $source .= "    public function configureOptions(OptionsResolver \$resolver)" . "\n";
        $source .= '    {' . "\n";
        $source .= "        \$resolver->setDefaults(array(" . "\n";

        $source .= PhpGenerator::arrayToPhp($formModelDefaults, 3);

        $source .= '        ));' . "\n";
        $source .= '    }' . "\n";
        $source .= "\n";

        return $source;
    }

    /**
     * Generate the template file for the form.
     */
    protected function generateTemplate()
    {
        $this->output->writeln(array(
            '',
            'Generating form Twig template for form model "' . $this->bundleName . ':' . $this->formModelName . '"',
        ));

        $formTemplateFileSource = $this->generateTemplateTwigSource();
        $formTemplateFilePath = $this->bundlePath . '/Resources/views/Form/' . $this->getContainer()->underscore($this->formModelName) . '.html.twig';

        // If "Ressources/views/Form" directory doesn't exist, we create it
        if (!file_exists($this->bundlePath . '/Resources/views/Form'))
        {
            $this->output->writeln('  > creating directory ' . $this->bundlePath . '/Resources/views/Form');

            if (!file_exists($this->bundlePath . '/Ressources'))
            {
                mkdir($this->bundlePath . '/Resources');
            }

            if (!file_exists($this->bundlePath . '/Ressources/views'))
            {
                mkdir($this->bundlePath . '/Resources/views');
            }

            mkdir($this->bundlePath . '/Resources/views/Form');
        }

        // If the form template already exists, we save a copy of it
        if (file_exists($formTemplateFilePath))
        {
            $this->output->writeln('  > backing up ' . $this->getContainer()->underscore($this->formModelName) . '.html.twig to ' . $this->getContainer()->underscore($this->formModelName) . '.html.twig~');
            copy($formTemplateFilePath, $formTemplateFilePath . '~');
        }

        // We write the (new) template for this form model
        $this->output->writeln('  > generating ' . $formTemplateFilePath);
        file_put_contents($formTemplateFilePath, $formTemplateFileSource);
    }

    /**
     * Generate the Twig source code for the form template.
     *
     * @return string The Twig source code
     */
    protected function generateTemplateTwigSource()
    {
        $source = '';

        $source .= '{#' . "\n";
        $source .= '    To customize your field blocks, check the Cookbook > How to Customize Form Rendering :' . "\n";
        $source .= '    http://symfony.com/doc/current/cookbook/form/form_customization.html#form-theming' . "\n";
        $source .= ' #}' . "\n\n";

        $source .= '{# Form opening tag #}' . "\n";
        $source .= '{{ form_start(form) }}' . "\n\n";

        $source .= '    {# Form general errors #}' . "\n";
        $source .= '    {{ form_errors(form) }}' . "\n\n";


        foreach ($this->formModel as $fieldName => $fieldOptions)
        {
            if (isset($fieldOptions['type']) && $fieldOptions['type'] == 'hidden')
            {
                continue;
            }

            $source .= '    {{ form_row(form.' . $fieldName . ') }}' . "\n";
        }

        $source .= "\n";
        $source .= '    {# CSRF and hidden fields #}' . "\n";
        $source .= '    {{ form_rest(form) }}' . "\n\n";

        $source .= '{# Form closing tag #}' . "\n";
        $source .= '{{ form_end(form) }}' . "\n";

        return $source;
    }

    /**
     * Generate the entity file.
     */
    protected function generateEntity()
    {
        $this->output->writeln(array(
            '',
            'Generating entity class for form model "' . $this->bundleName . ':' . $this->formModelName . '"',
        ));

        $entityFilePath = $this->bundlePath . '/Entity/' . $this->formModelName . '.php';
        $entityFileSource = $this->generateEntityPhpSource();

        // If "Entity" directory doesn't exist, we create it
        if (!file_exists($this->bundlePath . '/Entity'))
        {
            $this->output->writeln('  > creating directory ' . $this->bundlePath . '/Entity');
            mkdir($this->bundlePath . '/Entity');
        }

        // If the entity already exists, we save a copy of it
        if (file_exists($entityFilePath))
        {
            $this->output->writeln('  > backing up ' . $this->formModelName . '.php to ' . $this->formModelName . '.php~');
            copy($entityFilePath, $entityFilePath . '~');
        }

        // We write the (new) entity for this form model
        $this->output->writeln('  > generating ' . $entityFilePath);
        file_put_contents($entityFilePath, $entityFileSource);

        // We generate the entities for this entity (via Doctrine)
        $this->output->writeln('');

        $command = $this->getApplication()->find('doctrine:generate:entities');
        $commandInput = new ArrayInput(array(
            'command' => 'doctrine:generate:entities',
            'name'    => $this->bundleName . ':' . $this->formModelName,
        ));
        $command->run($commandInput, $this->output);
    }

    /**
     * Generate the PHP source code for the entity.
     *
     * @return string The PHP source code
     */
    protected function generateEntityPhpSource()
    {
        $source = '<?php' . "\n\n";

        $source .= 'namespace ' . $this->bundleNameSpace . '\\Entity;' . "\n\n";

        $source .= 'use Doctrine\\ORM\\Mapping as ORM;' . "\n\n";

        $source .= '/**' . "\n";
        $source .= ' * ' . $this->formModelName . "\n";
        $source .= ' *' . "\n";
        $source .= ' * @ORM\\Table(name="' . Pluralizer::pluralUnderscore($this->getContainer()->underscore($this->formModelName)) . '")' . "\n";
        $source .= ' * @ORM\\Entity(repositoryClass="' . $this->bundleNameSpace . '\\Entity\\' . $this->formModelName . 'Repository")' . "\n";
        $source .= ' */' . "\n";
        $source .= 'class ' . $this->formModelName . "\n";
        $source .= '{' . "\n";
        $source .= '    /**' . "\n";
        $source .= '     * @var integer' . "\n";
        $source .= '     *' . "\n";
        $source .= '     * @ORM\\Column(name="id", type="integer")' . "\n";
        $source .= '     * @ORM\\Id' . "\n";
        $source .= '     * @ORM\\GeneratedValue(strategy="AUTO")' . "\n";
        $source .= '     */' . "\n";
        $source .= "    private \$id;" . "\n\n";

        $source .= $this->generateEntityPropertiesPhpSource();

        $source .= '}' . "\n";

        return $source;
    }

    /**
     * Generate the PHP source code for the entity properties.
     *
     * @return string The PHP source code
     */
    protected function generateEntityPropertiesPhpSource()
    {
        $source = '';

        foreach ($this->formModel as $fieldName => $fieldOptions)
        {
            if (isset($fieldOptions['type']) && in_array($fieldOptions['type'], array('button', 'hidden', 'reset', 'submit')))
            {
                continue;
            }

            $source .= '    /**' . "\n";
            $source .= '     * @var string' . "\n";
            $source .= '     *' . "\n";
            $source .= '     * ' . $this->formTypeToDoctrineORMType($fieldName, isset($fieldOptions['type']) ? $fieldOptions['type'] : 'string') . "\n";
            $source .= '     */' . "\n";
            $source .= "    private \$" . $fieldName . ';' . "\n\n";
        }

        return $source;
    }

    /**
     * Convert a Symfony form field type into a Doctrine ORM annocation
     * for an entity property (to be converted as a column into the database).
     *
     * @param string $propertyName  The entity property name (in camelCase format !)
     * @param string $formFieldType The Symfony form type for the field regarding this property
     *
     * @return string The Doctrine ORM\Column annotation
     */
    protected function formTypeToDoctrineORMType($propertyName, $formFieldType)
    {
        $annotation = '@ORM\\Column(name="' . $this->getContainer()->underscore($propertyName) . '", ';

        switch ($formFieldType)
        {
            case 'checkbox':
                $annotation .= 'type="boolean"';
                break;

            case 'date':
                $annotation .= 'type="date", nullable=true';
                break;

            case 'birthday':
            case 'datetime':
                $annotation .= 'type="datetime", nullable=true';
                break;

            case 'integer':
                $annotation .= 'type="integer", nullable=true';
                break;

            case 'money':
            case 'percent':
                $annotation .= 'type="decimal", precision=13, scale=2, nullable=true';
                break;

            case 'number':
                $annotation .= 'type="decimal", precision=21, scale=10, nullable=true';
                break;

            case 'textarea':
                $annotation .= 'type="text", nullable=true';
                break;

            case 'time':
                $annotation .= 'type="time", nullable=true';
                break;

            case 'choice':
                $annotation .= 'type="array"';
                break;

            // country, currency, email, language, locale, password, radio, repeated, search, text, timezone, url AND entity, file, collection
            default:
                $annotation .= 'type="string", length=255, nullable=true';
                break;
        }

        $annotation .= ')';

        return $annotation;
    }
}
