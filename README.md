# ormi
ORM for PHP MySQLi

description is under construction

## Examples
```php
use \pgood\mysql\connection
	,\pgood\mysql\table
	,\pgood\mysql\field;

/*
 * Connection
 */
$c = new connection('localhost','username','password','database');

/*
 * Table definition
 */
$t = new table($c,'table_name');
//or
$t = $c->table('table_name');

$t->field('id',field::INT)->autoIncrement(true)->notNull(true);
$t->field('name',field::STRING)->length(255);
$t->field('email',field::STRING);
$t->field('created',field::DATE);

/*
 * Create table if not exists
 * pass primary key as a parameter (optional)
 */
if(!$t->exists())
	$t->create('id');

/*
 * Additional indexes
 */
$t->index('index_name_1','name','UNIQUE');
$t->index('index_name_2',['name','email']);

/*
 * Insert record
 */
$t->insert($t->row('name','email')->fill('John Doe','johndoe@email.com'));
$t->insert($t->row('name','created')->fill('Jane',time()));

//another way
$fieldset = $t->row('name','email','created');
$fieldset->name = 'Vasiliy Pupkin';
$fieldset->email = 'vasya@email.com';
$fieldset->created = 'NOW()';
$t->insert($fieldset);

/*
 * Update record
 */
$fieldset = $t->row('name','email')
	->fill('New name','new@email.com');
$t->update($fieldset,$t->where('id',2));

/*
 * Select
 */
$select = $t->select()
	->fields('name','email','created')
	->dateFormat('d.m.Y H:i:s')
	->orderBy('created','desc')
	->where('id','>',3)
	->limit(100);

/*
 * Get num rows
 */
$numRows = $select->count('id');

/*
 * Execute query and get results
 */
if($rowset = $select->query()){
	//show results
	foreach($rowset as $fieldset){
		echo '<p>'
			,$fieldset->name,'<br>'
			,$fieldset->email,'<br>'
			,$fieldset->created,'<br>'
			,'</p>';
	}
	//get results as array
	$arResults = $rowset->toArray();
	//get particular row as array
	$arRow = $rowset->toArray(0);
}
```

## Join example
```php
use \pgood\mysql\connection
	,\pgood\mysql\field;

$c = new connection('localhost','username','password','database');

/*
 * First table definition
 */
$t1 = $c->table('users');
$t1->alias('t1');
$t1->field('id',field::INT)->autoIncrement(true)->notNull(true);
$t1->field('name',field::STRING);
$t1->field('email',field::STRING);
$t1->field('created',field::DATE);
if(!$t1->exists())
	$t1->create('id');

/*
 * Second table definition
 */
$t2 = $c->table('linked');
$t2->field('user_id',field::INT)->notNull(true);
$t2->field('some_data',field::STRING);
if(!$t2->exists()){
	$t2->create();
	$t2->index('index_1','user_id');
}

/*
 * Join object
 */
$t2Join = $tLinked->join('t2')
	->type('left')
	->on('link_id',$t1->field('id'));

/*
 * Select
 */
$s = $t->select()
	->join($t2Join)
	->fields('id','name','email',$t2Join->field('some_data'))
	->groupBy('id')
	->orderBy('name');

/*
 * Execute query and show results
 */
if($rowset = $s->query()){
	foreach($rowset as $fieldset){
		echo '<p>'
			,$fieldset->id,'<br>'
			,$fieldset->name,'<br>'
			,$fieldset->email,'<br>'
			,'</p>';
	}
}

```