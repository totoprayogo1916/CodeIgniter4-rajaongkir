<?php

/**
 * This file is part of CodeIgniter 4 Rajaongkir.
 *
 * (c) 2022 Toto Prayogo <mail@totoprayogo.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Totoprayogo1916\CodeIgniter\Libraries;

use CodeIgniter\Config\Services;
use Exception;

/**
 * Class Rajaongkir
 * This class provides an interface to interact with the Rajaongkir API for various shipping and logistic services.
 */
class Rajaongkir
{
    // Account types
    public const ACCOUNT_STARTER = 'starter';
    public const ACCOUNT_BASIC   = 'basic';
    public const ACCOUNT_PRO     = 'pro';

    // Account type for the current instance
    protected $accountType = self::ACCOUNT_STARTER;

    // API key for authentication
    protected $apiKey;

    // Response object for handling responses
    protected $response;

    // Array to hold any errors encountered
    public $errors = [];

    // Supported account types
    protected $supportedAccountTypes = [
        self::ACCOUNT_STARTER,
        self::ACCOUNT_BASIC,
        self::ACCOUNT_PRO,
    ];

    // Supported couriers for each account type
    protected $supportedCouriers = [
        self::ACCOUNT_STARTER => ['jne', 'pos', 'tiki'],
        self::ACCOUNT_BASIC   => ['esl', 'jne', 'pcp', 'pos', 'rpx', 'tiki'],
        self::ACCOUNT_PRO     => [
            'cahaya', 'dse', 'esl', 'expedito*', 'first', 'idl', 'indah', 'j&t', 'jet', 'jne',
            'lion', 'ncs', 'ninja-express', 'pahala', 'pandu', 'pcp', 'pos', 'rex', 'rpx', 'sap',
            'sicepat', 'slis', 'star', 'tiki', 'wahana',
        ],
    ];

    // Supported waybills for each account type
    protected $supportedWayBills = [
        self::ACCOUNT_STARTER => [],
        self::ACCOUNT_BASIC   => ['jne'],
        self::ACCOUNT_PRO     => [
            'dse', 'first', 'j&t', 'jet', 'jne', 'pcp', 'pos', 'rpx', 'sap', 'sicepat', 'tiki', 'wahana',
        ],
    ];

    // List of couriers and their descriptions
    protected $couriersList = [
        'cahaya'    => 'Cahaya Logistik (CAHAYA)',
        'dse'       => '21 Express (DSE)',
        'esl'       => 'Eka Sari Lorena (ESL)',
        'expedito*' => 'Expedito*',
        'first'     => 'First Logistics (FIRST)',
        'indah'     => 'Indah Logistic (INDAH)',
        'j&t'       => 'J&T Express (J&T)',
        'jet'       => 'JET Express (JET)',
        'jne'       => 'Jalur Nugraha Ekakurir (JNE)',
        'ncs'       => 'Nusantara Card Semesta (NCS)',
        'pahala'    => 'Pahala Kencana Express (PAHALA)',
        'pandu'     => 'Pandu Logistics (PANDU)',
        'pcp'       => 'Priority Cargo and Package (PCP)',
        'pos'       => 'POS Indonesia (POS)',
        'rpx'       => 'RPX Holding (RPX)',
        'sap'       => 'SAP Express (SAP)',
        'sicepat'   => 'SiCepat Express (SICEPAT)',
        'slis'      => 'Solusi Express (SLIS)',
        'star'      => 'Star Cargo (STAR)',
        'tiki'      => 'Citra Van Titipan Kilat (TIKI)',
        'wahana'    => 'Wahana Prestasi Logistik (WAHANA)',
    ];

    /**
     * Rajaongkir constructor.
     *
     * @param array|string|null $apiKey      The API key or an array containing 'api_key' and optionally 'account_type'.
     * @param string|null       $accountType The account type (starter, basic, pro).
     */
    public function __construct($apiKey = null, $accountType = null)
    {
        if (isset($apiKey)) {
            $this->apiKey = is_array($apiKey) ? ($apiKey['api_key'] ?? null) : $apiKey;
            $accountType  = is_array($apiKey) ? ($apiKey['account_type'] ?? $accountType) : $accountType;
        }

        if (isset($accountType)) {
            $this->setAccountType($accountType);
        }
    }

    /**
     * Set the API key.
     *
     * @param string $apiKey The API key.
     *
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Set the account type.
     *
     * @param string $accountType The account type (starter, basic, pro).
     *
     * @return $this
     *
     * @throws Exception If the account type is invalid.
     */
    public function setAccountType($accountType)
    {
        $accountType = strtolower($accountType);

        if (in_array($accountType, $this->supportedAccountTypes, true)) {
            $this->accountType = $accountType;
        } else {
            throw new Exception('Rajaongkir: Invalid Account Type');
        }

        return $this;
    }

