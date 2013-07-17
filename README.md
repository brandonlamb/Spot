Spot PHP ORM+ODM
----------------
For Relational Databases and MongoDB

[![Build Status](https://www.travis-ci.org/brandonlamb/Spot.png?branch=master)](https://www.travis-ci.org/brandonlamb/Spot)

Connecting to a Database
========================
The `Spot\Config` object stores and references database connections by name.
Create a new instance of `Spot\Config` and add database connections created outside
of Spot. This was a change to allow your app to create the raw PDO connection and
just reuse this. A big complaint I have always had is pretty much every ORM/Model
class always wants to create this for you instead of being passed this connection.

```
// PostgreSQL
$db = new Pdo('pgsql:host=localhost;dbname=jdoe', 'jdoe', 'mypass');

$cfg = \Spot\Config::getInstance();
$adapter = $cfg->addConnection('db', new \Spot\Adapter\Pgsql($db));

$adapter = $cfg->connection('db');
```

Accessing the Mapper
====================

Since Spot follows the DataMapper design pattern, you will need a mapper
instance for working with object Entities and database tables.

```
$mapper = new \Spot\Mapper($cfg);
```

Since you have to have access to your mapper anywhere you use the
database, most people create a helper method to create a mapper instance
once and then return the same instance when required again. Such a
helper method might look something like this:

```
function get_mapper() {
    static $mapper;
    if($mapper === null) {
        $mapper = new \Spot\Mapper($cfg);
    }
    return $mapper;
}
```

Or if you have a Registry class in your framework:

```
$registry = Registry::getInstance();
$registry->set('mapper', $mapper);

$mapper = Register::get('mapper');
```

Or using a Dependency Injection Container

```
$di->setShared('mapper', $mapper);

$mapper = $di->getShared('mapper');
```

Creating Entities
=================

Entity classes can be named and namespaced however you want to set them
up within your project structure. For the following examples, the
Entities will just be prefixed with an `Entity` namespace for easy psr-0
compliant autoloading.

```
namespace Entity;

class Post extends \Spot\Entity
{
    protected static $datasource = 'posts';

    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'title' => array('type' => 'string', 'required' => true),
            'body' => array('type' => 'text', 'required' => true),
            'status' => array('type' => 'int', 'default' => 0, 'index' => true),
            'date_created' => array('type' => 'datetime')
        );
    }

    public static function relations()
    {
        return array(
            // Each post entity 'hasMany' comment entites
            'comments' => array(
                'type' => 'HasMany',
                'entity' => 'Entity_Post_Comment',
                'where' => array('post_id' => ':entity.id'),
                'order' => array('date_created' => 'ASC')
            )
        );
    }
}
```

Another entity example of a model class inside an application's Model namespace.
This is the simplest definition, only defining the model's fields.

```
<?php
namespace Blog\Model;
use \Spot\Entity;

class Game extends Entity
{
	protected static $datasource = 'game';

	public static function fields()
	{
		return array(
			'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
			'status_id' => array('type' => 'int', 'default' => 0, 'index' => true),
			'date_created' => array('type' => 'datetime', 'default' => date('Y-m-d h:m:i'), 'required' => true),
			'image_count' => array('type' => 'int', 'default' => 0, 'index' => true),
			'name' => array('type' => 'string', 'required' => true),
			'slug' => array('type' => 'string', 'required' => true),
		);
	}
}

```

### Built-in Field Types

All the basic field types are built-in with all the default
functionality provided for you:

 * `string`
 * `int`
 * `float/double/decimal`
 * `boolean`
 * `text`
 * `date`
 * `datetime`
 * `timestamp`
 * `year`
 * `month`
 * `day`

#### Registering Custom Field Types

If you want to register your own custom field type with custom
functionality on get/set, have a look at the clases in the `Spot\Type`
namespace, make your own, and register it in `Spot\Config`:

```
$this->setTypeHandler('string', '\Spot\Type\String');
```

### Relation Types

Entity relation types are:

 * `HasOne`
 * `HasMany`
 * `HasManyThrough`


Finders (Mapper)
================

The main finders used most are `all` to return a collection of entities,
and `first` or `get` to return a single entity matching the conditions.

### all(entityName, [conditions])

Find all `entityName` that matches the given conditions and return a
`Spot\Entity\Collection` of loaded `Spot\Entity` objects.
```
// Conditions can be the second argument
$posts = $mapper->all('Entity\Post', array('status' => 1));

// Or chained using the returned `Spot\Query` object - results identical to above
$posts = $mapper->all('Entity\Post')->where(array('status' => 1));

// Or building up a query programmatically
$posts = $mapper->all('Entity\Post');
$posts->where(array('date_created :gt', date('Y-m-d'));

... // Do some checks

$posts->limit(10);
```

Since a `Spot\Query` object is returned, conditions and other statements
can be chained in any way or order you want. The query will be
lazy-executed on interation or `count`, or manually by ending the chain with a
call to `execute()`.

### first(entityName, [conditions])

Find and return a single `Spot\Entity` object that matches the criteria.
```
$post = $mapper->first('Entity\Post', array('title' => "Test Post"));
```

Iterating Over Results
======================

```
// Fetch mapper from DI container
$mapper = $di->getShared('mapper');

// Get Query object to add constraints
$posts = $mapper->all('Entity\Posts');

// Find posts where the commenter's user_id is 123
$posts->where(array('user_id :eq', 123));

// Only get 10 results
$limit = (int) $_POST['limit'];
$posts->limit($limit);

// Loop over results
foreach ($posts as $post) {
	echo "Title: " . $post->title . "<br>";
	echo "Created: " . $post->date_created . "<br>";
}
```

