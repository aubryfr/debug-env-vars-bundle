# debug-env-vars-bundle

Symfony bundle to list all the environment variables used in parameter definitions.

Any referenced environment variable will be listed :
 - unless it contains a transformer different to the following : "int", "string", "bool"
 - as mandatory unless an "env(*)" parameter is defined (see example)

## Installation

```php
composer require aubry/env-vars-debug-bundle
```

Register the bundle, in **dev** mode only :
```php
$bundles[] = new Aubry\EnvVarsDebug\Bundle\EnvVarsDebugBundle();
```

## Usage

```php
bin/console aubry:debug:env-vars
```
## Example

For the given parameters (YAML) definition :
```yaml
parameters:
    param1: "%env(ENV_VAR_1)%"
    param2: "%env(int:ENV_VAR_2)%"
    env(ENV_VAR_3): "default value"
    param3: "%env(ENV_VAR_3)%"
```

the console command will output the following result :
```
 Variable                     Mandatory    Type
---------------------------  -----------  ------
 ENV_VAR_1                    true         string
 ENV_VAR_2                    true         int
 ENV_VAR_3                    false        string
```
