<?php

/*
 * This file is part of the IBLongFormBundle package.
 *
 * (c) Inspired Beings Ltd <http://www.inspired-beings.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace InspiredBeings\LongFormBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use InspiredBeings\LongFormBundle\Helper\Pluralizer;
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
     * @var string $bundleName      Bundle short name
     * @var string $bundleNameSpace Bundle namespace
     * @var string $bundlePath      Bundle absolute path (without the last slash)
     * @var string $formModelName   Form model name
     * @var array  $formModel       Form model data (gotten from yaml model file)
     */
    protected $bundleName;
    protected $bundleNameSpace;
    protected $bundlePath;
    protected $formModelName;
    protected $formModel;

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
                "The yaml form model path. Example: <info>MyBundle:MyForm</info>"
            )
            ->addOption(
                'no-entity',
                null,
                InputOption::VALUE_NONE,
                "Do NOT generate an entity"
            )
            ->addOption(
                'schema-update',
                null,
                InputOption::VALUE_NONE,
                "Update Doctrine Shema (<info>doctrine:schema:update --force</info>)"
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $formModelPath = explode(':', $input->getArgument('formModel'));

        // If the form model path format is correct (Foo:Bar)
        if (count($formModelPath) !== 2)
        {
            exit($output->writeln(array(
                "<error>Wrong format for your form model path !</error>",
                "Example: <info>php app/console generate:form MyBundle:MyForm</info>"
            )));
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
            exit($output->writeln("<error>" . $exception->getMessage() . "</error>"));
        }

        // We get the bundle name and the form model name
        $this->bundlePath = $bundle->getPath();
        $this->bundleNameSpace = $bundle->getNamespace();

        // If the form model name does not only contain letters
        if (!ctype_alpha($this->formModelName))
        {
            exit($output->writeln(array(
                "<error>Your form model name must only contain letters.</error>",
                "<info>Example: if your yaml filename is MyForm.yml (within Form/Model/ directory), the form model path will be " . $this->bundleName . ":MyForm.</info>"
            )));
        }

        // If the first letter is not a capital one
        if (ucfirst($this->formModelName) !== $this->formModelName)
        {
            exit($output->writeln(array(
                "<error>Your form model name must respect Pacal Case format (each word starts with a capital letter).</error>",
                "<info>Maybe " . $this->bundleName . ":" . ucfirst($this->formModelName) . " ?</info>"
            )));
        }

        // We set the form model yaml file absolute path
        $formModelPath = $this->bundlePath . '/Form/Model/' . $this->formModelName . '.yml';

        // If form model yaml file exists
        if (!file_exists($formModelPath))
        {
            exit($output->writeln(array(
                "<error>We didn't find your yaml file !</error>",
                "<question>Is " . $this->formModelName . ".yml within the Form/Model/ directory of your bundle " . $this->bundleName . " ?</question>"
            )));
        }

        // We try to get the yaml source
        try
        {
            // @throws ParseException If the YAML is not valid
            $this->formModel = Yaml::parse(file_get_contents($formModelPath));
        }
        catch (ParseException $exception)
        {
            exit($output->writeln("<error>" . $exception->getMessage() . "</error>"));
        }

        // ------------------------------------------------------------------------------------------------------------------------------------
        // Form Type PHP file generation

        $output->writeln("Generating form type class for form model \"" . $this->bundleName . ":" . $this->formModelName . "\"");
        $formTypeFileSource = $this->generateFormTypePHPSource();
        $formTypeFilePath = $this->bundlePath . '/Form/Type/' . $this->formModelName . 'Type.php';

        // If "Form/Type" directory doesn't exist, we create it
        if (!file_exists($this->bundlePath . '/Form/Type'))
        {
            $output->writeln("  > creating directory " . $this->bundlePath . "/Form/Type");
            mkdir($this->bundlePath . '/Form/Type');
        }

        // If the form type already exists, we save a copy of it
        if (file_exists($formTypeFilePath))
        {
            $output->writeln("  > backing up " . $this->formModelName . "Type.php to " . $this->formModelName . "Type.php~");
            copy($formTypeFilePath, $formTypeFilePath . '~');
        }

        // We write the (new) form type for this form model
        $output->writeln("  > generating " . $formTypeFilePath);
        file_put_contents($formTypeFilePath, $formTypeFileSource);

        if ($input->getOption('no-entity'))
        {
            exit();
        }

        // ------------------------------------------------------------------------------------------------------------------------------------
        // Entity PHP file generation

        $output->writeln("Generating entity class for form model \"" . $this->bundleName . ":" . $this->formModelName . "\"");
        $entityFilePath = $this->bundlePath . '/Entity/' . $this->formModelName . '.php';
        $entityFileSource = $this->generateEntityPHPSource();

        // If "Entity" directory doesn't exist, we create it
        if (!file_exists($this->bundlePath . '/Entity'))
        {
            $output->writeln("  > creating directory " . $this->bundlePath . "/Entity");
            mkdir($this->bundlePath . '/Entity');
        }

        // If the entity already exists, we save a copy of it
        if (file_exists($entityFilePath))
        {
            $output->writeln("  > backing up " . $this->formModelName . ".php to " . $this->formModelName . ".php~");
            copy($entityFilePath, $entityFilePath . '~');
        }

        // We write the (new) entity for this form model
        $output->writeln("  > generating " . $entityFilePath);
        file_put_contents($entityFilePath, $entityFileSource);

        // We generate the entities for this entity (via Doctrine)
        $command = $this->getApplication()->find('doctrine:generate:entities');
        $commandInput = new ArrayInput(array(
            'command' => 'doctrine:generate:entities',
            'name' => $this->bundleName . ':' . $this->formModelName
        ));
        $command->run($commandInput, $output);

        // If shema update option is set to TRUE
        if ($input->getOption('schema-update'))
        {
            // We generate the entities for this entity (via Doctrine)
            $command = $this->getApplication()->find('doctrine:schema:update');
            $commandInput = new ArrayInput(array(
                'command' => 'doctrine:schema:update',
                '--force' => true
            ));
            $command->run($commandInput, $output);
        }
    }

    /**
     * Generate PHP source code for the form type file
     *
     * @return string The PHP source code
     */
    protected function generateFormTypePHPSource()
    {
        $formModelDefaults = false;

        // If defaults array is set and contains some data
        if (isset($this->formModel['defaults']) && is_array($this->formModel['defaults']) && count($this->formModel['defaults']) !== 0)
        {
            $formModelDefaults = $this->formModel['defaults'];
            unset($this->formModel['defaults']);
        }

        $source = "";

        $source .= "<?php" . "\n";
        $source .= "\n";
        $source .= "namespace " . $this->bundleNameSpace . "\\Form\\Type;" . "\n";
        $source .= "\n";
        $source .= "use Symfony\\Component\\Form\\AbstractType;" . "\n";
        $source .= "use Symfony\\Component\\Form\\FormBuilderInterface;" . "\n";
        $source .= "use Symfony\\Component\\OptionsResolver\\OptionsResolver;" . "\n";
        $source .= "\n";
        $source .= "class " . $this->formModelName . "Type extends AbstractType" . "\n";
        $source .= "{" . "\n";
        $source .= "    /**" . "\n";
        $source .= "     * @param FormBuilderInterface \$builder" . "\n";
        $source .= "     * @param array \$options" . "\n";
        $source .= "     */" . "\n";
        $source .= "    public function buildForm(FormBuilderInterface \$builder, array \$options)" . "\n";
        $source .= "    {" . "\n";
        $source .= "        \$builder" . "\n";

        foreach ($this->formModel as $fieldName => $fieldOptions)
        {
            // By default, field type is "text"
            $fieldType = (isset($fieldOptions['type'])) ? $fieldOptions['type'] : 'text';
            unset($fieldOptions['type']);

            $source .= "            ->add('$fieldName', '$fieldType'";

            if (count($fieldOptions) !== 0)
            {
                $source .= ", array(" . "\n";
                $source .= $this->arrayToPhp($fieldOptions);
                $source .= "            ))" . "\n";

                continue;
            }

            $source .= ")" . "\n";
        }

        $source .= "        ;" . "\n";

        $source .= "    }" . "\n";
        $source .= "\n";

        // Setting form type defaults (if some are defined)
        if ($formModelDefaults)
        {
            $source .= "    /**" . "\n";
            $source .= "     * @param OptionsResolver \$resolver" . "\n";
            $source .= "     */" . "\n";
            $source .= "    public function configureOptions(OptionsResolver \$resolver)" . "\n";
            $source .= "    {" . "\n";
            $source .= "        \$resolver->setDefaults(array(" . "\n";

            $source .= $this->arrayToPhp($formModelDefaults, 3);

            $source .= "        ));" . "\n";
            $source .= "    }" . "\n";
            $source .= "\n";
        }

        $source .= "    /**" . "\n";
        $source .= "      * @return string" . "\n";
        $source .= "     */" . "\n";
        $source .= "    public function getName()" . "\n";
        $source .= "    {" . "\n";
        $source .= "        return 'form_" . $this->getContainer()->underscore($this->formModelName) . "';" . "\n";
        $source .= "    }" . "\n";
        $source .= "}" . "\n";

        return $source;
    }

    /**
     * Generate PHP source code for the entity
     *
     * @return string The PHP source code
     */
    protected function generateEntityPHPSource()
    {
        $source = "";

        $source .= "<?php" . "\n";
        $source .= "\n";
        $source .= "namespace " . $this->bundleNameSpace . "\\Entity;" . "\n";
        $source .= "\n";
        $source .= "use Doctrine\\ORM\\Mapping as ORM;" . "\n";
        $source .= "\n";
        $source .= "/**" . "\n";
        $source .= " * " . $this->formModelName . "\n";
        $source .= " *" . "\n";
        $source .= " * @ORM\\Table(name=\"" . Pluralizer::pluralUnderscore($this->getContainer()->underscore($this->formModelName)) . "\")" . "\n";
        $source .= " * @ORM\\Entity(repositoryClass=\"" . $this->bundleNameSpace . "\\Entity\\" . $this->formModelName . "Repository\")" . "\n";
        $source .= " */" . "\n";
        $source .= "class " . $this->formModelName . "\n";
        $source .= "{" . "\n";
        $source .= "    /**" . "\n";
        $source .= "     * @var integer" . "\n";
        $source .= "     *" . "\n";
        $source .= "     * @ORM\\Column(name=\"id\", type=\"integer\")" . "\n";
        $source .= "     * @ORM\\Id" . "\n";
        $source .= "     * @ORM\\GeneratedValue(strategy=\"AUTO\")" . "\n";
        $source .= "     */" . "\n";
        $source .= "    private \$id;" . "\n";

        foreach ($this->formModel as $fieldName => $fieldOptions)
        {
            if (
                isset($fieldOptions['type'])
                && ($fieldOptions['type'] == 'button' || $fieldOptions['type'] == 'hidden' || $fieldOptions['type'] == 'reset' || $fieldOptions['type'] == 'submit')
            )
            {
                continue;
            }

            $source .= "\n";
            $source .= "    /**" . "\n";
            $source .= "     * @var string" . "\n";
            $source .= "     *" . "\n";
            $source .= "     * " . $this->formTypeToDoctrineORMType( $fieldName, isset($fieldOptions['type']) ? $fieldOptions['type'] : 'string' ) . "\n";
            $source .= "     */" . "\n";
            $source .= "    private \$" . $fieldName . ";" . "\n";
        }

        $source .= "}" . "\n";

        return $source;
    }

    /**
     * Recursive function converting an array value into arrays written in PHP source code
     *
     * @param array  $array       The array to be converted
     * @param int    $tabulations Numbers of tabulation to indent the code with
     * @param string $endOfLine   Ends of line for code formatting
     *
     * @return string The PHP source code
     */
    protected function arrayToPhp($array, $tabulations = 4, $endOfLine = "\n")
    {
        $source = "";
        $spaces = "";
        for ($index = 0; $index < $tabulations; $index++)
        {
            $spaces .= "    ";
        }

        foreach ($array as $option => $value)
        {
            $source .= (($endOfLine === "\n") ? $spaces : "") . "'$option' => ";

            if (is_array($value)) $source .= "[" . $this->arrayToPhp($value, ++$tabulations, " ") . "]";
            elseif (is_bool($value)) $source .= ($value) ? "true" : "false";
            elseif (is_int($value) || is_float($value)) $source .= $value;
            else                                        $source .= "\"" . $value . "\"";

            $source .= "," . $endOfLine;
        }

        return $source;
    }

    /**
     * Convert a Symfony form field type into a Doctrine ORM annocation
     * for an entity property (to be converted as a column into the database)
     * 
     * @todo Manage all these form field types :
     *         - integer
     *         - number
     *         - percent
     *         - choice
     *         - entity
     *         - timezone
     *         - currency
     *         - file
     *         - radio
     *         - collection
     *
     * @param string $propertyName  The entity property name (in camelCase format !)
     * @param string $formFieldType The Symfony form type for the field regarding this property
     *
     * @return string The Doctrine ORM\Column annotation
     */
    protected function formTypeToDoctrineORMType($propertyName, $formFieldType)
    {
        $annotation = "@ORM\\Column(name=\"" . $this->getContainer()->underscore($propertyName) . "\", ";

        switch ($formFieldType)
        {
            case 'checkbox':
                $annotation .= "type=\"boolean\"";
                break;

            case 'date':
                $annotation .= "type=\"date\", nullable=true";
                break;

            case 'birthday':
            case 'datetime':
                $annotation .= "type=\"datetime\", nullable=true";
                break;

            case 'money':
                $annotation .= "type=\"decimal\", precision=13, scale=2, nullable=true";
                break;

            case 'textarea':
                $annotation .= "type=\"text\", nullable=true";
                break;

            case 'time':
                $annotation .= "type=\"time\", nullable=true";
                break;

            // country, email, language, locale, password, repeated, search, text, url
            default:
                $annotation .= "type=\"string\", length=255, nullable=true";
                break;
        }

        $annotation .= ")";

        return $annotation;
    }
}
