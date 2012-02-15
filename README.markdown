
<h3>Introduction</h3>
This is adaptation of <a href="https://github.com/fruit/sfDoctrineTablePlugin">sfDoctrineTablePlugin</a> plugin's by Ilya Sabelnikov, for Zend Framework and Doctrine. <b>My_Doctrine_Task_BuildTable</b> is the Doctrine Cli task responsible for base tables generation. Base tables contains all available pre generated methods for given model.<br/>
<a href="http://farm8.staticflickr.com/7039/6880080651_90f0f41408_z.jpg"><img src="http://farm8.staticflickr.com/7039/6880080651_90f0f41408_m.jpg" title="Available 'WHERE' conditions" /></a>
<a href="http://farm8.staticflickr.com/7181/6880069229_5f6ba4e72a_b.jpg"><img title="Available 'JOIN' conditions" src="http://farm8.staticflickr.com/7181/6880069229_5f6ba4e72a_m.jpg"/></a>
<a href="http://farm8.staticflickr.com/7207/6880136851_1d6238cb8f.jpg"><img src="http://farm8.staticflickr.com/7207/6880136851_1d6238cb8f_m.jpg" title="Doctrine cli tasks" /></a>
<br/>
<a href="http://pastie.org/3386479">Example of generated base table</a>
<h3>Architecture</h3>
After base tables generation each Doctrine model will have tables will following hierarchy:
<pre><code>class Model_Table_City extends <b>Model_Table_Base_City</b>
{
/* ... */
}</code></pre>
<pre><code>abstract class Model_Table_Base_City extends My_Doctrine_Table_Scoped
{
/* ... */
}</code></pre>
<pre><code>class My_Doctrine_Table_Scoped extends Doctrine_Table
{
/* ... */
}</code></pre>

<h3>Installation</h3>
<b>1. Configuring application.ini:</b><br/>
Configuration options related to the Doctrine's resource is provided in the snippet below:
<pre><code>; ...
pluginpaths.My_Application_Resource = "My/Application/Resource"
resources.frontController.controllerDirectory = APPLICATION_PATH "/controllers"
resources.frontController.params.displayExceptions = 1
resources.doctrine.connections.default.username = "username"
resources.doctrine.connections.default.password = "********"
resources.doctrine.connections.default.hostname = "localhost"
resources.doctrine.connections.default.database = "yourdatabase"
<b>resources.doctrine.generate_models_options.generateTableClasses = true
resources.doctrine.generate_models_options.baseClassesDirectory = ""
resources.doctrine.generate_models_options.baseClassPrefix = "Base_"
resources.doctrine.generate_models_options.suffix = ".php"
resources.doctrine.generate_models_options.classPrefix = "Model_"
resources.doctrine.generate_models_options.tableClassFormat = "Table_%s"
resources.doctrine.generate_models_options.classPrefixFiles = false
resources.doctrine.generate_models_options.baseTableTemplate = APPLICATION_PATH "/../library/My/Doctrine/Template/Table.php"
resources.doctrine.generate_models_options.baseTableClassName = "My_Doctrine_Table_Scoped"
resources.doctrine.generate_models_options.pearStyle = true
</b>resources.doctrine.generate_models_options.phpDocName = "John Doe"
resources.doctrine.generate_models_options.phpDocEmail = "john.doe@example.com"
resources.doctrine.data_fixtures_path = APPLICATION_PATH "/configs/fixtures"
resources.doctrine.models_path = APPLICATION_PATH "/models"
resources.doctrine.migrations_path = APPLICATION_PATH "/configs/migrations"
resources.doctrine.sql_path = APPLICATION_PATH "/configs/sql"
resources.doctrine.yaml_schema_path = APPLICATION_PATH "/configs/schema"
; ...
</code></pre>
If you configured Doctine and ZendFramework on your own, just pay attention to the configuration options marked in bold.<br/>
<b>2. Editing zf.ini:</b><br/>
In your <b>~/.zf/zf.ini</b> file, add My_Component_DoctrineProvider to the loader:
<pre><code>php.include_path = "/full/path/to/your/library"
basicloader.classes.1 = "My_Component_DoctrineProvider"</code></pre>

<h3>Generating base tables</h3>
<pre><code>bernard@ubuntu:~/.zf$ cd /path/to/project/
bernard@ubuntu:/path/to/project/$ zf generate doctrine generate-models-yaml
generate-models-yaml - Generated models successfully from YAML schema
bernard@ubuntu:/path/to/project/$ <b>zf generate doctrine build-tables</b>
build-tables - Base tables have been generated and installed
</code></pre>

