<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class ContactsMigration_100
 */
class ContactsMigration_100 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('contacts', [
                'columns' => [
                    new Column(
                        'id',
                        [
                            'type' => Column::TYPE_INTEGER,
                            'unsigned' => true,
                            'notNull' => true,
                            'autoIncrement' => true,
                            'size' => 10,
                            'first' => true
                        ]
                    ),
                    new Column(
                        'firstName',
                        [
                            'type' => Column::TYPE_CHAR,
                            'notNull' => true,
                            'size' => 50,
                            'after' => 'id'
                        ]
                    ),
                    new Column(
                        'lastName',
                        [
                            'type' => Column::TYPE_CHAR,
                            'size' => 50,
                            'after' => 'firstName'
                        ]
                    ),
                    new Column(
                        'phoneNumber',
                        [
                            'type' => Column::TYPE_CHAR,
                            'notNull' => true,
                            'size' => 20,
                            'after' => 'lastName'
                        ]
                    ),
                    new Column(
                        'countryCode',
                        [
                            'type' => Column::TYPE_CHAR,
                            'size' => 2,
                            'after' => 'phoneNumber'
                        ]
                    ),
                    new Column(
                        'timezoneName',
                        [
                            'type' => Column::TYPE_CHAR,
                            'size' => 20,
                            'after' => 'countryCode'
                        ]
                    ),
                    new Column(
                        'insertedOn',
                        [
                            'type' => Column::TYPE_TIMESTAMP,
                            'default' => "current_timestamp()",
                            'size' => 1,
                            'after' => 'timezoneName'
                        ]
                    ),
                    new Column(
                        'updatedOn',
                        [
                            'type' => Column::TYPE_TIMESTAMP,
                            'size' => 1,
                            'after' => 'insertedOn'
                        ]
                    )
                ],
                'indexes' => [
                    new Index('PRIMARY', ['id'], 'PRIMARY')
                ],
                'options' => [
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '11',
                    'ENGINE' => 'InnoDB',
                    'TABLE_COLLATION' => 'utf8_general_ci'
                ],
            ]
        );
    }

    /**
     * Run the migrations
     *
     * @return void
     */
    public function up()
    {

    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down()
    {

    }

}
