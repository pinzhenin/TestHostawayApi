<?php

use Phalcon\Mvc\Model;
use Phalcon\Validation;
use Phalcon\Validation\Validator\{
    InclusionIn,
    PresenceOf,
    Regex
};

class Contacts extends Model
{

    public $id;
    public $firstName;
    public $lastName;
    public $phoneNumber;
    public $countryCode;
    public $timezoneName;
    public $insertedOn;
    public $updatedOn;
    protected $apiHostaway;

    public function beforeCreate()
    {
        // Set the creation date
        $this->insertedOn = date('Y-m-d H:i:s');
        // Set the modification date
        $this->updatedOn = date('Y-m-d H:i:s');
    }

    public function beforeUpdate()
    {
        // Set the modification date
        $this->updatedOn = date('Y-m-d H:i:s');
    }

    // List of fields to use in assign() method by default
    const FIELDS_ASSIGN = [
        'firstName', 'lastName', 'phoneNumber', 'countryCode', 'timezoneName'
    ];

    public function assign(array $data, $dataColumnMap = NULL, $whiteList = self::FIELDS_ASSIGN)
    {
        return parent::assign($data, $dataColumnMap, $whiteList);
    }

    // List of fields to use in toArray() method by default
    const FIELDS_TO_ARRAY = [
        'id', 'firstName', 'lastName', 'phoneNumber', 'countryCode', 'timezoneName'
    ];

    public function toArray($columns = self::FIELDS_TO_ARRAY)
    {
        return parent::toArray($columns);
    }

    /**
     * Returns list of validation errors
     * @return array
     */
    public function errors()
    {
        $errors = [];
        foreach ($this->getMessages() as $message) {
            $errors[] = $message->getMessage();
        }
        return $errors;
    }

    public function validation()
    {
        $apiHostaway = $this->getApiHostaway();
        $resources = $apiHostaway->getResources(['countries', 'timezones']);

        $validator = new Validation();

        // Filter any extra space
        $validator->setFilters('firstName', 'trim');
        $validator->setFilters('lastName', 'trim');
        $validator->setFilters('phoneNumber', 'trim');

        // First name validation
        $validator->add(
            'firstName',
            new PresenceOf(
                [
                'message' => 'The first name is required',
                ]
            )
        );

        // Phone number validation
        $validator->add(
            'phoneNumber',
            new PresenceOf(
                [
                'message' => 'The phone number is required',
                ]
            )
        );
        $validator->add(
            'phoneNumber',
            new Regex(
                [
                'message' => 'Wrong phone number. Use format: +NN NNN NNNNNNNNN',
                'pattern' => '/^\+?[0-9 ]+$/',
                'allowEmpty' => TRUE
                ]
            )
        );

        // Country code validation
        if ($resources['countries']['status'] === 'success') {
            $countryCodes = array_keys($resources['countries']['result']);

            $validator->add(
                'countryCode',
                new InclusionIn(
                    [
                    'message' => 'Wrong country code. See ISO 3166-1 alpha-2 codes: https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2',
                    'domain' => &$countryCodes,
                    'allowEmpty' => TRUE
                    ]
                )
            );
        }
        else {
            // errmsg
            $this->appendMessage(
                new Message('Cannot validate country code: api error')
            );
        }

        // Timezone name validation
        if ($resources['timezones']['status'] === 'success') {
            $timezoneNames = array_keys($resources['timezones']['result']);

            $validator->add(
                'timezoneName',
                new InclusionIn(
                    [
                    'message' => 'Wrong timezone name. See list of tz database time zones: https://en.wikipedia.org/wiki/List_of_tz_database_time_zones',
                    'domain' => &$timezoneNames,
                    'allowEmpty' => TRUE
                    ]
                )
            );
        }
        else {
            // errmsg
            $this->appendMessage(
                new Message('Cannot validate timezone name: api error')
            );
        }

        return $this->validate($validator);
    }

    public function getApiHostaway()
    {
        return $this->apiHostaway;
    }

    public function setApiHostaway(ApiHostaway $service)
    {
        $this->apiHostaway = $service;
    }
}
