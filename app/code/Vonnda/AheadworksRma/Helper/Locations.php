<?php
namespace Vonnda\AheadworksRma\Helper;

class Locations
{

    protected $locations = [
        'alom' => [
            'contact' =>  NULL,
            'company' => 'ALOM c/o Molekule RMA',
            'phone' => '5103603600',
            'street' => ['48105 Warm Springs Blvd'],
            'city' => 'Fremont',
            'state' => 'CA',
            'zip' => '94539-7498',
            'country' => 'US'
        ],
        'godirect_canada' => [
            'contact' => NULL,
            'company' => 'GoDirect c/o Molekule RMA',
            'phone' => '9055648688',
            'street' => ['460 Admiral Blvd'],
            'city' => 'Mississauga',
            'state' => 'ON',
            'zip' => 'L5T3A3',
            'country' => 'CA'
        ],
        'grs' => [
            'contact' => NULL,
            'company' => 'S&B/GRS c/o Molekule RMA',
            'phone' => '8175679220',
            'street' => ['13301 Park Vista Blvd', 'Suite 100'],
            'city' => 'Fort Worth',
            'state' => 'TX',
            'zip' => '76177',
            'country' => 'US'
        ],
        'peco_zero' => [
            'contact' => NULL,
            'company' => 'Molekule c/o PZ RMA',
            'phone' => '8634557656',
            'street' => ['4000 North Combee Road', 'Suite #37'],
            'city' => 'Lakeland',
            'state' => 'FL',
            'zip' => '33805',
            'country' => 'US'
        ]
    ];
    protected $requestLocations = [];

    public function __construct()
    {
        $this->setRequestLocations();
    }

    public function getLocations()
    {
        return $this->locations;
    }

    public function getLocation($code)
    {
        return $this->locations[$code];
    }

    public function getLocationData($code, $field)
    {
        return $this->locations[$code][$field];
    }

    public function getRequestLocations()
    {
        return $this->requestLocations;
    }

    public function getRequestLocation($code)
    {
        return $this->requestLocations[$code];
    }

    protected function setRequestLocations()
    {
        $requestLocations = [];
        foreach ($this->locations as $key => $location) {
            $requestLocations[$key] = [
                'Contact' => [
                    'PersonName' => $location['contact'],
                    'CompanyName' => $location['company'],
                    'PhoneNumber' => $location['phone']
                ],
                'Address' => [
                    'StreetLines' => $location['street'],
                    'City' => $location['city'],
                    'StateOrProvinceCode' => $location['state'],
                    'PostalCode' => $location['zip'],
                    'CountryCode' => $location['country']
                ]
            ];
        }
        $this->requestLocations = $requestLocations;
    }

}