    /**
     * Make a request to the Rajaongkir API.
     *
     * @param string $path   The API endpoint path.
     * @param array  $params The query parameters.
     * @param string $type   The request method (GET, POST).
     *
     * @return mixed The API response or false if an error occurred.
     */
    protected function request($path, $params = [], $type = 'GET')
    {
        $apiUrl = 'https://api.rajaongkir.com';

        switch ($this->accountType) {
            case self::ACCOUNT_PRO:
                $apiUrl = 'https://pro.rajaongkir.com';
                $path   = 'api/' . $path;
                break;

            case self::ACCOUNT_BASIC:
                $path = 'basic/' . $path;
                break;

            case self::ACCOUNT_STARTER:
            default:
                $path = 'starter/' . $path;
                break;
        }

        $client = Services::curlrequest();
        $uri    = $apiUrl . '/' . $path;

        $client->setHeader('key', $this->apiKey);
        $this->response = $client->request($type, $uri, ['query' => $params]);

        $body   = json_decode($this->response->getBody(), true, 512, JSON_THROW_ON_ERROR)['rajaongkir'];
        $status = $body['status'];

        if ($status['code'] === 200) {
            return $body['results'] ?? $body['result'] ?? false;
        }

        $this->errors[$status['code']] = $status['description'];

        return false;
    }

    /**
     * Get the list of couriers.
     *
     * @return array The list of couriers.
     */
    public function getCouriersList()
    {
        return $this->couriersList;
    }

    /**
     * Get the list of provinces.
     *
     * @return mixed The list of provinces or false if an error occurred.
     */
    public function getProvinces()
    {
        return $this->request('province');
    }

    /**
     * Get details of a specific province.
     *
     * @param int $idProvince The province ID.
     *
     * @return mixed The province details or false if an error occurred.
     */
    public function getProvince($idProvince)
    {
        return $this->request('province', ['id' => $idProvince]);
    }

    /**
     * Get the list of cities, optionally filtered by province ID.
     *
     * @param int|null $idProvince
     *
     * @return mixed The list of cities or false if an error occurred.
     */
    public function getCities($idProvince = null)
    {
        $params = isset($idProvince) ? ['province' => $idProvince] : [];

        return $this->request('city', $params);
    }

    /**
     * Get details of a specific city.
     *
     * @param int $idCity The city ID.
     *
     * @return mixed The city details or false if an error occurred.
     */
    public function getCity($idCity)
    {
        return $this->request('city', ['id' => $idCity]);
    }

    /**
     * Get the list of subdistricts, optionally filtered by city ID.
     *
     * @param int|null $idCity The city ID.
     *
     * @return mixed The list of subdistricts or false if an error occurred.
     */
    public function getSubdistricts($idCity = null)
    {
        $params = isset($idCity) ? ['city' => $idCity] : [];

        return $this->request('subdistrict', $params);
    }

    /**
     * Get details of a specific subdistrict.
     *
     * @param int $idSubdistrict The subdistrict ID.
     *
     * @return mixed The subdistrict details or false if an error occurred.
     */
    public function getSubdistrict($idSubdistrict)
    {
        return $this->request('subdistrict', ['id' => $idSubdistrict]);
    }

    /**
     * Get the cost of shipping from origin to destination with specific weight and courier.
     *
     * @param int       $origin          The origin city or subdistrict ID.
     * @param int       $destination     The destination city or subdistrict ID.
     * @param array|int $metrics         The weight or an array of weight and dimensions.
     * @param string    $courier         The courier code.
     * @param string    $originType      The origin type (city, subdistrict).
     * @param string    $destinationType The destination type (city, subdistrict).
     *
     * @return mixed The shipping cost or false if an error occurred.
     */
    public function getCost($origin, $destination, $metrics, $courier, $originType = 'city', $destinationType = 'city')
    {
        $params = [
            'origin'      => $origin,
            'destination' => $destination,
            'courier'     => $courier,
        ];

        if ($originType === 'subdistrict') {
            $params['originType'] = 'subdistrict';
        }

        if ($destinationType === 'subdistrict') {
            $params['destinationType'] = 'subdistrict';
        }

        if (is_array($metrics)) {
            $params = array_merge($params, $metrics);
        } else {
            $params['weight'] = $metrics;
        }

        return $this->request('cost', $params, 'POST');
    }

    /**
     * Get the waybill tracking information for a specific courier.
     *
     * @param string $waybill The waybill number.
     * @param string $courier The courier code.
     *
     * @return mixed The waybill tracking information or false if an error occurred.
     */
    public function getWayBill($waybill, $courier)
    {
        if ($this->accountType === self::ACCOUNT_STARTER) {
            $this->errors[301] = 'Unsupported Way Bill Request.';

            return false;
        }

        return $this->request('waybill', ['waybill' => $waybill, 'courier' => $courier], 'POST');
    }

    /**
     * Get the currency for the current account type.
     *
     * @return mixed The currency information or false if an error occurred.
     */
    public function getCurrency()
    {
        if ($this->accountType !== self::ACCOUNT_STARTER) {
            return $this->request('currency');
        }

        $this->errors[301] = 'Unsupported Get Currency. Tipe akun starter tidak mendukung pengecekan currency.';

        return false;
    }
}
